<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Unit tests for the YamlTester test helper.
 */
class ParserYamlTest extends TestCase {

  /**
   * Tests the assertHasProperty() assertion.
   *
   * @dataProvider providerAssertHasProperty
   */
  public function testAssertHasProperty($property, $pass) {
    $yaml = <<<EOT
alpha:
  one:
    1: value
  two:
    - value
    -
      x: value
      y: value
  three:
    x: value
beta:
  one: value
  two:
    1: value
  three_beta:
    x: value
EOT;

    $yaml_tester = new YamlTester($yaml);

    try {
      $yaml_tester->assertHasProperty($property);
      // Assertion passed.
      if (!$pass) {
        $this->fail("assertHasProperty() should fail");
      }
    }
    catch (ExpectationFailedException $e) {
      // Assertion failed.
      if ($pass) {
        $this->fail("assertHasProperty() should pass.");
      }
    }
  }

  /**
   * Data provider for testAssertHasProperty().
   */
  public function providerAssertHasProperty() {
    return [
      'root' => [
        ['alpha'],
        TRUE
      ],
      'nested' => [
        ['alpha', 'one'],
        TRUE
      ],
      'numeric' => [
        ['alpha', 'two', 0],
        TRUE
      ],
      'numeric_nested' => [
        ['alpha', 'two', 1, 'x'],
        TRUE
      ],
      'wrong_three' => [
        ['beta', 'three'],
        FALSE
      ],
      'wrong_three_nested' => [
        ['beta', 'three', 'x'],
        FALSE
      ],
    ];
  }

}
