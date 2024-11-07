<?php

namespace DrupalCodeBuilder\Test\Unit\Parsing;

use PhpParser\Comment\Doc;
use PHPUnit\Framework\Assert;

/**
 * Helper class for making assertions on a docblock.
 */
class DocBlockTester {

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

    $docblock_text = $this->docblockNode->getReformattedText();
    $docblock_lines = explode("\n", $docblock_text);

    // Slice off first and last lines, which are the '/**' and '*/'.
    $docblock_lines = array_slice($docblock_lines, 1, -1);
    // Trim off the docblock formatting.
    array_walk($docblock_lines, function(&$line) {
      $line = preg_replace('/^ \* /', '', $line);
    });

    // Work around assertContains() not outputting the array on failure by
    // putting it in the message.
    // TODO: still needed with assertContains()?
    $message .= " Given docblock was: " . print_r($docblock_lines, TRUE);

    if ($assert) {
      Assert::assertContains($line, $docblock_lines, $message);
    }
    else {
      Assert::assertNotContains($line, $docblock_lines, $message);
    }
  }

}
