<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Unit tests for the PHPTester test helper.
 */
class ParserPHPTest extends TestCase {

  /**
   * Tests the assertIsProcedural() assertion.
   */
  public function testAssertIsProceduralAssertion() {
    $php_procedural = <<<EOT
<?php

use Some\Other\Space\Namespaced;
use Yet\Another\Space\Irrelevant;

function foo() {

}

function bar() {

}

EOT;

    $php_non_procedural = <<<EOT
<?php

namespace Some\Space\Namespaced;

use Some\Other\Space\Namespaced;
use Yet\Another\Space\Irrelevant;

class Foo implements Plain, WithSpace {

}

EOT;

    $php_tester = new PHPTester($php_procedural);

    $this->assertAssertion(TRUE, $php_tester, 'assertIsProcedural');

    $php_tester = new PHPTester($php_non_procedural);

    $this->assertAssertion(FALSE, $php_tester, 'assertIsProcedural');
  }

  /**
   * Tests the assertHasClass() assertion.
   */
  public function testAssertClassAssertion() {
    $php = <<<EOT
<?php

namespace Some\Space\Namespaced;

use Some\Other\Space\Namespaced;
use Yet\Another\Space\Irrelevant;

class Foo implements Plain, WithSpace {

}

EOT;

    $php_tester = new PHPTester($php);

    $this->assertAssertion(TRUE, $php_tester, 'assertHasClass', 'Some\Space\Namespaced\Foo');
    $this->assertAssertion(FALSE, $php_tester, 'assertHasClass', 'Some\Space\Namespaced\WrongClass');
    $this->assertAssertion(FALSE, $php_tester, 'assertHasClass', 'Some\Space\WrongNamespace\Foo');
    $this->assertAssertion(FALSE, $php_tester, 'assertHasClass', 'Some\Space\Namespaced\WithSpace');
  }

  /**
   * Tests the assertClassHasInterfaces() assertion.
   *
   * @dataProvider providerAssertClassInterfaces
   */
  public function testAssertClassInterfaces($expected_interfaces, $pass_has, $pass_has_not) {
    $php = <<<EOT
<?php

use Some\Space\Namespaced;
use Some\Other\Space\NamespacedOther;
use Yet\Another\Space\Irrelevant;

class Foo implements Plain, Namespaced, NamespacedOther {

}

EOT;

    $php_tester = new PHPTester($php);

    $expected_interfaces_string = implode(', ', $expected_interfaces);

    $this->assertAssertion($pass_has, $php_tester, 'assertClassHasInterfaces', $expected_interfaces);
    $this->assertAssertion($pass_has_not, $php_tester, 'assertClassHasNotInterfaces', $expected_interfaces);
  }

  /**
   * Data provider for testAssertHasProperty().
   */
  public function providerAssertClassInterfaces() {
    return [
      'single plain interface' => [
        ['Plain'],
        TRUE,
        FALSE,
      ],
      'single plain interface bad class' => [
        ['NotHere'],
        FALSE,
        TRUE,
      ],
      'single namespaced interface' => [
        ['Some\Space\Namespaced'],
        TRUE,
        FALSE,
      ],
      'single namespaced interface bad class' => [
        ['Some\Space\NotHere'],
        FALSE,
        TRUE,
      ],
      // This and a further case commented out, as assertClassHasNotInterfaces()
      // is not yet subtle enough to pick up on only the namespace being wrong
      // when the class matches.
      /*
      'single namespaced interface bad namespace' => [
        ['Some\WrongSpace\Namespaced'],
        FALSE,
        TRUE,
      ],
      */
      'multiple namespaced interfaces' => [
        ['Some\Space\Namespaced', 'Some\Other\Space\NamespacedOther'],
        TRUE,
        FALSE,
      ],
      'multiple namespaced interfaces with bad class' => [
        ['Some\Space\Namespaced', 'Some\Other\Space\NotHere'],
        FALSE,
        FALSE,
      ],
      'multiple namespaced interfaces with bad namespace' => [
        ['Some\Space\Namespaced', 'Some\Other\WrongSpace\NamespacedOther'],
        FALSE,
        FALSE,
      ],
      'multiple bad classes' => [
        ['Some\Space\Wrong', 'Some\Other\Space\NotHere'],
        FALSE,
        TRUE,
      ],
      /*
      'multiple bad namespaces' => [
        ['Some\Bad\Namespaced', 'Some\Other\Wrong\NamespacedOther'],
        FALSE,
        TRUE,
      ],
      */
    ];
  }

  /**
   * Tests the assertHasFunction() assertion.
   */
  public function testAssertHasFunctionAssertion() {
    $php = <<<'EOT'
<?php

use Some\Other\Space\Namespaced;
use Yet\Another\Space\Irrelevant;

function foo($param) {

}

function bar() {

}

EOT;

    $php_tester = new PHPTester($php);

    $this->assertAssertion(TRUE, $php_tester, 'assertHasFunction', 'foo');
    $this->assertAssertion(TRUE, $php_tester, 'assertHasFunction', 'bar');
    $this->assertAssertion(FALSE, $php_tester, 'assertHasFunction', 'notHere');
  }

  /**
   * Tests the assertClassDocBlockHasLine() assertion.
   *
   * @group php_tester_docblocks
   */
  public function testAssertClassDocblockHasLineAssertion() {
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

    $php_tester = new PHPTester($php);

    $this->assertAssertion(TRUE, $php_tester, 'assertClassDocBlockHasLine', 'Class docblock.');
    $this->assertAssertion(TRUE, $php_tester, 'assertClassDocBlockHasLine', 'Further line.');
    $this->assertAssertion(TRUE, $php_tester, 'assertClassDocBlockHasLine', 'Further line.');
    $this->assertAssertion(FALSE, $php_tester, 'assertClassDocBlockHasLine', 'not this line');
    $this->assertAssertion(FALSE, $php_tester, 'assertClassDocBlockHasLine', 'Partial');
  }

  /**
   * Tests the assertFileDocblockHasLine() assertion.
   *
   * @group php_tester_docblocks
   */
  public function testAssertFileDocblockHasLineAssertion() {
    $php = <<<EOT
<?php

/**
 * @file
 * File docblock.
 *
 * Further line.
 * Partial line.
 */

/**
 * Function docblock.
 */
function foo() {
}

EOT;

    $php_tester = new PHPTester($php);

    $this->assertAssertion(TRUE, $php_tester, 'assertFileDocblockHasLine', 'File docblock.');
    $this->assertAssertion(TRUE, $php_tester, 'assertFileDocblockHasLine', 'Further line.');
    $this->assertAssertion(TRUE, $php_tester, 'assertFileDocblockHasLine', 'Further line.');
    $this->assertAssertion(FALSE, $php_tester, 'assertFileDocblockHasLine', 'not this line');
    // TODO: assertion is not strict enough.
    // $this->assertAssertion(FALSE, $php_tester, 'assertFileDocblockHasLine', 'Partial');
  }

  /**
   * Tests the assertHasHookImplementation() assertion.
   *
   * @group php_tester_docblocks
   */
  public function testAssertHasHookImplementationAssertion() {
    $php = <<<EOT
<?php

/**
 * @file
 * File docblock.
 */

/**
 * Implements hook_foo().
 */
function my_module_foo() {
}

/**
 * hook_bar() has bad docblock, not properly implemented.
 */
function my_module_bar() {
}

/**
 * Not a hook.
 */
function my_module_not_hook() {
}

EOT;

    $php_tester = new PHPTester($php);

    $this->assertAssertion(TRUE, $php_tester, 'assertHasHookImplementation', 'hook_foo', 'my_module');
    $this->assertAssertion(FALSE, $php_tester, 'assertHasHookImplementation', 'hook_bar', 'my_module');
    $this->assertAssertion(FALSE, $php_tester, 'assertHasHookImplementation', 'hook_not_hook', 'my_module');
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
    catch (ExpectationFailedException $e) {
      // We get here if the assertion failed.
      if ($pass) {
        $this->fail("The assertion {$assertion_name}() should pass with the following parameters: {$message_parameters}");
      }
    }
  }

}
