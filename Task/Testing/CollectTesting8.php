<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Testing\CollectTesting8.
 */

namespace DrupalCodeBuilder\Task\Testing;

use DrupalCodeBuilder\Task\Collect8;
use DrupalCodeBuilder\Factory;

/**
 * Collect hook definitions to be stored as a file in our tests folder.
 *
 * This task is meant for internal use only, to keep the testing hook
 * definitions up to date.
 */
class CollectTesting8 extends Collect8 {

  /**
   * {@inheritdoc}
   */
  protected function gatherHookDocumentationFiles() {
    $files = parent::gatherHookDocumentationFiles();

    // For testing, only take a subset of api.php files so we're not storing a
    // massive list of hooks.
    $testing_files = array(
      'system.api.php' => TRUE,
      'block.api.php' => TRUE,
      // Need this for hook_help().
      'help.api.php' => TRUE,
      // Need this for ThemeHook component.
      'theme.api.php' => TRUE,
    );

    $files = array_intersect_key($files, $testing_files);

    return $files;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPluginManagerServices() {
    $plugin_manager_service_ids = parent::getPluginManagerServices();

    // For testing, only take a subset of service names.
    $testing_service_ids = array(
      'plugin.manager.block',
      'plugin.manager.field.formatter',
    );

    $plugin_manager_service_ids = array_intersect($plugin_manager_service_ids, $testing_service_ids);

    return $plugin_manager_service_ids;
  }

  /**
   * {@inheritdoc}
   */
  protected function gatherServiceDefinitions() {
    $service_definitions = parent::gatherServiceDefinitions();

    // For testing, only take a subset of service names.
    $testing_service_ids = array(
      'current_user' => TRUE,
      'entity.manager' => TRUE,
    );

    $service_definitions = array_intersect_key($service_definitions, $testing_service_ids);

    return $service_definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function writeProcessedData($data, $type) {
    // Write the processed data to a file in our testing folder.
    $directory = Factory::getLibraryBaseDirectory()
      . '/tests/sample_hook_definitions/'
      . $this->environment->getCoreMajorVersion();
    $serialized = serialize($data);
    file_put_contents("{$directory}/{$type}_processed.php", $serialized);
  }

}
