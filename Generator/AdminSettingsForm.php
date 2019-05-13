<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Utility\InsertArray;
use CaseConverter\CaseString;

/**
 * Component generator: admin form for modules.
 */
class AdminSettingsForm extends Form {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $data_definition['parent_class_name']['default'] = '\Drupal\Core\Form\ConfigFormBase';

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

    $data_definition['config_properties'] = [
      'label' => 'Entity properties',
      'description' => "The config properties that are stored for each entity of this type. An ID and label property are provided automatically.",
      'format' => 'compound',
      'properties' => [
        'name' => [
          'label' => 'Property name',
          'required' => TRUE,
        ],
        'label' => [
          'label' => 'Property label',
          'default' => function($component_data) {
            $entity_type_id = $component_data['name'];
            return CaseString::snake($entity_type_id)->title();
          },
          'process_default' => TRUE,
        ],
        'type' => [
          'label' => 'Data type',
          'required' => TRUE,
          'options' => 'ReportDataTypes:listDataTypesOptions',
        ],
      ],
      'process_empty' => TRUE,
    ];

    return $data_definition;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    // Restore the call to the parent method.
    $components['buildForm']['body'] = [
      "£form = parent::buildForm(£form, £form_state);",
      "",
      "£config = £this->config('%module.settings');",
      "",
      "£form['element'] = [",
      "  '#type' => 'textfield',",
      "  '#title' => t('Enter a value'),",
      "  '#required' => TRUE,",
      "  '#default_value' => £config->get('element'),",
      "];",
      "",
      "return £form;",
    ];

    // Add body for the submitForm() method.
    $components['submitForm']['body'] = [
      'parent::submitForm($form, $form_state);',
      "£config = £this->config('%module.settings');",
      '',
      "if (£form_state->hasValue('element')) {",
      "  £config->set('element', £form_state->getValue('element'));",
      '}',
      '',
      '£config->save();',
    ];

    $components['getEditableConfigNames'] = [
      'component_type' => 'PHPFunction',
      'containing_component' => '%requester',
      'doxygen_first' => '{@inheritdoc}',
      'declaration' => 'protected function getEditableConfigNames()',
      'body' => "return ['%module.settings'];",
    ];

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

    // Add a form element for each custom entity property.
    foreach ($this->component_data['config_properties'] as $schema_item) {
      $property_name = $schema_item['name'];

      // Skip id and label; done above.
      if ($property_name == 'id' || $property_name == 'label') {
        continue;
      }

      $components[$property_name] = [
        'component_type' => 'FormElement',
        'containing_component' => "%requester",
        'form_key' => $property_name,
        'element_type' => 'textfield',
        'element_title' => $schema_item['label'],
        'element_array' => [
          'default_value' => "£this->entity->get('{$property_name}')",
        ],
      ];
    }

    $schema_properties_yml = [];
    foreach ($this->component_data['config_properties'] as $schema_item) {
      $schema_properties_yml[$schema_item['name']] = [
        'type' => $schema_item['type'],
        'label' => $schema_item['label'],
      ];
    }

    $components["config/schema/%module.schema.yml"] = [
      'component_type' => 'ConfigSchema',
      'yaml_data' => [
         $this->component_data['root_component_name'] . '.settings' => [
           'type' => 'config_object',
           'label' => '%Module settings',
          'mapping' => $schema_properties_yml,
        ],
      ],
    ];

    return $components;
  }

}
