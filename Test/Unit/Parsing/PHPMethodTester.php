<?php

namespace DrupalCodeBuilder\Test\Unit\Parsing;

use PHPUnit\Framework\Assert;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\Variable;

/**
 * Helper class for testing a PHP method.
 *
 * Get this from the PHPTester with:
 * @code
 * $method_tester = $php_tester->getMethodTester($method_name);
 * @endcode
 *
 * TODO: move other method-related assertions from PHPTester to here.
 */
class PHPMethodTester {

  /**
   * The method node.
   *
   * @var \PhpParser\Node\Stmt\ClassMethod
   */
  protected $methodNode;

  /**
   * The name of the method.
   *
   * @var string
   */
  protected $methodName;

  /**
   * The method code.
   *
   * @var string[]
   */
  protected $methodBody;

  /**
   * The PHP tester for the file this method is in.
   *
   * @var \DrupalCodeBuilder\Test\Unit\Parsing\PHPTester
   */
  protected $fileTester;

  /**
   * Construct a new PHPMethodTester.
   *
   * @param \PhpParser\Node\Stmt\ClassMethod $method_node
   *   The PhpParser method node.
   * @param \DrupalCodeBuilder\Test\Unit\Parsing\PHPTester file_tester
   *   The PHP tester for the file this method is in.
   * @param string $php_code
   *   The complete PHP code being tested.
   */
  public function __construct(ClassMethod $method_node, PHPTester $file_tester, $php_code) {
    $this->methodNode = $method_node;
    $this->methodName = $method_node->name;
    $this->fileTester = $file_tester;

    $php_code_lines = explode("\n", $php_code);

    $start_line = $method_node->getAttribute('startLine');
    $end_line = $method_node->getAttribute('endLine');

    // Don't include the final line as it's just the closing '}'.
    $length = $end_line - $start_line - 1;
    $this->methodBody = implode("\n", array_slice($php_code_lines, $start_line, $length));
  }

  /**
   * Gets a form builder tester for this method.
   *
   * TODO: move this to PHPTester.
   *
   * @return FormBuilderTester
   *   The form builder tester object.
   */
  public function getFormBuilderTester() {
    return new FormBuilderTester($this->methodNode, $this->fileTester);
  }

  /**
   * Assert the method docblock has an 'inheritdoc' tag.
   *
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertMethodDocblockHasInheritdoc($message = NULL) {
    $message = $message ?? "The method {$this->methodName} has an 'inheritdoc' docblock.";

    $this->assertMethodHasDocblockLine('{@inheritdoc}', $message);
  }

  /**
   * Assert the method docblock contains the line.
   *
   * @param string $line
   *   The expected line.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertMethodHasDocblockLine($line, $message = NULL) {
    $message = $message ?? "The method {$this->methodName} has the docblock line {$line}.";

    $comments = $this->methodNode->getAttribute('comments');
    $docblock = $comments[0]->getReformattedText();
    $docblock_lines = explode("\n", $docblock);

    // Slice off first and last lines, which are the '/**' and '*/'.
    $docblock_lines = array_slice($docblock_lines, 1, -1);
    $docblock_lines = array_map(function($line) {
      return preg_replace('/^ \* /', '', $line);
    }, $docblock_lines);

    Assert::assertContains($line, $docblock_lines);
  }

  /**
   * Asserts a method of the parsed class has the given parameters.
   *
   * @param $parameters
   *   An array of parameters: keys are the parameter names, values are the
   *   typehint, with NULL for no typehint.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasParameters($parameters, $message = NULL) {
    $expected_parameter_names = array_keys($parameters);

    $parameter_names_string = implode(", ", $expected_parameter_names);
    $message = $message ?? "The method {$this->methodName} has the parameters {$parameter_names_string}.";

    $this->assertHelperHasParametersSlice($parameters, $message);
  }

  /**
   * Asserts a method of the parsed class has no parameters.
   *
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasNoParameters($message = NULL) {
    $message = $message ?? "The method {$this->methodName} has no parameters.";

    $this->assertHelperHasParametersSlice([], $message);
  }

  /**
   * Asserts a subset of the parameters of a method of the parsed class.
   *
   * Helper for assertMethodHasParameters() and other assertions.
   *
   * @param $parameters
   *   An array of parameters: keys are the parameter names, values are the
   *   typehint, with NULL for no typehint.
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
  private function assertHelperHasParametersSlice($parameters, $message = NULL, $offset = 0, $length = NULL) {
    $expected_parameter_names = array_keys($parameters);
    $expected_parameter_typehints = array_values($parameters);

    $parameter_names_string = implode(", ", $expected_parameter_names);
    $message = $message ?? "The method {$this->methodName} has the parameters {$parameter_names_string} in positions ... TODO.";

    //dump($this->parser_nodes['methods'][$method_name]);

    // Get the actual parameter names.
    $param_nodes = $this->methodNode->params;
    if (empty($length)) {
      $param_nodes_slice = array_slice($param_nodes, $offset);
    }
    else {
      $param_nodes_slice = array_slice($param_nodes, $offset, $length);
    }

    // Sanity check.
    Assert::assertEquals(count($parameters), count($param_nodes_slice), "The length of the expected parameters list for {$this->methodName} matches the found ones.");

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
          $this->fileTester->assertImportsClassLike($expected_typehint_parts, "The typehint for the {$index} parameter is imported.");

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
   * Asserts the method returns the given string.
   *
   * This expects the final statement to be a return. Other return statements
   * in the method are not checked.
   *
   * @param string $string
   *   The expected return string.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertReturnsString($string, $message = NULL) {
    $message = $message ?? "The method {$this->methodName} returns the string {$string}.";

    // Find the return statement.
    $statements = $this->methodNode->getStmts();
    $final_statement = end($statements);
    if (get_class($final_statement) != Return_::class) {
      Assert::fail("Final statement in {$this->methodName} is not a return.");
    }

    if (get_class($final_statement->expr) != String_::class) {
      Assert::fail("Return statement in {$this->methodName} is not a string.");
    }

    Assert::assertEquals($string, $final_statement->expr->value);
  }

  /**
   * Asserts the method returns the given variable name.
   *
   * This expects the final statement to be a return. Other return statements
   * in the method are not checked.
   *
   * @param string $variable_name
   *   The expected return variable name, without the '$'.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertReturnsVariable($variable_name, $message = NULL) {
    $message = $message ?? "The method {$this->methodName} returns the variable \${$variable_name}.";

    // Find the return statement.
    $statements = $this->methodNode->getStmts();
    $final_statement = end($statements);
    if (get_class($final_statement) != Return_::class) {
      Assert::fail("Final statement in {$this->methodName} is not a return.");
    }

    if (get_class($final_statement->expr) != Variable::class) {
      Assert::fail("Return statement in {$this->methodName} is not a variable.");
    }

    Assert::assertEquals($variable_name, $final_statement->expr->name, $message);
  }

  /**
   * Asserts a statement is a method call to the parent which assigns a value.
   *
   * @param int $index
   *   The index of the statement in the method, starting at 0.
   * @param string $assigned_variable
   *   The name of the variable that should be assigned, without the '$'.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertStatementIsParentCallAssignment($index, $assigned_variable, $message = NULL) {
    $message = $message ?? "The method {$this->methodName} statement {$index} has a parent call that assigns the variable \${$assigned_variable}.";

    $statements = $this->methodNode->getStmts();
    $statement = $statements[$index];

    Assert::assertEquals(\PhpParser\Node\Expr\Assign::class, get_class($statement), $message);

    Assert::assertObjectHasAttribute('var', $statement, $message);
    Assert::assertObjectHasAttribute('name', $statement->var, $message);
    Assert::assertEquals($assigned_variable, $statement->var->name, "The variable $assigned_variable is assigned by the parent call.");

    Assert::assertObjectHasAttribute('expr', $statement, $message);
    Assert::assertEquals(\PhpParser\Node\Expr\StaticCall::class, get_class($statement->expr), $message);
    Assert::assertObjectHasAttribute('class', $statement->expr, $message);
    Assert::assertObjectHasAttribute('parts', $statement->expr->class, $message);
    Assert::assertEquals('parent', $statement->expr->class->parts[0], $message);
    Assert::assertEquals($this->methodName, $statement->expr->name, $message);

    // TODO: check the method parameter names match the method call arguments.
  }

  /**
   * Asserts that a statement has chained method calls.
   *
   * @param int $statement_index
   *   The index of the statement to check.
   * @param string[]
   *   An array of expected method names.
   */
  public function assertStatementHasChainedMethodCalls($statement_index, $expected_calls) {
    $statements = $this->methodNode->getStmts();
    $statement_node = $statements[$statement_index];

    $method_call = $statement_node->expr;
    while (isset($method_call->var)) {
      $method_calls[] = $method_call->name;

      $method_call = $method_call->var;
    }

    // Reverse the array, as we encounter them in reverse order.
    $method_calls = array_reverse($method_calls);

    Assert::assertEquals($expected_calls, $method_calls);
  }

  /**
   * Asserts the method has the given line of code.
   *
   * @param string $code_line
   *   The expected code line. May use regex, but must not include the regex
   *   delimiters.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasLine($code_line, $message = NULL) {
    $message = $message ?? "The method {$this->methodName} contains the line '{$code_line}'.";

    Assert::assertRegExp('@^\s*' . preg_quote($code_line) . '@m', $this->methodBody, $message . ' ' . print_r($this->methodBody, TRUE));
  }

}
