<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for module permissions on Drupal 8.
 */
class Permission extends BaseGenerator {

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   An array of data for the component. Valid properties are:
   *    - 'permission': The machine name of the permission.
   *    - 'description': (optional) The description of the permission. If
   *      omitted, a default is generated from the machine name.
   *    - 'restrict_access': (optional) Whether to apply the 'restrict_access'
   *      property to the permission.
   */
  function __construct($component_name, $component_data, $root_generator) {
    // Set some default properties.
    $component_data += array(
      // The array property value is set at the component name by
      // processComponentData().
      'permission' => $component_name,
    );

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + array(
      'permission' => array(
        'label' => 'Permission machine-readable name',
        'default' => 'access my_module',
        'required' => TRUE,
      ),
      'description' => array(
        'label' => 'Permission description',
        'default' => function($component_data) {
          if (isset($component_data['permission'])) {
            return ucfirst(str_replace('_', ' ', $component_data['permission']));
          }
        },
        'process_default' => TRUE,
      ),
      'restrict_access' => array(
        'label' => 'Access warning',
        'description' => 'Whether the permission should show a warning that it should be granted with care.',
        'default' => FALSE,
        'format' => 'boolean',
      ),
    );
  }

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
    return $this->component_data['root_component_name'] . '/' . 'YMLFile:%module.permissions.yml';
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    $permission_name = $this->component_data['permission'];

    $permission_info = array(
      'title' => ucfirst($permission_name),
      'description' => $this->component_data['description'],
    );
    if (!empty($this->component_data['restrict_access'])) {
      $permission_info['restrict access'] = TRUE;
    }

    $yaml_data[$permission_name] = $permission_info;

    return [
      'permission' => [
        'role' => 'yaml',
        'content' => $yaml_data,
      ],
    ];
  }

}
