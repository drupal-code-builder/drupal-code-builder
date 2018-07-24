<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Utility\InsertArray;

/**
 * Component generator: admin form for modules.
 */
class AdminSettingsForm extends Form {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $parent_route_property['parent_route'] = [
      'label' => 'Parent menu item',
      'options' => 'ReportAdminRoutes:listAdminRoutesOptions',
      'required' => TRUE,
    ];
    InsertArray::insertBefore($data_definition, 'injected_services', $parent_route_property);

    $data_definition['form_class_name']['internal'] = TRUE;
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

    $task_handler_report_admin_routes = \DrupalCodeBuilder\Factory::getTask('ReportAdminRoutes');
    $admin_routes = $task_handler_report_admin_routes->listAdminRoutes();

    $parent_route_data = $admin_routes[$this->component_data['parent_route']];

    $settings_form_path = ltrim($parent_route_data['path'], '/') . '/%module';

    $components['route'] = array(
      'component_type' => 'RouterItem',
      // Specify this so we can refer to it in the menu link.
      'route_name' => "{$this->component_data['root_component_name']}.settings",
      // OK to use a token here, as the YAML value for this will be quoted
      // anyway.
      'path' => $settings_form_path,
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
        'route_name' => "{$this->component_data['root_component_name']}.settings",
        'parent' => $this->component_data['parent_route'],
      ],
    ];

    $components['administer %module'] = array(
      'component_type' => 'Permission',
      'permission' => 'administer %module',
    );

    $components['info_configuration'] = array(
      'component_type' => 'InfoProperty',
      'property_name' => 'configure',
      'property_value' => $settings_form_path,
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
