<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\AdminSettingsForm.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Component generator: admin form for modules.
 */
class AdminSettingsForm extends Form {

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the component. Any missing properties
   *   (or all if this is entirely omitted) are given default values.
   */
  function __construct($component_name, $component_data, $root_generator) {
    // Set some default properties.
    $component_data += array(
      'form_class_name' => 'AdminSettingsForm',
    );

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * {@inheritdoc}
   */
  public static function requestedComponentHandling() {
    return 'singleton';
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    $form_name = $this->getFormName();
    $components['admin/config/TODO-SECTION/%module'] = array(
      'component_type' => 'RouterItem',
      'title' => 'Administer %readable',
      'description' => 'Configure settings for %readable.',
      // Suppress the router item producing a controller, as we have a form.
      // TODO: pass the form details here.
      'controller' => [
        'controller_property' => '_form',
        'controller_value' => '\\' . $this->qualified_class_name,
      ],
    );

    $components['administer %module'] = array(
      'component_type' => 'Permission',
      'permission' => 'administer %module',
    );

    $components['info_configuration'] = array(
      'component_type' => 'InfoProperty',
      'property_name' => 'configure',
      'property_value' => 'admin/config/TODO-SECTION/%module',
    );

    return $components;
  }

  /**
   * The name of the form.
   */
  protected function getFormName() {
    $base_component_name = $this->component_data['root_component_name'];
    return "{$base_component_name}_settings_form";
  }

}
