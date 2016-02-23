<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Permission8.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator for module permissions on Drupal 8.
 */
class Permission8 extends Permission {

  /**
   * Return an array of subcomponent types.
   */
  protected function requiredComponents() {
    $components = array(
      '%module.permissions.yml' => array(
        'component_type' => 'YMLFile',
      ),
    );

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%module.permissions.yml';
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    $permission_name = $this->component_data['permission'];
    $yaml_data[$permission_name] = array(
      'title' => $permission_name,
      'decription' => 'TODO: permission description',
    );
    return $yaml_data;
  }

}
