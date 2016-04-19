<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\Permission.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for module permissions.
 *
 * TODO: Change name to singular when D8 version is also changed.
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
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    // Set some default properties.
    $component_data += array(
      // TEMPORARY. Will change when repeat component handling changes in
      // processComponentData().
      'permission' => $component_name,
    );

    parent::__construct($component_name, $component_data, $generate_task, $root_generator);
  }

  /**
   * Define the component data this component needs to function.
   */
  protected static function componentDataDefinition() {
    return array(
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
      ),
      'restrict_access' => array(
        'label' => 'Whether the permission should show a warning that it should be granted with care.',
        'default' => FALSE,
        'format' => 'boolean',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function requestedComponentHandling() {
    return 'repeat';
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = array(
      'hooks' => array(
        'component_type' => 'Hooks',
        'hooks' => array(
          'hook_permission' => TRUE,
        ),
      ),
    );

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return 'HookPermission:hook_permission';
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    // Return code for a single permission item for the hook.
    $code = array();

    $permission_name = $this->component_data['permission'];
    $permission_description = $this->component_data['description'];
    $code[] = "£permissions['$permission_name'] = array(";
    $code[] = "  'title' => t('$permission_description'),";
    $code[] = "  'description' => t('TODO: enter permission description'),";
    $code[] = ");";
    return $code;
  }

}
