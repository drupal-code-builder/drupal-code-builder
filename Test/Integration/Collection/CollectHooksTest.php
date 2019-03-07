<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;

use Drupal\KernelTests\KernelTestBase;

/**
 * Integration tests test aspects that need a working Drupal site.
 *
 * These need to be run from Drupal's PHPUnit, rather than ours:
 * @code
 *  [drupal]/core $ ../vendor/bin/phpunit ../vendor/drupal-code-builder/drupal-code-builder/Test/Integration/Collection/CollectHooksTest.php
 * @endcode
 *
 * Note that a common base class won't work for these tests, as when Travis
 * runs integration tests, Drupal doesn't have the DCB classes in its
 * autoloader, and so a base class for the collection test classes can't be
 * loaded.
 */
class CollectHooksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Drupal doesn't know about DCB, so won't have it in its autoloader, so
    // rely on the Factory file's autoloader.
    $dcb_root = dirname(dirname(dirname(__DIR__)));
    require_once("$dcb_root/Factory.php");

    \DrupalCodeBuilder\Factory::setEnvironmentLocalClass('DrupalLibrary')
      ->setCoreVersionNumber(\Drupal::VERSION);

    $this->environment = \DrupalCodeBuilder\Factory::getEnvironment();

    parent::setUp();
  }

  /**
   * Tests collection of hooks info.
   */
  public function testHooksCollection() {
    $hooks_collector = new \DrupalCodeBuilder\Task\Collect\HooksCollector8(
      $this->environment
    );

    $test_hook_jobs = [
      [
        'uri' => 'core/lib/Drupal/Core/Entity/entity.api.php',
        'filename' => 'CORE_entity.api.php',
        'name' => 'entity.api',
        'group' => 'core:entity',
        'module' => 'core',
        'process_label' => 'hooks',
        'item_label' => 'entity.api.php',
        'collector' => 'HooksCollector',
      ],
    ];

    $data = $hooks_collector->collect($test_hook_jobs);

    // Only test specific hooks; don't assert a count
    $hook_entity_base_field_info_data = $data['core:entity']['hook_entity_base_field_info'];

    $this->assertEquals('hook', $hook_entity_base_field_info_data['type']);
    $this->assertEquals('hook_entity_base_field_info', $hook_entity_base_field_info_data['name']);
    $this->assertEquals(
      'function hook_entity_base_field_info(\Drupal\Core\Entity\EntityTypeInterface $entity_type)',
      $hook_entity_base_field_info_data['definition'],
      'The data has the hook definition with the fully-qualified typehint.'
    );
    $this->assertEquals('Provides custom base field definitions for a content entity type.', $hook_entity_base_field_info_data['description']);
    $this->assertEquals('%module.module', $hook_entity_base_field_info_data['destination']);
    $this->assertEquals([], $hook_entity_base_field_info_data['dependencies']);
    $this->assertEquals('core:entity', $hook_entity_base_field_info_data['group']);
    $this->assertEquals(TRUE, $hook_entity_base_field_info_data['core']);
    $this->assertEquals('core:entity', $hook_entity_base_field_info_data['group']);
    $this->assertEquals('public:///CORE_entity.api.php', $hook_entity_base_field_info_data['file_path']);

    $this->assertArrayHasKey('body', $hook_entity_base_field_info_data);
    $body = $hook_entity_base_field_info_data['body'];

    $this->assertNotEmpty($body);
    $this->assertContains(
      "\$fields['mymodule_text'] = \Drupal\Core\Field\BaseFieldDefinition::create('string')",
      $body,
      'The short class name in hook body code is replaced with the fully-qualified version.'
    );
  }

}
