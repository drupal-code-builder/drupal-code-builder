<?php

namespace DrupalCodeBuilder\Test\Unit;

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
 * Base class for unit tests that generate code and test the result.
 *
 * @deprecated Use TestBase with PHPTester instead.
 */
abstract class TestBaseComponentGeneration extends TestBase {

  /**
   * The PHP_CodeSniffer instance set up in setUpBeforeClass().
   *
   * @var \PHP_CodeSniffer
   */
  static protected $phpcs;

  /**
   * The PHP CodeSniffer to exclude for this test.
   *
   * @var string[]
   */
  static protected $phpcsExcludedSniffs = [];

  /**
   * Sets up PHPCS.
   */
  public static function setUpBeforeClass() {
    // TODO: move this to setUp().
    // Set runtime config.
    PHP_CodeSniffer::setConfigData(
      'installed_paths',
      __DIR__ . '/../../vendor/drupal/coder/coder_sniffer',
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
      // Exclude standards defined in the test class.
      static::$phpcsExcludedSniffs
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

    static::$phpcs = $phpcs;
  }

  /**
   * Assert a string is correctly-formed PHP.
   *
   * @param $string
   *  The text of PHP to check. This is expected to begin with a '<?php' tag.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertWellFormedPHP($code, $message = NULL) {
    if (!isset($message)) {
      $message = "String evaluates as correct PHP.";
    }

    // Escape all the backslashes. This is to prevent any escaped character
    // sequences from being formed by namespaces and long classes, e.g.
    // 'namespace Foo\testmodule;' will treat the '\t' as a tab character.
    // TODO: find a better way to do this that doesn't involve changing the
    // code.
    $escaped_code = str_replace('\\', '\\\\', $code);

    // Pass the code to PHP for linting.
    $output = NULL;
    $exit = NULL;
    $result = exec(sprintf('echo %s | php -l', escapeshellarg($escaped_code)), $output, $exit);

    if (!empty($exit)) {
      // Dump the code lines as an array so we get the line numbers.
      $code_lines = explode("\n", $code);
      // Re-key it so the line numbers start at 1.
      $code_lines = array_combine(range(1, count($code_lines)), $code_lines);
      dump($code_lines);

      $this->fail("Error parsing the code resulted in: \n" . implode("\n", $output));
    }
  }

  /**
   * Assert that code adheres to Drupal Coding Standards.
   *
   * This runs PHP Code Sniffer using the Drupal Coder module's standards.
   *
   * @param $string
   *  The text of PHP to check. This is expected to begin with a '<?php' tag.
   */
  function assertDrupalCodingStandards($code) {
    // Process the file with PHPCS.
    // We need to pass in a value for the filename, even though the file does
    // not exist, as the Drupal standard uses it to try to check the file when
    // it tries to find an associated module .info file to detect the Drupal
    // major version in DrupalPractice_Project::getCoreVersion(). We don't use
    // the DrupalPractice standard, so that shouldn't concern us, but the
    // Drupal_Sniffs_Array_DisallowLongArraySyntaxSniff sniff calls that to
    // determine whether to run itself. This check for the Drupal code version
    // will fail, which means that the short array sniff will not be run.
    $phpcsFile = static::$phpcs->processFile('fictious file name', $code);

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
    $code_lines = explode("\n", $code);
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

    $this->fail("PHPCS failed with $error_count errors and $warning_count warnings.");
  }

  /**
   * Parses a code file string and sets various parser nodes on this test.
   *
   * This populates $this->parser_nodes with groups parser nodes, after
   * resetting it from any previous call to this method.
   *
   * @param string $code
   *   The code file to parse.
   */
  protected function parseCode($code) {
    $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    try {
      $ast = $parser->parse($code);
    }
    catch (Error $error) {
      $this->fail("Parse error: {$error->getMessage()}");
    }

    //dump($ast);

    // Reset our array of parser nodes.
    // This then passed into the anonymous visitor class, and populated with the
    // nodes we are interested in for subsequent assertions.
    $this->parser_nodes = [];

    // Group the parser nodes by type, so subsequent assertions can easily
    // find them.
    $visitor = new class($this->parser_nodes) extends NodeVisitorAbstract {

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
    $traverser->addVisitor($visitor);

    $ast = $traverser->traverse($ast);
  }

  /**
   * Asserts the parsed code is entirely procedural.
   *
   * @param string $message
   *   (optional) The assertion message.
   */
  protected function assertIsProcedural($message = NULL) {
    $message = $message ?? "The file contains only procedural code.";

    $this->assertArrayNotHasKey('classes', $this->parser_nodes, $message);
    $this->assertArrayNotHasKey('interfaces', $this->parser_nodes, $message);
    // Technically we should cover traits too, but we don't generate any of
    // those.
  }

}
