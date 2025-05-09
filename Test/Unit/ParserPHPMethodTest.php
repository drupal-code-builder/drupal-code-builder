<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Unit tests for the PHPMethodTester test helper.
 */
class ParserPHPMethodTest extends TestCase {

  public function testAssertHasAttribute(): void {
    $php = <<<EOT
      <?php

      namespace Some\Space\Namespaced;

      use Some\Other\Space\AttributeClass;

      class Foo {

        #[AttributeClass('cake')]
        public function myMethod() {}

      }

      EOT;

    $php_tester = new PHPTester(11, $php);
    $method_tester = $php_tester->getMethodTester('myMethod');

    // Specifying no expected parameters omit checking parameters entirely.
    $this->assertAssertion(TRUE, $method_tester, 'assertHasAttribute', '\Some\Other\Space\AttributeClass');
    $this->assertAssertion(TRUE, $method_tester, 'assertHasAttribute', '\Some\Other\Space\AttributeClass', ['cake']);

    $this->assertAssertion(FALSE, $method_tester, 'assertHasAttribute', '\Some\Other\Space\AttributeClass', ['wrong']);
    $this->assertAssertion(FALSE, $method_tester, 'assertHasAttribute', '\Some\Other\Space\AttributeClass', ['cake', 'too many']);
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
  protected function assertAssertion($pass, $php_tester, $assertion_name, ...$assertion_parameters) {
    $message_parameters = print_r($assertion_parameters, TRUE);

    try {
      $php_tester->$assertion_name(...$assertion_parameters);

      // We get here if the assertion passed.
      if (!$pass) {
        $this->fail("The assertion {$assertion_name}() should fail with the following parameters: {$message_parameters}");
      }
    }
    catch (ExpectationFailedException|AssertionFailedError $e) {
      // We get here if the assertion failed.
      if ($pass) {
        $failure_message = $e->getMessage();
        $this->fail("The assertion {$assertion_name}() should pass with the following parameters:\n{$message_parameters}. Got failure:\n{$failure_message}.");
      }
    }

    // This is just to stop PHPUnit complaining that the test does not perform
    // any assertions.
    $this->assertTrue(TRUE);
  }

}
