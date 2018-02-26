<?php

namespace DrupalCodeBuilder\Test\Unit\Parsing;

use PHPUnit\Framework\Assert;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Scalar\String_;

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
   * Construct a new AnnotationTester.
   *
   * @param \PhpParser\Node\Stmt\ClassMethod $method_node
   *   The PhpParser method node.
   */
  public function __construct(ClassMethod $method_node) {
    $this->methodNode = $method_node;
    $this->methodName = $method_node->name;
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

}
