<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\Permissions.
 */

namespace ModuleBuider\Generator;

/**
 * Generator for module permissions.
 */
class Permissions extends BaseGenerator {

  /**
   * @inheritdoc
   */
  public static function requestedComponentHandling() {
    return 'group';
  }

  /**
   * Return an array of subcomponent types.
   */
  protected function requiredComponents() {
    $components = array(
      'hook_permission' => array(
        'component_type' => 'HookPermission',
        'permissions' => $this->component_data['request_data'],
      ),
      // TODO: make this automatic, done by HookImplementation.
      'hooks' => 'Hooks',
    );

    return $components;
  }

}

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
