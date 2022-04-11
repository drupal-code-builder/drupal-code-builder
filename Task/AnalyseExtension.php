<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\File\DrupalExtension;

/**
 * Task handler for analyzing an existing module.
 */
class AnalyseExtension extends Base {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  public function createExtension(string $extension_type, string $extension_path): DrupalExtension {
    return new DrupalExtension($extension_type, $extension_path);
  }


}
