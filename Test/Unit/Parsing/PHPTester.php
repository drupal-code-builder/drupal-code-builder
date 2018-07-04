<?php

namespace DrupalCodeBuilder\Test\Unit\Parsing;

use PHPUnit\Framework\Assert;
use PHP_CodeSniffer;
use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Helper class for parsing and testing PHP.
 */
class PHPTester {

  /**
   * Construct a new PHPTester.
   *
   * @param string $php_code
   *   The PHP code that should be tested.
   */
  public function __construct($php_code) {
    $this->phpCode = $php_code;

    $this->assertWellFormedPHP();

    // Run the code through the parser once we know it's correct PHP, so the
    // parsed node tree is ready for any subsequent assertions.
    $this->parseCode();
  }

  /**
   * Assert the code is correctly-formed PHP.
   */
  protected function assertWellFormedPHP() {
    // Escape all the backslashes. This is to prevent any escaped character
    // sequences from being formed by namespaces and long classes, e.g.
    // 'namespace Foo\testmodule;' will treat the '\t' as a tab character.
    // TODO: find a better way to do this that doesn't involve changing the
    // code.
    $escaped_code = str_replace('\\', '\\\\', $this->phpCode);

    // Pass the code to PHP for linting.
    $output = NULL;
    $exit = NULL;
    $result = exec(sprintf('echo %s | php -l', escapeshellarg($escaped_code)), $output, $exit);

    if (!empty($exit)) {
      // Dump the code lines as an array so we get the line numbers.
      $code_lines = explode("\n", $this->phpCode);
      // Re-key it so the line numbers start at 1.
      $code_lines = array_combine(range(1, count($code_lines)), $code_lines);
      dump($code_lines);

      Assert::fail("Error parsing the code resulted in: \n" . implode("\n", $output));
    }
  }

  /**
   * Asserts that the code adheres to Drupal Coding Standards.
   *
   * This runs PHP Code Sniffer using the Drupal Coder module's standards.
   *
   * @param string[] $excluded_sniffs
   *   (optional) An array of names of PHPCS sniffs to exclude from testing.
   */
  public function assertDrupalCodingStandards(array $excluded_sniffs = []) {
    $phpcs = $this->setUpPHPCS($excluded_sniffs);

    // Process the file with PHPCS.
    // We need to pass in a value for the filename, even though the file does
    // not exist, as the Drupal standard uses it to try to check the file when
    // it tries to find an associated module .info file to detect the Drupal
    // major version in DrupalPractice_Project::getCoreVersion(). We don't use
    // the DrupalPractice standard, so that shouldn't concern us, but the
    // Drupal_Sniffs_Array_DisallowLongArraySyntaxSniff sniff calls that to
    // determine whether to run itself. This check for the Drupal code version
    // will fail, which means that the short array sniff will not be run.
    $phpcsFile = $phpcs->processFile('fictious file name', $this->phpCode);

    $error_count   = $phpcsFile->getErrorCount();
    $warning_count = $phpcsFile->getWarningCount();

    $total_error_count = $error_count + $warning_count;

    if (empty($total_error_count)) {
      // No pass method :(
      //$this->pass("PHPCS passed.");
      return;
    }

    // Get the reporting to process the errors.
    $this->reporting = new \PHP_CodeSniffer_Reporting();
    $reportClass = $this->reporting->factory('full');
    // Prepare the report, but don't call generateFileReport() as that echo()s
    // it!
    $reportData  = $this->reporting->prepareFileReport($phpcsFile);
    //$reportClass->generateFileReport($reportData, $phpcsFile);

    // Dump the code lines as an array so we get the line numbers.
    $code_lines = explode("\n", $this->phpCode);
    // Re-key it so the line numbers start at 1.
    $code_lines = array_combine(range(1, count($code_lines)), $code_lines);
    dump($code_lines);

    foreach ($reportData['messages'] as $line_number => $columns) {
      foreach ($columns as $column_number => $messages) {
        $code_line = $code_lines[$line_number];
        $before = substr($code_line, 0, $column_number - 1);
        $after = substr($code_line, $column_number - 1);
        dump($before . '^' . $after);
        foreach ($messages as $message_info) {
          dump("{$message_info['type']}: line $line_number, column $column_number: {$message_info['message']} - {$message_info['source']}");
        }
      }
    }

    Assert::fail("PHPCS failed with $error_count errors and $warning_count warnings.");
  }

  /**
   * Sets up PHPCS.
   *
   * Helper for assertDrupalCodingStandards().
   */
  protected function setUpPHPCS($excluded_sniffs) {
    // Set runtime config.
    PHP_CodeSniffer::setConfigData(
      'installed_paths',
      __DIR__ . '/../../../vendor/drupal/coder/coder_sniffer',
      TRUE
    );

    // Check that the installed standard works.
    //$installedStandards = PHP_CodeSniffer::getInstalledStandards();
    //dump($installedStandards);
    //exit();

    $phpcs = new PHP_CodeSniffer(
      // Verbosity.
      0,
      // Tab width
      0,
      // Encoding.
      'iso-8859-1',
      // Interactive.
      FALSE
    );

    $phpcs->initStandard(
      'Drupal',
      // Include all standards.
      [],
      // Exclude given standards.
      $excluded_sniffs
    );

    // Mock a PHP_CodeSniffer_CLI object, as the PHP_CodeSniffer object expects
    // to have this and be able to retrieve settings from it.
    $prophet = new \Prophecy\Prophet;
    $prophecy = $prophet->prophesize();
    $prophecy->willExtend(\PHP_CodeSniffer_CLI::class);
    // No way to set these on the phpcs object.
    $prophecy->getCommandLineValues()->willReturn([
      'reports' => [
        "full" => NULL,
      ],
      "showSources" => false,
      "reportWidth" => null,
      "reportFile" => null
    ]);
    $phpcs_cli = $prophecy->reveal();
    // Have to set these properties, as they are read directly, e.g. by
    // PHP_CodeSniffer_File::_addError()
    $phpcs_cli->errorSeverity = 5;
    $phpcs_cli->warningSeverity = 5;

    // Set the CLI object on the PHP_CodeSniffer object.
    $phpcs->setCli($phpcs_cli);

    return $phpcs;
  }

  /**
   * Parses a code file string and sets various parser nodes on this test.
   *
   * This populates $this->parser_nodes with groups parser nodes, after
   * resetting it from any previous call to this method.
   */
  protected function parseCode() {
    $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    try {
      $ast = $parser->parse($this->phpCode);
    }
    catch (Error $error) {
      Assert::fail("Parse error: {$error->getMessage()}");
    }

    //dump($ast);

    // Reset our array of parser nodes.
    // This then passed into the anonymous visitor class, and populated with the
    // nodes we are interested in for subsequent assertions.
    $this->parser_nodes = [];

    // A recursive visitor that groups the parser nodes by type, so subsequent
    // assertions can easily find them.
    $recursive_visitor = new class($this->parser_nodes) extends NodeVisitorAbstract {

      /**
       * Constructor for the visitor.
       *
       * Receives the array of parser nodes from the outer PHPTester object by
       * reference, so we can get data out of the visitor object.
       *
       * @param array $nodes
       *   The PHPTester's array of parser nodes.
       */
      public function __construct(&$nodes) {
        $this->nodes = &$nodes;
      }

      public function enterNode(Node $node) {
        switch (get_class($node)) {
          case \PhpParser\Node\Stmt\Namespace_::class:
            $this->nodes['namespace'][] = $node;
            break;
          case \PhpParser\Node\Stmt\Use_::class:
            $this->nodes['imports'][] = $node;
            break;
          case \PhpParser\Node\Stmt\Class_::class:
            $this->nodes['classes'][$node->name] = $node;
            break;
          case \PhpParser\Node\Stmt\Interface_::class:
            $this->nodes['interfaces'][$node->name] = $node;
            break;
          case \PhpParser\Node\Stmt\Property::class:
            $this->nodes['properties'][$node->props[0]->name] = $node;
            break;
          case \PhpParser\Node\Stmt\TraitUse::class:
            $this->nodes['traits'][$node->traits[0]->parts[0]] = $node;
            break;
          case \PhpParser\Node\Stmt\Function_::class:
            $this->nodes['functions'][$node->name] = $node;
            break;
          case \PhpParser\Node\Stmt\ClassMethod::class:
            $this->nodes['methods'][$node->name] = $node;
            break;
        }
      }
    };

    $traverser = new NodeTraverser();
    $traverser->addVisitor($recursive_visitor);

    $ast = $traverser->traverse($ast);
  }

  /**
   * Asserts the parsed code is entirely procedural.
   *
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertIsProcedural($message = NULL) {
    $message = $message ?? "The file contains only procedural code.";

    Assert::assertArrayNotHasKey('classes', $this->parser_nodes, $message);
    Assert::assertArrayNotHasKey('interfaces', $this->parser_nodes, $message);
    // Technically we should cover traits too, but we don't generate any of
    // those.
  }

  /**
   * Asserts the parsed code imports the given class.
   *
   * @param string[] $class_name
   *   Array of the full class name pieces.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertImportsClassLike($class_name_parts, $message = NULL) {
    $class_name = implode('\\', $class_name_parts);
    $message = $message ?? "The full class name for the parent class {$class_name} is imported.";

    // If the given class is in the current namespace, skip this assertion, as
    // it should not be imported.
    // TODO: assert it's NOT imported!
    if (!empty($this->parser_nodes['namespace'])) {
      $expected_class_namespace = array_slice($class_name_parts, 0, -1);
      if ($expected_class_namespace == $this->parser_nodes['namespace'][0]->name->parts) {
        return;
      }
    }

    // Find the matching import statement.
    $seen = [];
    foreach ($this->parser_nodes['imports'] as $use_node) {
      if ($use_node->uses[0]->name->parts === $class_name_parts) {
        return;
      }

      $seen[] = implode('\\', $use_node->uses[0]->name->parts);
    }

    // Add the seen array, as PHPUnit doesn't output the searched array when
    // assertContains() fails.
    $message .= "\n" . print_r($seen, TRUE);

    Assert::assertContains($class_name, $seen, $message);
  }

  /**
   * Asserts the parsed code contains the class name.
   *
   * @param string $class_name
   *   The full class name, without the leading \.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasClass($full_class_name, $message = NULL) {
    $class_name_parts = explode('\\', $full_class_name);
    $class_short_name = end($class_name_parts);
    $namespace_parts = array_slice($class_name_parts, 0, -1);

    $message = $message ?? "The file contains the class {$full_class_name}.";

    // All the class files we generate contain only one class.
    Assert::assertCount(1, $this->parser_nodes['classes']);
    Assert::assertArrayHasKey($class_short_name, $this->parser_nodes['classes'], $message);

    // Check the namespace of the class.
    if (count($class_name_parts) > 1) {
      Assert::assertCount(1, $this->parser_nodes['namespace']);
      Assert::assertEquals($namespace_parts, $this->parser_nodes['namespace'][0]->name->parts, $message);
    }
  }

  /**
   * Asserts that the class's docblock contains the given line.
   *
   * @param string $line
   *   The line to search for, without the docblock formatting, i.e. without
   *   the '*' or margin space. Indented lines will need to include their
   *   indentation, however.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertClassDocBlockHasLine($line, $message = NULL) {
    // All the class files we generate contain only one class.
    Assert::assertCount(1, $this->parser_nodes['classes']);
    $class_node = reset($this->parser_nodes['classes']);
    $docblock_text = $class_node->getAttribute('comments')[0]->getText();
    $docblock_lines = explode("\n", $docblock_text);

    // Trim off the docblock formatting.
    array_walk($docblock_lines, function(&$line) {
      $line = str_replace(" * ", '', $line);
    });

    $message = $message ?? "The docblock has the line '{$line}': " . print_r($docblock_lines, TRUE);

    Assert::assertContains($line, $docblock_lines, $message);
  }

  /**
   * Gets an annotation tester for the class annotation.
   *
   * @return \DrupalCodeBuilder\Test\Unit\Parsing\AnnotationTester
   *   The annotation tester.
   */
  public function getAnnotationTesterForClass() {
    Assert::assertCount(1, $this->parser_nodes['classes']);
    $class_node = reset($this->parser_nodes['classes']);
    $docblock_text = $class_node->getAttribute('comments')[0]->getText();

    $annotation_tester = new AnnotationTester($docblock_text);
    return $annotation_tester;
  }

  /**
   * Asserts the parsed code defines the interface.
   *
   * @param string $full_interface_name
   *   The full interface name, without the leading \.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasInterface($full_interface_name, $message = NULL) {
    $interface_name_parts = explode('\\', $full_interface_name);
    $interface_short_name = end($interface_name_parts);
    $namespace_parts = array_slice($interface_name_parts, 0, -1);

    $message = $message ?? "The file contains the interface {$interface_short_name}.";

    // All the class files we generate contain only one class.
    Assert::assertCount(1, $this->parser_nodes['interfaces']);
    Assert::assertArrayHasKey($interface_short_name, $this->parser_nodes['interfaces']);

    // Check the namespace of the interface.
    Assert::assertCount(1, $this->parser_nodes['namespace']);
    Assert::assertEquals($namespace_parts, $this->parser_nodes['namespace'][0]->name->parts);
  }

  /**
   * Asserts that the defined interface has the expected parents.
   *
   * @param string[] $expected_parent_interface_full_names
   *   An array of fully-qualified names of the parent interfaces, without the
   *   leading '\'.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertInterfaceHasParents($expected_parent_interface_full_names, $message = NULL) {
    // There will be only one interface.
    $interface_node = reset($this->parser_nodes['interfaces']);
    $interface_name = $interface_node->name;

    $message_parent_interfaces = implode(', ', $expected_parent_interface_full_names);
    $message = $message ?? "The interface {$interface_name} inherits from the interfaces {$message_parent_interfaces}.";

    foreach ($interface_node->extends as $parent_name_node) {
      $actual_parent_interface_full_names[] = $this->resolveImportedClassLike($parent_name_node->parts[0]);
    }

    // Sort both arrays, as PHPUnit does not have an order-irrelevant array
    // comparison assertion :(
    sort($expected_parent_interface_full_names);
    sort($actual_parent_interface_full_names);

    Assert::assertEquals($expected_parent_interface_full_names, $actual_parent_interface_full_names, $message);
  }

  /**
   * Gets the fully-qualified name for an imported class-like.
   *
   * WARNING: This will not handle aliased class imports! But none of our
   * generated code under test uses these anyway.
   *
   * @param string $name
   *   The short name for a class, interface, or trait that is imported.
   *
   * @return string
   *   The full name, without the leading '\'.
   */
  protected function resolveImportedClassLike($name) {
    foreach ($this->parser_nodes['imports'] as $use_node) {
      if ($use_node->uses[0]->name->getLast() === $name) {
        return $use_node->uses[0]->name->toString();
      }
    }

    Assert::fail("An import statement was found for the short name {$name}.");
  }

  /**
   * Asserts the parsed code's class extends the given parent class.
   *
   * @param string $class_name
   *   The full parent class name, without the leading \.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertClassHasParent($parent_full_class_name, $message = NULL) {
    $parent_class_name_parts = explode('\\', $parent_full_class_name);

    // There will be only one class.
    $class_node = reset($this->parser_nodes['classes']);
    $class_name = $class_node->name;

    $parent_class_short_name = end($parent_class_name_parts);

    $message = $message ?? "The class {$class_name} has inherits from parent class {$parent_full_class_name}.";

    // Check the class is declared as extending the short parent name.
    $extends_node = $class_node->extends;
    Assert::assertTrue($extends_node->isUnqualified(), "The class parent is unqualified.");
    Assert::assertEquals($parent_class_short_name, $extends_node->getLast(), $message);

    // Check the full parent name is imported.
    $this->assertImportsClassLike($parent_class_name_parts, $message);
  }

  /**
   * Asserts that the parsed class implements the given interfaces.
   *
   * @param string[] $expected_interface_names
   *   An array of fully-qualified interface names, without the leading '\'.
   */
  public function assertClassHasInterfaces($expected_interface_names) {
    // There will be only one class.
    $class_node = reset($this->parser_nodes['classes']);

    $class_node_interfaces = [];
    foreach ($class_node->implements as $implements) {
      Assert::assertCount(1, $implements->parts);
      $class_node_interfaces[] = $implements->parts[0];
    }

    foreach ($expected_interface_names as $interface_full_name) {
      $interface_parts = explode('\\', $interface_full_name);

      Assert::assertContains(end($interface_parts), $class_node_interfaces);

      // An interface with no namespace won't have an import statement.
      if (count($interface_parts) == 1) {
        continue;
      }

      $this->assertImportsClassLike($interface_parts);
    }
  }

  /**
   * Asserts that the parsed class uses the given traits.
   *
   * @param string[] $expected_trait_full_names
   *   An array of fully-qualified trait names, without the leading '\'.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertClassHasTraits($expected_trait_full_names, $message = NULL) {
    if (empty($expected_trait_full_names)) {
      $message = $message ?? "The class does not use any traits.";

      Assert::assertArrayNotHasKey('traits', $this->parser_nodes, $message);

      return;
    }

    $message_traits = implode(', ', $expected_trait_full_names);
    $message = $message ?? "The class uses the traits {$message_traits}.";

    $actual_trait_full_names = [];
    foreach ($this->parser_nodes['traits'] as $trait_node) {
      $actual_trait_full_names[] = $this->resolveImportedClassLike($trait_node->traits[0]->parts[0]);
    }

    // Sort both arrays, as PHPUnit does not have an order-irrelevant array
    // comparison assertion :(
    sort($expected_trait_full_names);
    sort($actual_trait_full_names);

    Assert::assertEquals($expected_trait_full_names, $actual_trait_full_names, $message);
  }

  /**
   * Asserts that the parsed class does not implement the given interfaces.
   *
   * TODO: Does not work for the case where the class name matches but the
   * namespace does not. Basically because we don't need to test for this yet,
   * so YAGNI.
   *
   * @param string[] $not_expected_interface_names
   *   An array of fully-qualified interface names, without the leading '\'.
   */
  public function assertClassHasNotInterfaces($not_expected_interface_names) {
    // There will be only one class.
    $class_node = reset($this->parser_nodes['classes']);

    $class_node_interfaces = [];
    foreach ($class_node->implements as $implements) {
      Assert::assertCount(1, $implements->parts);
      $class_node_interfaces[] = $implements->parts[0];
    }

    foreach ($not_expected_interface_names as $interface_full_name) {
      $interface_parts = explode('\\', $interface_full_name);

      // Add the array, as PHPUnit doesn't output the searched array when
      // assertContains() fails.
      $message = "\n" . print_r($class_node_interfaces, TRUE);

      Assert::assertNotContains(end($interface_parts), $class_node_interfaces, $message);
    }
  }

  /**
   * Assert the parsed class has the given public property.
   *
   * @param string $property_name
   *   The name of the property, without the initial '$'.
   * @param string $typehint
   *   The typehint for the property, without the initial '\' if a class or
   *   interface.
   * @param mixed $default
   *   (optional) The expected default value of the property, as a PHP value.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertClassHasPublicProperty($property_name, $typehint, $default = NULL, $message = NULL) {
    $message = $message ?? "The class defines the public property \${$property_name}";

    $this->assertClassHasProperty($property_name, $typehint, $default, $message);

    $property_node = $this->parser_nodes['properties'][$property_name];
    Assert::assertTrue($property_node->isPublic(), $message);
  }

  /**
   * Assert the parsed class has the given protected property.
   *
   * @param string $property_name
   *   The name of the property, without the initial '$'.
   * @param string $typehint
   *   The typehint for the property, without the initial '\' if a class or
   *   interface.
   * @param mixed $default
   *   (optional) The expected default value of the property, as a PHP value.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertClassHasProtectedProperty($property_name, $typehint, $default = NULL, $message = NULL) {
    $message = $message ?? "The class defines the protected property \${$property_name}";

    $this->assertClassHasProperty($property_name, $typehint, $default, $message);

    $property_node = $this->parser_nodes['properties'][$property_name];
    Assert::assertTrue($property_node->isProtected(), $message);
  }

  /**
   * Assert the parsed class has the given property.
   *
   * @param string $property_name
   *   The name of the property, without the initial '$'.
   * @param string $typehint
   *   The typehint for the property, without the initial '\' if a class or
   *   interface.
   * @param mixed $default
   *   (optional) The expected default value of the property, as a PHP value.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertClassHasProperty($property_name, $typehint, $default, $message = NULL) {
    $message = $message ?? "The class defines the property \${$property_name}";

    Assert::assertArrayHasKey($property_name, $this->parser_nodes['properties'], $message);

    $property_node = $this->parser_nodes['properties'][$property_name];

    // TODO: this doesn't allow for an actual default value of NULL.
    if (!is_null($default)) {
      // Use PHP Parser's PrettyPrinter to output the code of the default value
      // and then compare it, as this is far simpler than trying to compare
      // the whole of the property's default value parser node.
      $pretty_printer = new \PhpParser\PrettyPrinter\Standard;
      $property_default_php = $pretty_printer->prettyPrintFile([$property_node->props[0]->default]);

      // The first two lines will be a PHP open tag and a blank line: ditch.
      $property_default_php_lines = explode("\n", $property_default_php);
      $property_default_php = end($property_default_php_lines);

      // Prepend a return to the value so eval() returns it.
      $property_default_php = 'return ' . $property_default_php;

      // Get the actual value.
      $property_default_value = eval($property_default_php);

      Assert::assertEquals($default, $property_default_value, "The default value for the {$property_name} property is as expected.");
    }

    $property_docblock = $property_node->getAttribute('comments')[0]->getText();

    if (ucfirst($typehint) == $typehint) {
      // The typehint is a class, e.g. 'Drupal\foo', or 'Exception'.
      Assert::assertContains("@var \\{$typehint}", $property_docblock, "The docblock for property \${$property_name} contains the typehint.");
    }
    else {
      // The typehint is a primitive, e.g. 'string'.
      Assert::assertContains("@var {$typehint}", $property_docblock, "The docblock for property \${$property_name} contains the typehint.");
    }
  }

  /**
   * Assert the parsed class injects the given services.
   *
   * @param array $injected_services
   *   Array of the injected services.
   * @param string $message
   *   The assertion message.
   */
  public function assertInjectedServices($injected_services, $message = NULL) {
    $service_count = count($injected_services);

    // Assert the constructor method.
    $this->assertHasMethod('__construct');
    $construct_node = $this->parser_nodes['methods']['__construct'];

    // Constructor parameters and container extraction much match the same
    // order.
    // Slice the construct method params to the count of services.
    $construct_service_params = array_slice($construct_node->params, - $service_count);
    // Check that the constructor has parameters for all the services, after
    // any basic parameters.
    foreach ($construct_service_params as $index => $param) {
      Assert::assertEquals($injected_services[$index]['parameter_name'], $param->name);
    }

    // TODO: should check that __construct() calls its parent, though this is
    // not always the case!

    // Check the class property assignments in the constructor.
    $assign_index = 0;
    foreach ($construct_node->stmts as $stmt_node) {
      if (get_class($stmt_node) == \PhpParser\Node\Expr\Assign::class) {
        Assert::assertEquals($injected_services[$assign_index]['property_name'], $stmt_node->var->name);
        Assert::assertEquals($injected_services[$assign_index]['parameter_name'], $stmt_node->expr->name);

        $assign_index++;
      }
    }

    // For each service, assert the property.
    foreach ($injected_services as $injected_service_details) {
      $this->assertClassHasProtectedProperty($injected_service_details['property_name'], $injected_service_details['typehint']);
    }
  }

  /**
   * Assert the parsed class injects the given services using a static factory.
   *
   * @param array $injected_services
   *   Array of the injected services.
   * @param string $message
   *   The assertion message.
   */
  public function assertInjectedServicesWithFactory($injected_services, $message = NULL) {
    $service_count = count($injected_services);

    // Assert the create() factory method.
    $this->assertHasMethod('create');
    $create_node = $this->parser_nodes['methods']['create'];
    Assert::assertTrue($create_node->isStatic(), "The create() method is static.");

    // This should have a single return statement.
    Assert::assertCount(1, $create_node->stmts);
    $return_statement = $create_node->stmts[0];
    Assert::assertEquals(\PhpParser\Node\Stmt\Return_::class, get_class($return_statement), "The create() method's statement is a return.");
    $return_args = $return_statement->expr->args;

    // Slice the construct call arguments to the count of services.
    $construct_service_args = array_slice($return_args, - $service_count);

    // After the basic arguments, each one should match a service.
    foreach ($construct_service_args as $index => $arg) {
      // The argument is a method call.
      Assert::assertInstanceOf(\PhpParser\Node\Expr\MethodCall::class, $arg->value,
        "The create() method's new call's parameter {$index} is a method call.");
      $method_call_node = $arg->value;

      // Typically, container extraction is a single method call, e.g.
      //   $container->get('foo')
      // but sometimes the call gets something out of the service, e.g.
      //   $container->get('logger.factory')->get('image')
      // PHP Parser sees the final method call first, and the first part as
      // being the var (the thing that is called on). In other words, it parses
      // this right-to-left, unlike humans who (well I do!) parse it
      // left-to-right.
      // So recurse into the method call's var until we get a name.
      $var_node = $method_call_node->var;
      while (get_class($var_node) != \PhpParser\Node\Expr\Variable::class) {
        $var_node = $var_node->var;
      }

      // The argument is a container extraction.
      Assert::assertEquals('container', $var_node->name,
        "The create() method's new call's parameter {$index} is a method call on the \$container variable.");
      Assert::assertEquals('get', $method_call_node->name,
        "The create() method's new call's parameter {$index} is a method call to get().");
      Assert::assertCount(1, $arg->value->args);
      Assert::assertEquals($injected_services[$index]['service_name'], $arg->value->args[0]->value->value, "The call to the container extracts the expected service.");
    }

    // Assert the constructor and the class properties.
    $this->assertInjectedServices($injected_services, $message);
  }

  /**
   * Asserts that the class construction has the given base parameters.
   *
   * @param $parameters
   *   An array of parameters, in the same format as for
   *   assertMethodHasParameters().
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertConstructorBaseParameters($parameters, $message = NULL) {
    $message = $message ?? "The constructor method has the expected base parameters.";

    $expected_parameter_names = array_keys($parameters);

    $this->assertHasMethod('__construct');
    $this->assertHasMethod('create');

    // Check that the __construct() method has the base parameters before the
    // services.
    $this->assertHelperMethodHasParametersSlice($parameters, '__construct', $message, 0, count($parameters));

    // The first statement in the __construct() should be parent call, with
    // the base parameters.
    $parent_call_node = $this->parser_nodes['methods']['__construct']->stmts[0];
    Assert::assertInstanceOf(\PhpParser\Node\Expr\StaticCall::class, $parent_call_node);
    Assert::assertEquals('parent', $parent_call_node->class->parts[0]);
    Assert::assertEquals('__construct', $parent_call_node->name);
    $call_arg_names = [];
    foreach ($parent_call_node->args as $arg) {
      $call_arg_names[] = $arg->value->name;
    }
    Assert::assertEquals(array_keys($parameters), $call_arg_names, "The call to the parent constructor has the base parameters.");

    // The only statement in the create() method should return the new object.
    $create_node = $this->parser_nodes['methods']['create'];

    $object_create_node = $this->parser_nodes['methods']['create']->stmts[0];
    // Slice the construct call arguments to the given parameters.
    $construct_base_args = array_slice($create_node->stmts[0]->expr->args, 0, count($parameters));
    $create_arg_names = [];

    foreach ($construct_base_args as $index => $arg) {
      // Recurse into the arg until we get a name, to account for args which
      // are a method call on the container.
      $arg_value_node = $arg->value;

      if (get_class($arg_value_node) == \PhpParser\Node\Expr\Variable::class) {
        // Plain variable.
        Assert::assertEquals($expected_parameter_names[$index], $arg_value_node->name,
          "The create() method's return statement's argument {$index} is the variable \${$arg_value_node->name}.");
      }
      else {
        //dump($arg_value_node);
        // TODO! check a constainer extraction.
      }
    }
  }

  /**
   * Gets a method tester for a class method in the parsed code.
   *
   * @param string $method_name
   *   The method name to check for.
   *
   * @return \DrupalCodeBuilder\Test\Unit\Parsing\PHPMethodTester
   */
  public function getMethodTester($method_name) {
    $this->assertHasMethod($method_name);

    return new PHPMethodTester($this->parser_nodes['methods'][$method_name], $this, $this->phpCode);
  }

  /**
   * Gets a specialized method tester for the baseFieldDefinitions() method.
   *
   * @return DrupalCodeBuilder\Test\Unit\Parsing\BaseFieldDefinitionsTester
   *   The tester object.
   */
  public function getBaseFieldDefinitionsTester() {
    $this->assertHasMethod('baseFieldDefinitions');

    return new BaseFieldDefinitionsTester($this->parser_nodes['methods']['baseFieldDefinitions'], $this, $this->phpCode);
  }

  /**
   * Assert the parsed code contains the given function.
   *
   * @param string $function_name
   *   The function name to check for.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasFunction($function_name, $message = NULL) {
    $message = $message ?? "The file contains the function {$function_name}.";

    Assert::assertArrayHasKey($function_name, $this->parser_nodes['functions'], $message);
  }

  /**
   * Assert the parsed code contains the given method.
   *
   * @param string $method_name
   *   The method name to check for.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasMethod($method_name, $message = NULL) {
    $message = $message ?? "The file contains the method {$method_name}.";

    Assert::assertArrayHasKey($method_name, $this->parser_nodes['methods'], $message);
  }

  /**
   * Asserts a subset of the parameters of a method of the parsed class.
   *
   * Helper for other assertions.
   *
   * TODO: Move assertions that use this to PHPMethodTester and remove this.
   *
   * @param $parameters
   *   An array of parameters: keys are the parameter names, values are the
   *   typehint, with NULL for no typehint.
   * @param string $method_name
   *   The method name.
   * @param integer $offset
   *   (optional) The array slice offset in the actual parameters to compare
   *   with.
   * @param integer $length
   *   (optional) The array slice length in the actual parameters to compare
   *   with. If omitted, all the actual parameters from the offset are
   *   considered. This means that omitting both values will compare the given
   *   parameters with all of the method's parameters for an exact match.
   * @param string $message
   *   (optional) The assertion message.
   */
  private function assertHelperMethodHasParametersSlice($parameters, $method_name, $message = NULL, $offset = 0, $length = NULL) {
    $expected_parameter_names = array_keys($parameters);
    $expected_parameter_typehints = array_values($parameters);

    $parameter_names_string = implode(", ", $expected_parameter_names);
    $message = $message ?? "The method {$method_name} has the parameters {$parameter_names_string} in positions ... TODO.";

    //dump($this->parser_nodes['methods'][$method_name]);

    // Get the actual parameter names.
    $param_nodes = $this->parser_nodes['methods'][$method_name]->params;
    if (empty($length)) {
      $param_nodes_slice = array_slice($param_nodes, $offset);
    }
    else {
      $param_nodes_slice = array_slice($param_nodes, $offset, $length);
    }

    // Sanity check.
    Assert::assertEquals(count($parameters), count($param_nodes_slice), "The length of the expected parameters list for {$method_name} matches the found ones.");

    $actual_parameter_names_slice = [];
    $actual_parameter_types_slice = [];
    foreach ($param_nodes_slice as $index => $param_node) {
      $actual_parameter_names_slice[] = $param_node->name;

      if (is_null($param_node->type)) {
        $actual_parameter_types_slice[] = NULL;
      }
      elseif (is_string($param_node->type)) {
        $actual_parameter_types_slice[] = $param_node->type;
      }
      else {
        // PHP CodeSniffer will have already caught a non-imported class, so
        // safe to assume there is only one part to the class name.
        $actual_parameter_types_slice[] = $param_node->type->parts[0];

        $expected_typehint_parts = explode('\\', $expected_parameter_typehints[$index]);

        if (count($expected_typehint_parts) == 1) {
          // It's a class in the global namespace, e.g. '\Traversable'. This
          // will have the '\' with it and not be imported. PHP Parser doesn't
          // keep the initial '\' here. Rather, the param node will be a
          // PhpParser\Node\Name\FullyQualified rather than a
          // PhpParser\Node\Name.
          Assert::assertInstanceOf(\PhpParser\Node\Name\FullyQualified::class, $param_node->type,
            "The typehint for the parameter \${$param_node->name} is a fully-qualified class name.");

          $expected_parameter_typehints[$index] = $expected_parameter_typehints[$index];
        }
        else {
          // It's a namespaced class.
          // Check the full expected typehint is imported.
          $this->assertImportsClassLike($expected_typehint_parts, "The typehint for the {$index} parameter is imported.");

          // Replace the fully-qualified name with the short name in the
          // expectations array for comparison.
          $expected_parameter_typehints[$index] = end($expected_typehint_parts);
        }
      }
    }

    Assert::assertEquals($expected_parameter_names, $actual_parameter_names_slice, $message);

    Assert::assertEquals($expected_parameter_typehints, $actual_parameter_types_slice, $message);
  }

  /**
   * Assert the parsed code contains the given methods.
   *
   * @param string[] $method_name
   *   An array of method names to check for.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasMethods($method_names, $message = NULL) {
    $method_names_string = implode(", ", $method_names);
    $message = $message ?? "The file contains the methods {$method_names_string}.";

    // Can't use assertArraySubset() on numeric arrays: see
    // https://github.com/sebastianbergmann/phpunit/issues/2069
    foreach ($method_names as $method_name) {
      $this->assertHasMethod($method_name, $message);
    }
  }

  /**
   * Assert the parsed code does not contain the given function.
   *
   * @param string $function_name
   *   The function name to check for.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasNotFunction($function_name, $message = NULL) {
    $message = $message ?? "The file does not contain the function {$function_name}.";

    Assert::assertArrayNotHasKey($function_name, $this->parser_nodes['functions'], $message);
  }

  /**
   * Assert the parsed code contains no methods.
   *
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasNoMethods($message = NULL) {
    $message = $message ?? "The file contains no methods.";
    Assert::assertArrayNotHasKey('methods', $this->parser_nodes, $message);
  }

  /**
   * Assert the parsed code implements the given hook.
   *
   * Also checks the hook implementation docblock has the correct text.
   *
   * @param string $hook_name
   *   The full name of the hook to check for, e.g. 'hook_help'.
   * @param $module_name
   *  The name of the implementing module.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasHookImplementation($hook_name, $module_name, $message = NULL) {
    $message = $message ?? "The code has a function that implements the hook $hook_name for module $module_name.";

    $hook_short_name = substr($hook_name, 5);
    $function_name = $module_name . '_' . $hook_short_name;

    $this->assertHasFunction($function_name, $message);

    $function_node = $this->parser_nodes['functions'][$function_name];
    $comments = $function_node->getAttribute('comments');

    // Workaround for issue with PHP Parser: if the function is the first in the
    // file, and there are no import statements, then the @file docblock will
    // be treated as one of the function's comments. Therefore, we need to take
    // the last comment in the array to be sure of having the actual function
    // docblock.
    // @see https://github.com/nikic/PHP-Parser/issues/445
    $function_docblock = end($comments);
    $docblock_text = $function_docblock->getReformattedText();

    // TODO: this will need to switch on major version when we use this to test
    // D7 hooks.
    $expected_line = "Implements {$hook_name}().";

    $this->assertDocblockHasLine($expected_line, $docblock_text, "The module file contains the docblock for hook_menu().");
  }

  /**
   * Assert the parsed code does not implement the given hook.
   *
   * @param string $hook_name
   *   The full name of the hook to check for, e.g. 'hook_help'.
   * @param $module_name
   *  The name of the implementing module.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasNotHookImplementation($hook_name, $module_name, $message = NULL) {
    $message = $message ?? "The code does not have a function that implements the hook $hook_name for module $module_name.";

    $hook_short_name = substr($hook_name, 5);
    $function_name = $module_name . '_' . $hook_short_name;

    $this->assertHasNotFunction($function_name, $message);
  }

  /**
   * Assert the given docblock contains a line.
   *
   * @param string $line
   *   The expected line.
   * @param $docblock
   *   The docblock text.
   * @param string $message
   *   (optional) The assertion message.
   */
  protected function assertDocblockHasLine($line, $docblock, $message = NULL) {
    $message = $message ?? "The method docblock contains the line {$line}.";

    $docblock_lines = explode("\n", $docblock);

    // Slice off first and last lines, which are the '/**' and '*/'.
    $docblock_lines = array_slice($docblock_lines, 1, -1);
    $docblock_lines = array_map(function($line) {
      return preg_replace('/^ \* /', '', $line);
    }, $docblock_lines);

    // Work around assertContains() not outputting the array on failure by
    // putting it in the message.
    Assert::assertContains($line, $docblock_lines, "The line '{$line}' was found in the docblock lines: " . print_r($docblock_lines, TRUE));
  }

  /**
   * Asserts that a statement in a method is a call to the parent.
   *
   * @param string $method_name
   *   The method name.
   * @param int $statement_index
   *   The index of the statement in the array of statements for the method,
   *   starting at 0.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertStatementIsParentCall($method_name, $statement_index, $message = NULL) {
    $message = $message ?? "The {$method_name} method's statement index {$statement_index} is a parent call.";

    $statement_node = $this->parser_nodes['methods'][$method_name]->stmts[$statement_index];
    Assert::assertInstanceOf(\PhpParser\Node\Expr\StaticCall::class, $statement_node, $message);
    Assert::assertCount(1, $statement_node->class->parts);
    Assert::assertEquals('parent', $statement_node->class->parts[0]);
  }

  /**
   * Asserts that a statement in a method is a call to the given method.
   *
   * I.e., the ($statement_index)th statement in $method_name is a call to
   * $this->$called_method_name().
   *
   * @param string $called_method_name
   *   The expected method that is called.
   * @param string $method_name
   *   The name of the method which the statement is in.
   * @param int $statement_index
   *   The index of the statement in the array of statements for the method,
   *   starting at 0.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertStatementIsLocalMethodCall($called_method_name, $method_name, $statement_index, $message = NULL) {
    $message = $message ?? "The {$method_name} method's statement index {$statement_index} is a method call to {$called_method_name}.";

    $statement_node = $this->parser_nodes['methods'][$method_name]->stmts[$statement_index];
    Assert::assertInstanceOf(\PhpParser\Node\Expr\MethodCall::class, $statement_node, $message);
    Assert::assertInstanceOf(\PhpParser\Node\Expr\Variable::class, $statement_node->var, $message);
    Assert::assertEquals('this', $statement_node->var->name);
    Assert::assertEquals($called_method_name, $statement_node->name, $message);
  }

  /**
   * Asserts that a statement which is a call has the given arguments.
   *
   * @param array $expected_args
   *   An array whose keys are the values of each argument and whose values
   *   indicate the type.
   * @param string $method_name
   *   The name of the method which the statement is in.
   * @param int $statement_index
   *   The index of the statement in the array of statements for the method,
   *   starting at 0.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertCallHasArgs($expected_args, $method_name, $statement_index, $message = NULL) {
    $statement_node = $this->parser_nodes['methods'][$method_name]->stmts[$statement_index];

    Assert::assertEquals(count($expected_args), count($statement_node->args), "The call has the expected number of arguments.");

    $index = 0;
    foreach ($expected_args as $expected_arg_name => $expected_arg_type) {
      Assert::assertArrayHasKey($index, $statement_node->args, "The statement has an argument at index {$index}.");

      $actual_arg = $statement_node->args[$index];

      switch ($expected_arg_type) {
        case 'variable':
          Assert::assertInstanceOf(\PhpParser\Node\Expr\Variable::class, $actual_arg->value);
          Assert::assertEquals($expected_arg_name, $actual_arg->value->value);
          break;

        case 'string':
          Assert::assertInstanceOf(\PhpParser\Node\Scalar\String_::class, $actual_arg->value);
          Assert::assertEquals($expected_arg_name, $actual_arg->value->value);
          break;

        case 'class':
          Assert::assertInstanceOf(\PhpParser\Node\Expr\ClassConstFetch::class, $actual_arg->value);
          Assert::assertEquals('class', $actual_arg->value->name);

          $class_name_parts = explode('\\', $expected_arg_name);
          Assert::assertEquals(end($class_name_parts), $actual_arg->value->class->parts[0]);
          $this->assertImportsClassLike($class_name_parts);
          break;

        // TODO: other types.
      }

      $index++;
    }
  }

}
