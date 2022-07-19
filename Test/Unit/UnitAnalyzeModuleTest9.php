<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the AnalyzeModule task.
 *
 * This uses the test_analyze_9 fixture module in Test/Fixtures/modules.
 */
class UnitAnalyzeModuleTest9 extends TestCase {
  use ProphecyTrait;

  /**
   * Test extraction of invented hooks.
   */
  public function testInventedHooks() {
    $environment = $this->prophesize(\DrupalCodeBuilder\Environment\EnvironmentInterface::class);

    // Return the location any module in the test fixtures modules folder.
    $environment->getExtensionPath('module', Argument::type('string'))->will(function ($args) {
      return __DIR__ . "/../Fixtures/modules/{$args[1]}/";
    });

    $analyze_task = new \DrupalCodeBuilder\Task\AnalyzeModule($environment->reveal());

    $hooks = $analyze_task->getInventedHooks('test_analyze_9');
    ksort($hooks);

    $expected = [
      // invokeAll()
      'test_analyze_9_method_all_cats' => '$purr, $miaow',
      'test_analyze_9_service_all_cats' => '$purr, $miaow',
      'test_analyze_9_di_all_cats' => '$purr, $miaow',
      // invoke()
      'test_analyze_9_method_one_cat' => '$purr, $miaow',
      'test_analyze_9_service_one_cat' => '$purr, $miaow',
      'test_analyze_9_di_one_cat' => '$purr, $miaow',
      // alter()
      'test_analyze_9_method_change_cat_alter' => '$purr, $miaow',
      'test_analyze_9_service_change_cat_alter' => '$purr, $miaow',
      'test_analyze_9_di_change_cat_alter' => '$purr, $miaow',
      // alter() with an array of hooks.
      'test_analyze_9_di_change_cat_1_alter' => '$purr, $miaow',
      'test_analyze_9_di_change_cat_2_alter' => '$purr, $miaow',
      'test_analyze_9_method_change_cat_1_alter' => '$purr, $miaow',
      'test_analyze_9_method_change_cat_2_alter' => '$purr, $miaow',
      'test_analyze_9_service_change_cat_1_alter' => '$purr, $miaow',
      'test_analyze_9_service_change_cat_2_alter' => '$purr, $miaow',
    ];
    // WTF: assertEqualsCanonicalizing() doesn't work with associative arrays.
    // Have to sort these ourselves.
    ksort($expected);

    $this->assertEquals($expected, $hooks);
  }

}
