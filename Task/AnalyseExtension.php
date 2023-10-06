<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\File\DrupalExtension;
use Drupal\Core\Extension\Extension as CoreExtension;

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

  /**
   * Creates a Drupal extension object for a given core extension object.
   *
   * @param \Drupal\Core\Extension\Extension $core_extension
   *   The core extension object.
   *
   * @return \DrupalCodeBuilder\File\DrupalExtension
   *   The Drupal extension object.
   */
  public function createExtensionFromCoreExtension(CoreExtension $core_extension): DrupalExtension {
    return new DrupalExtension($core_extension->getType(), $core_extension->getPath());
  }

}
