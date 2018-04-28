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

    try {
      $php_tester->assertClassHasInterfaces($expected_interfaces);
      // Assertion passed.
      if (!$pass_has) {
        $this->fail("assertClassHasInterfaces() should fail with " . $expected_interfaces_string);
      }
    }
    catch (ExpectationFailedException $e) {
      // Assertion failed.
      if ($pass_has) {
        $this->fail("assertClassHasInterfaces() should pass with " . $expected_interfaces_string);
      }
    }

    try {
      $php_tester->assertClassHasNotInterfaces($expected_interfaces);
      // Assertion passed.
      if (!$pass_has_not) {
        $this->fail("assertClassHasNotInterfaces() should fail with " . $expected_interfaces_string);
      }
    }
    catch (ExpectationFailedException $e) {
      // Assertion failed.
      if ($pass_has_not) {
        $this->fail("assertClassHasNotInterfaces() should pass with " . $expected_interfaces_string);
      }
    }
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

}
