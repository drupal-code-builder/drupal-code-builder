<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Info8.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator class for module info file for Drupal 8.
 */
class Info8 extends Info {

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $files = parent::getFileInfo();

    $files['info']['filename'] = '%module.info.yml';

    return $files;
  }

  /**
   * Create lines of file body for Drupal 8.
   */
  function file_body() {
    $args = func_get_args();
    $files = array_shift($args);

    $module_data = $this->base_component->component_data;
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

  /**
   * {@inheritdoc}
   */
  public function filesAlter(&$files, $component_list) {
    $info_extra_lines = array();

    // Add a 'configure' line if there's an admin settings form component.
    if (isset($component_list['AdminSettingsForm'])) {
      // TODO: get this path from the generator.
      $info_extra_lines['configure'] = 'admin/config/TODO-SECTION/%module';
    }

    $lines = $this->process_info_lines($info_extra_lines);
    $files['info']['body'] = array_merge($files['info']['body'], $this->process_info_lines($info_extra_lines));
  }

}
