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
   * Construct a new AnnotationTester.
   *
   * @param \PhpParser\Node\Stmt\ClassMethod $method_node
   *   The PhpParser method node.
   * @param string $php_code
   *   The complete PHP code being tested.
   */
  public function __construct(ClassMethod $method_node, $php_code) {
    $this->methodNode = $method_node;
    $this->methodName = $method_node->name;

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
    return new FormBuilderTester($this->methodNode);
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
