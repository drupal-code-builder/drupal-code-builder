<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Component generator: admin form for modules.
 */
class AdminSettingsForm extends Form {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $data_definition['form_class_name']['default'] = 'AdminSettingsForm';
    $data_definition['form_class_name']['process_default'] = TRUE;

    return $data_definition;
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
        'controller_value' => '\\' . $this->component_data['qualified_class_name'],
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
