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

  /**
   * Tests the assertPropertyIsExpanded()/assertPropertyIsInlined() assertions.
   *
   * @dataProvider providerAssertPropertyExpandedInline
   */
  public function testAssertPropertyExpandedInline($property, $expanded) {
    $yaml = <<<EOT
services:
  test_module.alpha:
    class: Drupal\test_module\Alpha
    arguments:
      - '@current_user'
      - '@entity_type.manager'
    tags:
      - { name: foo, priority: 0 }
  test_module.beta:
    class: Drupal\test_module\Alpha
    arguments: ['@current_user']
    tags:
      - { name: bar, priority: 0 }
EOT;

    $yaml_tester = new YamlTester($yaml);

    try {
      $yaml_tester->assertPropertyIsExpanded($property);
      // Assertion passed.
      if (!$expanded) {
        $this->fail("assertPropertyIsExpanded() should fail");
      }
    }
    catch (ExpectationFailedException $e) {
      // Assertion failed.
      if ($expanded) {
        $this->fail("assertPropertyIsExpanded() should pass.");
      }
    }

    try {
      $yaml_tester->assertPropertyIsInlined($property);
      // Assertion passed.
      if ($expanded) {
        $this->fail("assertPropertyIsInlined() should fail");
      }
    }
    catch (ExpectationFailedException $e) {
      // Assertion failed.
      if (!$expanded) {
        $this->fail("assertPropertyIsInlined() should pass.");
      }
    }
  }

  /**
   * Data provider for testAssertPropertyExpandedInline().
   */
  public function providerAssertPropertyExpandedInline() {
    return [
      'root' => [
        ['services'],
        TRUE,
      ],
      'alpha_service' => [
        ['services', 'test_module.alpha'],
        TRUE,
      ],
      'alpha_class' => [
        ['services', 'test_module.alpha', 'class'],
        TRUE,
      ],
      'alpha_args' => [
        ['services', 'test_module.alpha', 'arguments'],
        TRUE,
      ],
      'alpha_tags' => [
        ['services', 'test_module.alpha', 'tags'],
        TRUE,
      ],
      'alpha_tags_0' => [
        ['services', 'test_module.alpha', 'tags', 0],
        TRUE,
      ],
      'alpha_name' => [
        ['services', 'test_module.alpha', 'tags', 0, 'name'],
        FALSE,
      ],
      'beta_service' => [
        ['services', 'test_module.beta'],
        TRUE,
      ],
      'beta_args' => [
        ['services', 'test_module.beta', 'arguments'],
        TRUE,
      ],
      'beta_args_0' => [
        ['services', 'test_module.beta', 'arguments', 0],
        FALSE,
      ],
    ];
  }
}
