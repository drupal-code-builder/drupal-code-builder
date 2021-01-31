<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * Tests the AnalyzeModule task.
 *
 * This uses the test_analyze fixture module in Test/Fixtures/modules.
 */
class UnitAnalyzeModuleTest extends TestCase {

  public function testAnalyzeModule() {
    $environment = $this->prophesize(\DrupalCodeBuilder\Environment\EnvironmentInterface::class);

    // Return the location any module in the test fixtures modules folder.
    $environment->getExtensionPath('module', Argument::type('string'))->will(function ($args) {
      return __DIR__ . "/../Fixtures/modules/{$args[1]}/";
    });

    $analyze_task = new \DrupalCodeBuilder\Task\AnalyzeModule($environment->reveal());

    $files = $analyze_task->getFiles('test_analyze');

    // We get absolute paths back; too faffy to check them in a portable way.
    $this->assertCount(4, $files);

    $hooks = $analyze_task->getInventedHooks('test_analyze');
  }

}
