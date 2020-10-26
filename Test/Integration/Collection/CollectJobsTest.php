<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests collecting data from a Drupal site.
 */
class CollectJobsTest extends KernelTestBase {

  public static $modules = [
    'system',
  ];

  /**
   * Tests collecting all the data.
   */
  public function testJobList() {
    \DrupalCodeBuilder\Factory::setEnvironmentLocalClass('DrupalLibrary')
      ->setCoreVersionNumber(8)
      // Use the memory storage so we don't write any files.
      // TODO: make the storage a service, so we can mock it.
      ->setStorageLocalClass('Memory');

    $task_handler_collect = \DrupalCodeBuilder\Factory::getTask('Collect');
    $job_list = $task_handler_collect->getJobList();

    // Check for some of the jobs.
    $this->assertContains([
      "service_id" => "plugin.manager.field.field_type",
      "type_id" => "field.field_type",
      "process_label" => "plugin type",
      "item_label" => "plugin.manager.field.field_type",
      "collector" => "Collect\PluginTypesCollector",
    ], $job_list);
    $this->assertContains([
      "collector" => "Collect\ServicesCollector",
      "process_label" => "services",
      "last" => TRUE
    ], $job_list);
    $this->assertContains([
      "collector" => "Collect\ServiceTagTypesCollector",
      "process_label" => "tagged service types",
      "last" => TRUE
    ], $job_list);

    $results = [];
    foreach ($job_list as $job) {
      $task_handler_collect->collectComponentDataIncremental([$job], $results);
    }

    $this->assertArrayHasKey('hook definitions', $results);
    $this->assertArrayHasKey('plugin types', $results);
    $this->assertArrayHasKey('services', $results);
    $this->assertArrayHasKey('tagged service types', $results);
    $this->assertArrayHasKey('field types', $results);
    $this->assertArrayHasKey('data types', $results);
  }

}
