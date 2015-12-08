<?php

/**
 * @file
 * Definition of ModuleBuilder\Generator\Permissions.
 */

namespace ModuleBuilder\Generator;

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
