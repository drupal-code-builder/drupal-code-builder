<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator class for module info file for Drupal 9.
 */
class Info9 extends Info {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('base')
      ->setAutoAcquiredFromRequester()
    );

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function detectExistence($existing_module_files) {
    // Quick and dirty hack!
    $root_component_name = $this->component_data['root_component_name'];
    // Violates DRY as this is also in getFileInfo()!
    $filename = "{$root_component_name}.info.yml";

    if (isset($existing_module_files[$filename])) {
      $this->exists = TRUE;
    }
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $file = parent::getFileInfo();

    $file['filename'] = '%module.info.yml';

    return $file;
  }

  /**
   * Create lines of file body for Drupal 8.
   */
  function infoData(): array {
    $args = func_get_args();
    $files = array_shift($args);

    $module_data = $this->component_data;
    // dump("FILE BODY");
    // dump($module_data->export());

    $lines = $this->getInfoFileEmptyLines();
    $lines['name'] = $module_data->readable_name->value;
    $lines['type'] = $module_data->base->value;
    $lines['description'] = $module_data->short_description->value;
    // For lines which form a set with the same key and array markers,
    // simply make an array.
    foreach ($module_data->module_dependencies as $dependency) {
      $lines['dependencies'][] = $dependency->value;
    }

    if (!$module_data->module_package->isEmpty()) {
      $lines['package'] = $module_data->module_package->value;
    }

    $lines['core_version_requirement'] = '^8 || ^9';

    if (!empty($this->extraLines)) {
      $lines = array_merge($lines, $this->extraLines);
    }
    // dump($lines);

    return $lines;
  }

  /**
   * Process a structured array of info files lines to a flat array for merging.
   *
   * @param $lines
   *  An array of lines keyed by label.
   *  Place grouped labels (eg, dependencies) into an array of
   *  their own, keyed numerically.
   *  Eg:
   *    name => module name
   *    dependencies => array(foo, bar)
   *
   * @return
   *  An array of lines for the .info file.
   */
  function process_info_lines($lines) {
    $yaml_parser = new \Symfony\Component\Yaml\Yaml;
    $yaml = $yaml_parser->dump($lines, 2, 2);
    //drush_print_r($yaml);

    // Because the yaml is all built for us, this is just a singleton array.
    return [$yaml];
  }

}
