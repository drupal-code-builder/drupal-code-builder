<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the AnalyseExtension task.
 *
 * This uses the test_analyze fixture module in Test/Fixtures/modules.
 */
class UnitAnalyseExtensionTest extends TestCase {
  use ProphecyTrait;

  public function testAnalyseExtension() {
    $environment = $this->prophesize(\DrupalCodeBuilder\Environment\EnvironmentInterface::class);

    $analyze_task = new \DrupalCodeBuilder\Task\AnalyseExtension($environment->reveal());

    $extension = $analyze_task->createExtension('module', __DIR__ . '/../Fixtures/modules/test_generated_plugin_type/');

    $this->assertTrue($extension->hasFile('%module.info.yml'));

    $yaml = $extension->getFileYaml('%module.info.yml');
    $this->assertEquals('Test Generated Plugin Type', $yaml['name']);
  }

}
