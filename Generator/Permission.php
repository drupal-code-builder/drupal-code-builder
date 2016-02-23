<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\Permission.
 */

namespace ModuleBuilder\Generator;

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
   *   (optional) An array of data for the component. Any missing properties
   *   (or all if this is entirely omitted) are given default values.
   *   Valid properties are:
   *      - 'permission': The machine name of the permission.
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
   * {@inheritdoc}
   */
  public static function requestedComponentHandling() {
    return 'repeat';
  }

  /**
   * Return an array of subcomponent types.
   */
  protected function requiredComponents() {
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
    return 'hook_permission';
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    // Return code for a single permission item for the hook.
    $code = array();

    $permission_name = $this->component_data['permission'];
    $code[] = "£permissions['$permission_name'] = array(";
    $code[] = "  'title' => t('TODO: enter permission title'),";
    $code[] = "  'description' => t('TODO: enter permission description'),";
    $code[] = ");";
    return $code;
  }

}
