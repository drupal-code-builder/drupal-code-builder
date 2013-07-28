<?php

/**
 * @file
 * Definition of ModuleBuider\Task\Collect.
 */

namespace ModuleBuider\Task;

/**
 * Task handler for collecting and processing hook definitions.
 *
 * This will do different things depending on the core Drupal version:
 * On D5/6, this donwloads!
 */
class Collect {

  /**
   * The sanity level this task requires to operate.
   */
  public $sanity_level = 'hook_directory';

  /**
   * Constructor.
   *
   * @param $environment
   *  The current environment handler.
   */
  function __construct($environment) {
    $this->environment = $environment;
  }

  /**
   * Collect hook api.php documentation files from sources and process them.
   *
   * (Replaces module_builder_update_data().)
   */
  function collectHooks() {
    // Load the legacy procedural include file.
    // TODO: move these into this class.
    $this->environment->loadInclude('update');

    // Update the hook documentation.
    $hook_files = module_builder_update_documentation();

    // Process the hook files.
    // TODO: move these into this class.
    $this->environment->loadInclude('process');
    module_builder_process_hook_data($hook_files);
  }

}
