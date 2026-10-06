<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Factory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Unit tests for the front-end default functions.
 */
class UnitFrontEndExpressionsTest extends TestCase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $environment = $this->prophesize(\DrupalCodeBuilder\Environment\EnvironmentInterface::class);
    \DrupalCodeBuilder\Factory::setEnvironment($environment->reveal());
  }

  /**
   * Tests the output of JS code for the default functions.
   */
  public function testJavaScriptExpressionsCode(): void {
    $task = Factory::getTask('FrontEndExpressions');

    $js_code = $task->getFrontEndExpressionsCode();

    // We can't run the JS code, and checking it exactly is brittle, but check
    // for the object name and a function declaration.
    $this->assertStringContainsString("var DrupalCodeBuilderDataAddressExpressionLanguage", $js_code);
    $this->assertStringContainsString("machineToLabel: function(value) {", $js_code);
  }

  /**
   * Tests the front-end functions used in Expression Language.
   *
   * @param string $expression
   *   The Expression Language expression.
   * @param string $output
   *   The expected output.
   */
  #[DataProvider('providerPhpExpressions')]
  public function testPhpExpressions(string $expression, string $output): void {
    $expression_language = \DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory::getExpressionLanguage();

    $result = $expression_language->evaluate($expression);
    $this->assertEquals($output, $result);
  }

  /**
   * Data provider for testPhpExpressions().
   *
   * @return array
   */
  public static function providerPhpExpressions(): array {
    return [
      'machineToLabel' => [
        'machineToLabel("foo_bar")',
        'Foo Bar',
      ],
      'machineToClass' => [
        'machineToClass("foo_bar")',
        'FooBar',
      ],
      'classToMachine' => [
        'classToMachine("FooBar")',
        'foo_bar',
      ],
      'stripBefore' => [
        'stripBefore("prefix:main", ":")',
        'main',
      ],
    ];
  }

}

