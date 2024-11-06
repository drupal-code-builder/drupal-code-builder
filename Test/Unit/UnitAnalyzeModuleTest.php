<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the AnalyzeModule task.
 *
 * This uses the test_analyze fixture module in Test/Fixtures/modules.
 */
class UnitAnalyzeModuleTest extends TestCase {
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

    $hooks = $analyze_task->getInventedHooks('test_analyze');

    $this->assertEqualsCanonicalizing([
      'test_analyze_tokens_all' => '$param',
      'test_analyze_tokens_single' => '$param',
      'test_analyze_tokens_alter' => '$param',
      'test_analyze_install_all' => '$param',
      'test_analyze_install_single' => '$param',
      'test_analyze_install_alter' => '$param',
      'test_analyze_module_all' => '$param',
      'test_analyze_module_single' => '$param',
      'test_analyze_module_alter' => '$param',
      'test_analyze_views_all' => '$param',
      'test_analyze_views_single' => '$param',
      'test_analyze_views_alter' => '$param',
      'test_analyze_service_all' => '$param',
      'test_analyze_service_single' => '$param',
      'test_analyze_service_alter' => '$param',
      'test_analyze_plugin_all' => '$param',
      'test_analyze_plugin_single' => '$param',
      'test_analyze_plugin_alter' => '$param',
    ], $hooks);
  }

}
