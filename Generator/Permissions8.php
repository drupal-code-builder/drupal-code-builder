<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Permissions8.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator for module permissions on Drupal 8.
 */
class Permissions8 extends Permissions {

  /**
   * Return an array of subcomponent types.
   */
  protected function requiredComponents() {
    $permission_names = $this->component_data['request_data'];
    $yaml_data = array();

    foreach ($permission_names as $permission_name) {
      $yaml_data[$permission_name] = array(
        'title' => $permission_name,
        'decription' => 'TODO: permission description',
      );
    }

    $components = array(
      '%module.permissions.yml' => array(
        'component_type' => 'YMLFile',
        'yaml_data' => $yaml_data,
      ),
    );

    return $components;
  }

}
