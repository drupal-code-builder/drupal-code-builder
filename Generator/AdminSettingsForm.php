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

    $data_definition['form_id']['default'] = function($component_data) {
      return $component_data['root_component_name'] . '_settings_form';
    };

    return $data_definition;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    $components['admin/config/TODO-SECTION/%module'] = array(
      'component_type' => 'RouterItem',
      // Specify this so we can refer to it in the menu link.
      'route_name' => '%module.settings',
      // OK to use a token here, as the YAML value for this will be quoted
      // anyway.
      'path' => 'admin/config/TODO-SECTION/%module',
      'title' => 'Administer %readable',
      'controller_type' => 'form',
      'controller_type_value' => '\\' . $this->component_data['qualified_class_name'],
      'access_type' => 'permission',
      'access_type_value' => 'administer %module',
    );

    $components['menu_link'] = [
      'component_type' => 'PluginYAML',
      'plugin_type' => 'menu.link',
      'plugin_name' => 'settings',
      'plugin_properties' => [
        'title' => '%Module',
        'description' => 'Configure the settings for %Module.',
        'route_name' => '%module.settings',
        'parent' => 'system.admin_config_system',
      ],
    ];

    $components['administer %module'] = array(
      'component_type' => 'Permission',
      'permission' => 'administer %module',
    );

    $components['info_configuration'] = array(
      'component_type' => 'InfoProperty',
      'property_name' => 'configure',
      'property_value' => 'admin/config/TODO-SECTION/%module',
    );

    $components["config/schema/%module.schema.yml"] = [
      'component_type' => 'ConfigSchema',
      'yaml_data' => [
         $this->component_data['root_component_name'] . '.settings' => [
           'type' => 'config_object',
           'label' => '%Module settings',
          'mapping' => [
          ],
        ],
      ],
    ];

    return $components;
  }

}
