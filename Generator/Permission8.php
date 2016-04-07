<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\Permission8.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for module permissions on Drupal 8.
 */
class Permission8 extends Permission {

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
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
    return 'YMLFile:%module.permissions.yml';
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    $permission_name = $this->component_data['permission'];
    $yaml_data[$permission_name] = array(
      'title' => $permission_name,
      'description' => 'TODO: permission description',
    );
    return $yaml_data;
  }

}
