<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Utility\NestedArray;
use CaseConverter\CaseString;

/**
 * Generator for router item on Drupal 8.
 *
 * This adds a routing item to the routing component.
 */
class RouterItem extends BaseGenerator {

  use NameFormattingTrait;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'path' => [
        'label' => "Route path",
        'description' => "The path of the route. Include the initial '/'.",
        'required' => TRUE,
      ],
      'route_name' => [
        'internal' => TRUE,
        'process_default' => TRUE,
        'default' => function($component_data) {
          // Strip the initial slash so it's not turned into a surplus dot.
          $trimmed_path = ltrim($component_data['path'], '/');

          // Get the module name rather than using the token, to avoid the
          // property name getting quoted.
          $module = $component_data['root_component_name'];
          $route_name = $module . '.' . str_replace('/', '.', $trimmed_path);
          return $route_name;
        },
      ],
      'title' => [
        'label' => "The page title for the route.",
        'default' => 'myPage',
        'process_default' => TRUE,
      ],
      'menu_link' => [
        'label' => "Menu link",
        'format' => 'compound',
        'cardinality' => 1,
        'properties' => [
          'title' => [
            'label' => "The title for the menu link.",
            'default' => 'My Page',
          ],
        ],
      ],
      'controller_plain_class_name' => [
        'internal' => TRUE,
        'process_default' => TRUE,
        'default' => function($component_data) {
          // Create a controller name from the route path.
          $snake = str_replace(['/', '-'], '_', $component_data['path']);
          $controller_class_name = CaseString::snake($snake)->pascal() . 'Controller';
          return $controller_class_name;
        },
      ],
      'controller_relative_class_name_pieces' => [
        'internal' => TRUE,
        'process_default' => TRUE,
        'default' => function($component_data) {
          return ['Controller', $component_data['controller_plain_class_name']];
        },
      ],
      'controller_qualified_class_name' => [
        'internal' => TRUE,
        'process_default' => TRUE,
        'default' => function($component_data) {
          return implode('\\', [
            'Drupal',
            '%module',
            'Controller',
            $component_data['controller_plain_class_name'],
          ]);
        },
      ],
      'controller_type' => [
        'label' => "Controller type",
        'options' => [
          // These are all YAML keys that take an initial '_', but having that
          // in the option key makes it harder to enter in the Drush UI.
          'controller' => 'Controller class',
          'form' => 'Form',
          'entity_view' => 'Entity view mode',
          'entity_form' => 'Entity form',
          'entity_list' => 'Entity list',
        ],
        'yaml_address' => ['defaults'],
      ],
      // The value for the YAML property that's set in controller_type.
      // This is a bit fiddly, but there's no UI for a key-value pair.
      'controller_type_value' => [
        'internal' => TRUE,
        'default' => function ($component_data) {
          $lookup = [
            // This will contain a placeholder token, but it's ok to use here as
            // the value will be quoted in the rendered YAML anyway.
            'controller' => '\\' . $component_data['controller_qualified_class_name'] . '::content',
            'form' => 'Drupal\%module\Form\MyFormClass',
            'entity_view' => 'ENTITY_TYPE.VIEW_MODE',
            'entity_form' => 'ENTITY_TYPE.FORM_MODE',
            'entity_list' => 'ENTITY_TYPE',
          ];
          if (isset($component_data['controller_type'])) {
            return $lookup[$component_data['controller_type']];
          }
        },
      ],
      'access_type' => [
        'label' => "Access type",
        'options' => [
          'access' => 'No access control',
          'permission' => 'Permission',
          'role' => 'Role',
          'entity_access' => 'Entity access',
        ],
        'yaml_address' => ['requirements'],
      ],
      // The value for the YAML property that's set in access_type.
      'access_type_value' => [
        'internal' => TRUE,
        'default' => function ($component_data) {
          $lookup = [
            'access' => 'TRUE',
            'permission' => 'TODO: set permission machine name',
            'role' => 'authenticated',
            'entity_access' => 'ENTITY_TYPE.OPERATION',
          ];
          if (isset($component_data['access_type'])) {
            return $lookup[$component_data['access_type']];
          }
        },
      ],
    ];
  }

  /**
   * Declares the subcomponents for this component.
   *
   * @return
   *  An array of subcomponent names and types.
   */
  public function requiredComponents() {
    $components = [];

    // Each RouterItem that gets added will cause a repeat request of these
    // components.
    $components['%module.routing.yml'] = array(
      'component_type' => 'Routing',
    );

    $controller_relative_class = $this->component_data['controller_relative_class_name_pieces'];

    // Add a controller class if needed.
    if (!empty($this->component_data['controller_type']) && $this->component_data['controller_type'] == 'controller') {
      $controller_class_component_name = implode('\\', $controller_relative_class);
      $components[$controller_class_component_name] = array(
        'component_type' => 'PHPClassFile',
        'relative_class_name' => $controller_relative_class,
      );
      $components["{$controller_class_component_name}:content"] = [
        'component_type' => 'PHPFunction',
        'containing_component' => "%requester:{$controller_class_component_name}",
        'declaration' => 'public function content()',
        'doxygen_first' => "Callback for the {$this->component_data['route_name']} route.",
      ];
    }

    if (!empty($this->component_data['menu_link'][0])) {
      // Strip off the module name prefix from the route name to make the plugin
      // name, as the plugin generator will add it back again.
      $plugin_name = $this->component_data['route_name'];
      $plugin_name = substr($plugin_name, strlen($this->component_data['root_component_name']) + 1);

      $components['menu_link'] = array(
        'component_type' => 'PluginYAML',
        'plugin_type' => 'menu.link',
        'plugin_name' => $plugin_name,
        'plugin_properties' => [
          'title' => $this->component_data['menu_link'][0]['title'],
          'route_name' => $this->component_data['route_name'],
        ],
      );
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%self:%module.routing.yml';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $path = $this->component_data['path'];

    $route_yaml = [];

    // Prepend a slash to the path for D8 if one not given.
    if (substr($path, 0, 1) != '/') {
      $path = '/' . $path;
    }
    $route_yaml['path'] = $path;
    $route_yaml['defaults']['_title'] = $this->component_data['title'];

    // Set the YAML values that come from component data with an address.
    $yaml_data_component_properties = [
      'controller_type',
      'access_type',
    ];
    foreach ($yaml_data_component_properties as $component_property_name) {
      if (empty($this->component_data[$component_property_name])) {
        continue;
      }

      // The value for the property is the YAML key without the initial '_'; the
      // YAML value is given in a companion property called PROPERTY_value; the
      // 'yaml_address' attribute in the property's info defines where in the
      // YAML structure the key and value should be inserted.
      $yaml_key = '_' . $this->component_data[$component_property_name];

      $yaml_value = $this->component_data["{$component_property_name}_value"];

      // Bit of a hack: instantiated generators don't have access to their
      // processed data info.
      $property_info = static::componentDataDefinition()[$component_property_name];
      $property_address = $property_info['yaml_address'];
      $property_address[] = $yaml_key;

      NestedArray::setValue($route_yaml, $property_address, $yaml_value);
    }

    $route_name = $this->component_data['route_name'];
    $routing_data[$route_name] = $route_yaml;

    return [
      'route' => [
        'role' => 'yaml',
        'content' => $routing_data,
      ],
    ];
  }

}
