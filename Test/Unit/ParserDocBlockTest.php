<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\DocBlockTester;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Unit tests for the DocBlockTester test helper.
 *
 * @group php_tester_docblocks
 */
class ParserDocBlockTest extends TestCase {

  /**
   * Tests the assertHasLine() assertion with a class docblock.
   */
  public function testAssertHasLineAssertionClass() {
    $php = <<<EOT
      <?php

      /**
       * Class docblock.
       *
       * Further line.
       * Partial line.
       */
      class Foo {

      }

      EOT;

    $docblock_tester = (new PHPTester(8, $php))->getClassDocBlockTester();

    $this->assertAssertion(TRUE, $docblock_tester, 'assertHasLine', 'Class docblock.');
    $this->assertAssertion(TRUE, $docblock_tester, 'assertHasLine', 'Further line.');
    $this->assertAssertion(TRUE, $docblock_tester, 'assertHasLine', 'Further line.');
    $this->assertAssertion(FALSE, $docblock_tester, 'assertHasLine', 'not this line');
    $this->assertAssertion(FALSE, $docblock_tester, 'assertHasLine', 'Partial');
  }

  /**
   * Helper for tests that test custom assertions.
   *
   * @param bool $pass
   *   Whether the assertion should pass with the given parameters: TRUE if it
   *   should pass, FALSE if it should fail.
   * @param object $php_tester
   *   The PHP tester, on which to call the assertion method.
   * @param string $assertion_name
   *   The name of the assertion method. It is expected to be on the given
   *   object.
   * @param mixed ...$assertion_parameters
   *   Remaining parameters are passed to the assertion method.
   */
  protected function assertAssertion($pass, DocBlockTester $docblock_tester, $assertion_name, ...$assertion_parameters) {
    $message_parameters = print_r($assertion_parameters, TRUE);

    try {
      $docblock_tester->$assertion_name(...$assertion_parameters);
    }
    catch (ExpectationFailedException|AssertionFailedError $e) {
      // We get here if the assertion failed.
      if ($pass) {
        $failure_message = $e->getMessage();
        $this->fail("The assertion {$assertion_name}() should pass with the following parameters:\n{$message_parameters}. Got failure:\n{$failure_message}.");
      }

      return;
    }

    // We get here if the assertion passed.
    if (!$pass) {
      $this->fail("The assertion {$assertion_name}() should fail with the following parameters: {$message_parameters}");
    }

    // This is just to stop PHPUnit complaining that the test does not perform
    // any assertions.
    $this->assertTrue(TRUE);
  }

}
