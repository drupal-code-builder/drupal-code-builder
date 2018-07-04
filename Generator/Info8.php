<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module info file for Drupal 8.
 */
class Info8 extends Info {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $data_definition['base'] = [
      'acquired' => TRUE,
    ];

    return $data_definition;
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
  function file_body() {
    $args = func_get_args();
    $files = array_shift($args);

    $module_data = $this->component_data;
    //print_r($module_data);

    $lines = array();
    $lines['name'] = $module_data['readable_name'];
    $lines['type'] = $module_data['base'];
    $lines['description'] = $module_data['short_description'];
    if (!empty($module_data['module_dependencies'])) {
      // For lines which form a set with the same key and array markers,
      // simply make an array.
      foreach ($module_data['module_dependencies'] as $dependency) {
        $lines['dependencies'][] = $dependency;
      }
    }

    if (!empty($module_data['module_package'])) {
      $lines['package'] = $module_data['module_package'];
    }

    $lines['core'] = "8.x";

    if (!empty($this->extraLines)) {
      $lines = array_merge($lines, $this->extraLines);
    }

    $info = $this->process_info_lines($lines);
    return $info;
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
    return array($yaml);
  }

}
