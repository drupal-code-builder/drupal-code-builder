<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;

/**
 * Tests service tags collection.
 */
class CollectServiceTagsTest extends CollectionTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->serviceTagsCollector = new \DrupalCodeBuilder\Task\Collect\ServiceTagTypesCollector(
      \DrupalCodeBuilder\Factory::getEnvironment(),
      new \DrupalCodeBuilder\Task\Collect\ContainerBuilderGetter,
      new \DrupalCodeBuilder\Task\Collect\MethodCollector,
    );
  }

  /**
   * Tests collection of service tag data.
   */
  public function testCoreServiceTagsCollection() {
    $tag_data = $this->serviceTagsCollector->collect([]);

    $this->assertArrayHasKey('route_enhancer', $tag_data);
    $route_enhancer_data = $tag_data['route_enhancer'];
    $this->assertEquals('Enhancer', $route_enhancer_data['label']);
    $this->assertEquals('service_collector', $route_enhancer_data['collector_type']);
    $this->assertEquals('Drupal\Core\Routing\EnhancerInterface', $route_enhancer_data['interface']);

    $this->assertArrayHasKey('logger', $tag_data);
    $logger_data = $tag_data['logger'];
    $this->assertEquals('Logger', $logger_data['label']);
    $this->assertEquals('service_collector', $logger_data['collector_type']);
    $this->assertEquals('Psr\Log\LoggerInterface', $logger_data['interface']);
  }

}
