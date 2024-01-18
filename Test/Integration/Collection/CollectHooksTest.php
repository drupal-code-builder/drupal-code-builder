<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;

/**
 * Tests collecting data on hooks from Drupal.
 */
class CollectHooksTest extends CollectionTestBase {

  /**
   * @inheritdoc
   */
  protected static $modules = [
    'system',
    'node',
  ];

  /**
   * Tests collection of hooks info.
   */
  public function testHooksCollection() {
    $hooks_collector = new \DrupalCodeBuilder\Task\Collect\HooksCollector8(
      $this->environment
    );

    $job_list = $hooks_collector->getJobList();
    $this->assertContains([
      "uri" => "core/lib/Drupal/Core/Entity/entity.api.php",
      "filename" => "CORE_entity.api.php",
      "name" => "entity.api",
      "group" => "core:entity",
      "module" => "core",
      "process_label" => "hooks",
      "item_label" => "entity.api.php",
    ], $job_list);
    $this->assertContains([
      "uri" => "core/modules/node/node.api.php",
      "filename" => "node.api.php",
      "name" => "node.api",
      "process_label" => "hooks",
      "item_label" => "node.api.php",
    ], $job_list);

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
    $this->assertStringContainsStringIgnoringCase(
      "\$fields['mymodule_text'] = \Drupal\Core\Field\BaseFieldDefinition::create('string')",
      $body,
      'The short class name in hook body code is replaced with the fully-qualified version.'
    );

    $test_hook_jobs = [
      [
        'uri' => 'core/modules/views/views.api.php',
        'filename' => 'views.api.php',
        'name' => 'views.api',
        'group' => 'views',
        'module' => 'views',
        'process_label' => 'hooks',
        'item_label' => 'views.api.php',
        'collector' => 'HooksCollector',
      ],
    ];

    $data = $hooks_collector->collect($test_hook_jobs);

    // Only test specific hooks; don't assert a count
    $hook_views_post_render_data = $data['views']['hook_views_post_render'];

    // Test the short class names in the function declaration are replaced.
    // This is kind of babysitting, as most api.php files use fully-qualified
    // class names in the declarations, but there's no documentation standard
    // for it.
    $this->assertStringContainsString('\Drupal\views\ViewExecutable $view', $hook_views_post_render_data['definition']);
    $this->assertStringContainsString('\Drupal\views\Plugin\views\cache\CachePluginBase $cache', $hook_views_post_render_data['definition']);
  }

}
