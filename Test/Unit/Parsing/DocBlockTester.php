<?php

namespace DrupalCodeBuilder\Test\Unit\Parsing;

use DrupalCodeBuilder\Test\Constraint\TraversableContainsMatchesRegularExpression;
use PhpParser\Comment\Doc;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Constraint\LogicalNot;

/**
 * Helper class for making assertions on a docblock.
 */
class DocBlockTester {

  /**
   * An array of the docblock lines, without the docblock markers.
   *
   * @var array|null
   */
  protected ?array $docblockLines;

  /**
   * Constructor.
   *
   * @param \PhpParser\Comment\Doc $docblockNode
   *   The docblock node from the function, method, or class.
   * @param string $hostElementName
   *   The name of the function, method, or class this docblock is on.
   */
  public function __construct(
    protected Doc $docblockNode,
    protected string $hostElementName,
  ) {
  }

  /**
   * Assert the method docblock has an 'inheritdoc' tag.
   *
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasInheritdoc($message = NULL) {
    $message = $message ?? "The method {$this->hostElementName} has an 'inheritdoc' docblock.";

    $this->assertHasLine('{@inheritdoc}', $message);
    // TODO: assert line count -- no other lines.
  }

  /**
   * Asserts the documented return type.
   *
   * @param string $type
   *   The expected return type in the docblock.
   *
   * @param string|null $message
   *   (optional) The assertion message.
   */
  public function assertReturnType(string $type, ?string $message = NULL) {
    $this->assertHasLine('@return ' . $type, $message);
  }

  /**
   * Asserts there is no documented return type.
   *
   * @param string|null $message
   *   (optional) The assertion message.
   */
  public function assertNoReturnType(?string $message = NULL) {
    $this->assertNotHasLineMatchingRegularExpression('/^@return/', $message);
  }

  /**
   * Assert the docblock contains a line.
   *
   * @param string $line
   *   The expected line.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasLine(string $line, $message = NULL) {
    $this->assertLineHelper($line, TRUE, $message);
  }

  /**
   * Assert the docblock does not contain a line.
   *
   * @param string $line
   *   The expected line.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertNotHasLine(string $line, $message = NULL) {
    $this->assertLineHelper($line, FALSE, $message);
  }

  /**
   * Assert the docblock contains a line matching a regular expression.
   *
   * @param string $pattern
   *   The expected regular expression.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasLineMatchingRegularExpression(string $pattern, $message = NULL) {
    $message ??= "The docblock has a line matching pattern '$pattern'.";

    $this->ensureDocBlockLines();

    $constraint = new TraversableContainsMatchesRegularExpression($pattern);

    Assert::assertThat($this->docblockLines, $constraint, $message);
  }

  /**
   * Assert the docblock does not containt a line matching a regular expression.
   *
   * @param string $pattern
   *   The regular expression that is not expected.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertNotHasLineMatchingRegularExpression(string $pattern, $message = NULL) {
    $message ??= "The docblock does not have a line matching pattern '$pattern'.";

    $this->ensureDocBlockLines();

    Assert::assertThat(
      $this->docblockLines,
      new LogicalNot(
          new TraversableContainsMatchesRegularExpression($pattern),
      ),
      $message,
    );
  }

  /**
   * Helper for asserting a line.
   *
   * @param string $line
   *   The expected line.
   * @param bool $assert
   *   Whether to assert the line is in the docblock, or assert it is not.
   * @param string $message
   *   (optional) The assertion message.
   */
  protected function assertLineHelper(string $line, bool $assert, $message = NULL) {
    $message = $message ?? (
      $assert ?
      "The docblock contains the line '{$line}'." :
      "The docblock does not the line '{$line}'."
    );

    $this->ensureDocBlockLines();

    // Work around assertContains() not outputting the array on failure by
    // putting it in the message.
    // TODO: still needed with assertContains()?
    $message .= " Given docblock was: " . print_r($this->docblockLines, TRUE);

    if ($assert) {
      Assert::assertContains($line, $this->docblockLines, $message);
    }
    else {
      Assert::assertNotContains($line, $this->docblockLines, $message);
    }
  }

  /**
   * Splits the docblock into an array and strips docblock formatting.
   *
   * This needs to be called before any assertion.
   */
  protected function ensureDocBlockLines() {
    if (!isset($this->docblockLines)) {
      $docblock_text = $this->docblockNode->getReformattedText();
      $docblock_lines = explode("\n", $docblock_text);

      // Slice off first and last lines, which are the '/**' and '*/'.
      $docblock_lines = array_slice($docblock_lines, 1, -1);
      // Trim off the docblock formatting.
      array_walk($docblock_lines, function(&$line) {
        $line = preg_replace('/^ \* ?/', '', $line);
      });

      $this->docblockLines = $docblock_lines;
    }
  }

}
