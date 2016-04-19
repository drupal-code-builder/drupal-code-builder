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

    $permission_info = array(
      'title' => $permission_name,
      'description' => $this->component_data['description'],
    );
    if (!empty($this->component_data['restrict_access'])) {
      $permission_info['restrict access'] = TRUE;
    }

    $yaml_data[$permission_name] = $permission_info;

    return $yaml_data;
  }

}
