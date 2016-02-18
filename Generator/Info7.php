<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Info7.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator class for module info file for Drupal 7.
 */
class Info7 extends InfoIni {

  /**
   * Create lines of file body for Drupal 7.
   */
  function file_body() {
    $module_data = $this->base_component->component_data;
    //print_r($module_data);

    $lines = array();
    $lines['name'] = $module_data['readable_name'];
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

    $lines['core'] = "7.x";

    $info = $this->process_info_lines($lines);
    return $info;
  }

  /**
   * @inheritdoc
   */
  public function filesAlter(&$files) {
    // Files containing classes need to be declared in the .info file.
    $info_extra_lines = array();
    foreach ($files as $file) {
      if (!empty($file['contains_classes'])) {
        $info_extra_lines['files'][] = $file['filename'];
      }
    }

    // Add a 'configure' line if there's an admin settings form component.
    $components = $this->getComponentList();
    if (isset($components['AdminSettingsForm'])) {
      // TODO: get this path from the generator.
      $info_extra_lines['configure'] = 'admin/config/TODO-SECTION/%module';
    }

    $lines = $this->process_info_lines($info_extra_lines);
    $files['info']['body'] = array_merge($files['info']['body'], $this->process_info_lines($info_extra_lines));
  }

}
