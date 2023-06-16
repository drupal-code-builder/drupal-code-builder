<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests the container collection.
 *
 * @group container
 */
class ContainerCollectionTest extends TestBase {

  protected $drupalMajorVersion = 9;

  /**
   * Tests the container injects all the collector helpers into Collect.
   */
  public function testCollectorServices() {
    $collect = \DrupalCodeBuilder\Factory::getTask('Collect');
    $reflection = new \ReflectionProperty($collect, 'collectors');
    $reflection->setAccessible(TRUE);
    $collectors = $reflection->getValue($collect);

    $collector_classes = [];
    foreach ($collectors as $collector) {
      $collector_classes[] = get_class($collector);
    }

    $this->assertContains("DrupalCodeBuilder\Task\Analyse\TestTraits", $collector_classes);
    $this->assertContains('DrupalCodeBuilder\Task\Collect\AdminRoutesCollector', $collector_classes);
    $this->assertContains('DrupalCodeBuilder\Task\Collect\DataTypesCollector', $collector_classes);
    $this->assertContains('DrupalCodeBuilder\Task\Collect\ElementTypesCollector', $collector_classes);
    $this->assertContains('DrupalCodeBuilder\Task\Collect\EntityTypesCollector', $collector_classes);
    $this->assertContains('DrupalCodeBuilder\Task\Collect\FieldTypesCollector', $collector_classes);
    $this->assertContains('DrupalCodeBuilder\Task\Collect\PluginTypesCollector', $collector_classes);
    $this->assertContains('DrupalCodeBuilder\Task\Collect\ServiceTagTypesCollector', $collector_classes);
    $this->assertContains('DrupalCodeBuilder\Task\Collect\ServicesCollector', $collector_classes);
    $this->assertContains('DrupalCodeBuilder\Task\Collect\HooksCollector9', $collector_classes);
  }

}
