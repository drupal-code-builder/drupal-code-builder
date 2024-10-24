<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;

use DrupalCodeBuilder\Task\Collect\PluginTypesCollector;
use ReflectionMethod;

/**
 * Tests collecting data on plugin types from Drupal.
 */
class CollectPluginInfoTest extends CollectionTestBase {

  protected ?PluginTypesCollector $pluginTypesCollector;

  protected ?ReflectionMethod $gatherPluginTypeInfoMethod;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pluginTypesCollector = new \DrupalCodeBuilder\Task\Collect\PluginTypesCollector(
      \DrupalCodeBuilder\Factory::getEnvironment(),
      new \DrupalCodeBuilder\Task\Collect\ContainerBuilderGetter,
      new \DrupalCodeBuilder\Task\Collect\MethodCollector,
      new \DrupalCodeBuilder\Task\Collect\CodeAnalyser($this->environment)
    );

    // Hack the task handler so we can call the processing method with a subset
    // of plugin manager service IDs.
    $class = new \ReflectionObject($this->pluginTypesCollector);
    $this->gatherPluginTypeInfoMethod = $class->getMethod('gatherPluginTypeInfo');
    $this->gatherPluginTypeInfoMethod->setAccessible(TRUE);
  }

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    // Module which provides a plugin type, and a module which has a plugin of
    // that type.
    'filter',
    'media',
    // Provides a queue worker plugin.
    'media',
  ];

  protected function getPluginTypeInfoFromCollector($job) {
    return $this->gatherPluginTypeInfoMethod->invoke($this->pluginTypesCollector, [$job]);
  }

  /**
   * Tests collection of plugin type info.
   */
  public function testPluginTypesInfoCollection() {
    $this->pluginTypeQueueWorker();
    $this->pluginTypeMenuLink();
    $this->pluginTypeFieldType();
    $this->pluginTypeHelpSection();
  }

  /**
   * In Core, and other modules provide plugins.
   */
  protected function pluginTypeQueueWorker() {
    $plugin_types_info = $this->getPluginTypeInfoFromCollector(
      [
        'service_id' => 'plugin.manager.queue_worker',
        'type_id' => 'queue_worker',
      ]
    );

    $this->assertArrayHasKey('queue_worker', $plugin_types_info, "The plugin types list has the queue_worker plugin type.");

    $queue_worker_type_info = $plugin_types_info['queue_worker'];
    $this->assertEquals('queue_worker', $queue_worker_type_info['type_id']);
    $this->assertEquals('queue_worker', $queue_worker_type_info['type_label']);
    $this->assertEquals('plugin.manager.queue_worker', $queue_worker_type_info['service_id']);
    $this->assertEquals('Plugin/QueueWorker', $queue_worker_type_info['subdir']);
    $this->assertEquals('Drupal\Core\Queue\QueueWorkerInterface', $queue_worker_type_info['plugin_interface']);
    $this->assertEquals('Drupal\Core\Annotation\QueueWorker', $queue_worker_type_info['plugin_definition_annotation_name']);
    $this->assertEquals('Drupal\Core\Queue\QueueWorkerBase', $queue_worker_type_info['base_class']);

    $this->assertArrayHasKey('plugin_interface_methods', $queue_worker_type_info);
    $plugin_interface_methods = $queue_worker_type_info['plugin_interface_methods'];
    $this->assertCount(1, $plugin_interface_methods);
    $this->assertArrayHasKey('processItem', $plugin_interface_methods);

    $this->assertArrayHasKey('plugin_properties', $queue_worker_type_info);
    $plugin_properties = $queue_worker_type_info['plugin_properties'];
    $this->assertGreaterThanOrEqual(3, $plugin_properties);
    $this->assertArrayHasKey('id', $plugin_properties);
    $this->assertArrayHasKey('title', $plugin_properties);
    $this->assertArrayHasKey('cron', $plugin_properties);
  }

  /**
   * In core, YAML discovery.
   */
  protected function pluginTypeMenuLink() {
    $plugin_types_info = $this->getPluginTypeInfoFromCollector(
      [
        'service_id' => 'plugin.manager.menu.link',
        'type_id' => 'menu.link',
      ]
    );
    $this->assertArrayHasKey('menu.link', $plugin_types_info, "The plugin types list has the menu.link plugin type.");

    $menu_link_type_info = $plugin_types_info['menu.link'];
    $this->assertEquals('menu.link', $menu_link_type_info['type_id']);
    $this->assertEquals('menu.link', $menu_link_type_info['type_label']);
    $this->assertEquals('links.menu', $menu_link_type_info['yaml_file_suffix']);
    $this->assertArrayHasKey('menu_name', $menu_link_type_info['yaml_properties']);
  }

  /**
   * In Core, and our name doesn't match Plugin module's name.
   */
  protected function pluginTypeFieldType() {
    $plugin_types_info = $this->getPluginTypeInfoFromCollector(
      [
        'service_id' => 'plugin.manager.field.field_type',
        'type_id' => 'field.field_type',
      ]
    );

    $this->assertArrayHasKey('field.field_type', $plugin_types_info, "The plugin types list has the field.field_type plugin type.");

    $field_type_info = $plugin_types_info['field.field_type'];
    $this->assertEquals('field.field_type', $field_type_info['type_id']);
    $this->assertEquals('field.field_type', $field_type_info['type_label']);
    $this->assertEquals('plugin.manager.field.field_type', $field_type_info['service_id']);
    $this->assertEquals('Plugin/Field/FieldType', $field_type_info['subdir']);
    $this->assertEquals('Drupal\Core\Field\FieldItemInterface', $field_type_info['plugin_interface']);
    $this->assertEquals('Drupal\Core\Field\Annotation\FieldType', $field_type_info['plugin_definition_annotation_name']);
    $this->assertEquals('Drupal\Core\Field\FieldItemBase', $field_type_info['base_class']);

    $this->assertArrayHasKey('plugin_interface_methods', $field_type_info);
    $plugin_interface_methods = $field_type_info['plugin_interface_methods'];
    //$this->assertCount(3, $plugin_interface_methods);
    $this->assertArrayHasKey('propertyDefinitions', $plugin_interface_methods);
    $this->assertArrayHasKey('mainPropertyName', $plugin_interface_methods);
    $this->assertArrayHasKey('schema', $plugin_interface_methods);
    // ... TODO loads more!

    $this->assertArrayHasKey('plugin_properties', $field_type_info);
    $plugin_properties = $field_type_info['plugin_properties'];
    //$this->assertCount(3, $plugin_properties);
    $this->assertArrayHasKey('id', $plugin_properties);
    $this->assertArrayHasKey('module', $plugin_properties);
    $this->assertArrayHasKey('label', $plugin_properties);
    $this->assertArrayHasKey('description', $plugin_properties);
    $this->assertArrayHasKey('category', $plugin_properties);
    $this->assertArrayHasKey('default_widget', $plugin_properties);
    $this->assertArrayHasKey('default_formatter', $plugin_properties);
    // ... TODO loads more!
  }

  /**
   * In a module, and other modules provide plugins.
   *
   * Having an implementation of a plugin allows the analysis to detect the
   * likely plugin base class.
   */
  protected function pluginTypeHelpSection() {
    $plugin_types_info = $this->getPluginTypeInfoFromCollector(
      [
        'service_id' => 'plugin.manager.filter',
        'type_id' => 'filter',
      ]
    );

    $this->assertArrayHasKey('filter', $plugin_types_info, "The plugin types list has the filter plugin type.");

    $help_section_type_info = $plugin_types_info['filter'];
    $this->assertEquals('filter', $help_section_type_info['type_id']);
    $this->assertEquals('filter', $help_section_type_info['type_label']);
    $this->assertEquals('plugin.manager.filter', $help_section_type_info['service_id']);
    $this->assertEquals('Plugin/Filter', $help_section_type_info['subdir']);
    $this->assertEquals('Drupal\filter\Plugin\FilterInterface', $help_section_type_info['plugin_interface']);
    $this->assertEquals('Drupal\filter\Annotation\Filter', $help_section_type_info['plugin_definition_annotation_name']);
    $this->assertEquals('Drupal\filter\Plugin\FilterBase', $help_section_type_info['base_class']);
  }

}
