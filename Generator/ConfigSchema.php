<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for general config schema YML files.
 */
class ConfigSchema extends YMLFile {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $definition = parent::componentDataDefinition();

    $definition['filename']['default'] = "config/schema/%module.schema.yml";

    // Set this value as a default, so that different components that request
    // config don't have to repeat this value.
    $definition['line_break_between_blocks']['default'] = TRUE;

    return $definition;
  }

}
