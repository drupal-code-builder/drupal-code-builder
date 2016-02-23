<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\AdminSettingsForm8.
 */

namespace ModuleBuilder\Generator;

/**
 * Component generator: admin form for modules.
 */
class AdminSettingsForm8 extends Form8 {

  /**
   * {@inheritdoc}
   */
  public static function requestedComponentHandling() {
    return 'singleton';
  }

  /**
   * Set properties relating to class name.
   */
  protected function setClassNames($component_name) {
    // Form the full class name.
    $class_name_pieces = array(
      'Drupal',
      $this->base_component->component_data['root_name'],
      'Form',
      'AdminSettingsForm',
    );
    $qualified_class_name = implode('\\', $class_name_pieces);

    parent::setClassNames($qualified_class_name);
  }

  /**
   * Return an array of subcomponent types.
   */
  protected function requiredComponents() {
    $components = parent::requiredComponents();

    // This takes care of adding hook_menu() and so on.
    $form_name = $this->getFormName();
    $components['admin/config/TODO-SECTION/%module'] = array(
      'component_type' => 'RouterItem',
      'title' => 'Administer %readable',
      'description' => 'Configure settings for %readable.',
    );

    $components['administer %module'] = array(
      'component_type' => 'Permission',
    );

    return $components;
  }

  /**
   * The name of the form.
   */
  protected function getFormName() {
    // TODO: this should be set in our data.
    $root_component_data = $this->getRootComponentData();
    $base_component_name = $root_component_data['root_name'];
    return "{$base_component_name}_settings_form";
  }

}
