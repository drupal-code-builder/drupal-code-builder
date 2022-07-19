<?php

namespace DrupalCodeBuilder\Environment;

/**
 * Environment class for local Drush commands.
 */
class DrushFixtures extends Drush {

  /**
   * Assume that module_builder_devel module is installed in Drupal.
   */
  protected $storageType = 'ExportInclude';

}
