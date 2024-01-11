<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;

/**
 * Tests entity types collection.
 */
class CollectEntityTypesTest extends CollectionTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
  ];

  /**
   * Tests collection of entity type data.
   */
  public function testCoreEntityTypesCollection() {
    $entity_types_collector = \DrupalCodeBuilder\Factory::getTask('Collect\EntityTypesCollector');

    $entity_types_data = $entity_types_collector->collect([]);

    $this->assertArrayHasKey('node', $entity_types_data);
    $this->assertEquals('Content', $entity_types_data['node']['label']);
    $this->assertEquals('content', $entity_types_data['node']['group']);
    $this->assertEquals('\Drupal\node\NodeInterface', $entity_types_data['node']['interface']);
  }

}