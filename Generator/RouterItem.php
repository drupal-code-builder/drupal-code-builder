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
        'description' => "The path of the route. Do not include the initial '/'.",
        'required' => TRUE,
      ],
      'title' => [
        'label' => "The page title for the route.",
        'default' => 'myPage',
        'process_default' => TRUE,
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
          '_controller' => 'Controller class',
          '_form' => 'Form',
          '_entity_view' => 'Entity view mode',
          '_entity_form' => 'Entity form',
          '_entity_list' => 'Entity list',
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
            '_controller' => '\\' . $component_data['controller_qualified_class_name'] . '::content',
            '_form' => 'Drupal\%module\Form\MyFormClass',
            '_entity_view' => 'ENTITY_TYPE.VIEW_MODE',
            '_entity_form' => 'ENTITY_TYPE.FORM_MODE',
            '_entity_list' => 'ENTITY_TYPE',
          ];
          if (isset($component_data['controller_type'])) {
            return $lookup[$component_data['controller_type']];
          }
        },
      ],
      'access_type' => [
        'label' => "Access type",
        'options' => [
          '_accesss' => 'No access control',
          '_permission' => 'Permission',
          'role' => 'Role',
          '_entity_access' => 'Entity access',
        ],
        'yaml_address' => ['requirements'],
      ],
      // The value for the YAML property that's set in access_type.
      'access_type_value' => [
        'internal' => TRUE,
        'default' => function ($component_data) {
          $lookup = [
            '_accesss' => 'TRUE',
            '_permission' => 'TODO: set permission machine name',
            'role' => 'authenticated',
            '_entity_access' => 'ENTITY_TYPE.OPERATION',
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
    if (!empty($this->component_data['controller_type']) && $this->component_data['controller_type'] == '_controller') {
      $components[implode('\\', $controller_relative_class)] = array(
        'component_type' => 'PHPClassFile',
        'relative_class_name' => $controller_relative_class,
      );
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return $this->component_data['root_component_name'] . '/' . 'Routing:%module.routing.yml';
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    $path = $this->component_data['path'];

    $route_yaml = [];

    // Prepend a slash to the path for D8.
    $route_yaml['path'] = '/' . $path;
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

      // The value for the property is the YAML key; the YAML value is given in
      // a companion property called PROPERTY_value; the 'yaml_address'
      // attribute in the property's info defines where in the YAML structure
      // the key and value should be inserted.
      $yaml_key = $this->component_data[$component_property_name];

      $yaml_value = $this->component_data["{$component_property_name}_value"];

      // Bit of a hack: instantiated generators don't have access to their
      // processed data info.
      $property_info = static::componentDataDefinition()[$component_property_name];
      $property_address = $property_info['yaml_address'];
      $property_address[] = $yaml_key;

      NestedArray::setValue($route_yaml, $property_address, $yaml_value);
    }

    $route_name = str_replace('/', '.', $path);
    $routing_data['%module.' . $route_name] = $route_yaml;

    return [
      'route' => [
        'role' => 'yaml',
        'content' => $routing_data,
      ],
    ];
  }

}
