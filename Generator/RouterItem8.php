<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\RouterItem8.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for router item on Drupal 8.
 *
 * This adds a routing item to the routing component.
 */
class RouterItem8 extends RouterItem {

  use NameFormattingTrait;

  /**
   * Constructor method; sets the component data.
   *
   * Properties in $component_data:
   *  - controller: (optional) An array specifying the source for the route's
   *    content. May contain:
   *    - controller_property: (optional) The name of the property in the route
   *      defaults that sets the controler. E.g, '_controller', '_entity_view'.
   *    - controller_value: (optional) The corresponding value.
   *    These default to the plain '_controller' with a controller class of
   *   {ROUTE}Controller.
   */
  function __construct($component_name, $component_data, $root_generator) {
    // Create a controller name from the route path.
    $snake = str_replace(['/', '-'], '_', $component_name);
    $controller_class_name = $this->toCamel($snake) . 'Controller';
    $controller_qualified_class_name = implode('\\', [
      'Drupal',
      '%module',
      'Controller',
      $controller_class_name
    ]);

    $component_data += [
      'controller' => [
        'controller_property' => '_controller',
        'controller_value' => '\\' . "$controller_qualified_class_name::content",
      ],
      'controller_qualified_class' => $controller_qualified_class_name,
    ];

    parent::__construct($component_name, $component_data, $root_generator);
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

    // Add a controller class if needed.
    if (!empty($this->component_data['controller']['controller_property'])
        && $this->component_data['controller']['controller_property'] == '_controller') {
      $controller_qualified_class = $this->component_data['controller_qualified_class'];
      $components[$controller_qualified_class] = array(
        // TODO: Add a sample build method to this.
        'component_type' => 'PHPClassFile',
        'qualified_class_name' => $controller_qualified_class,
      );
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return 'Routing:%module.routing.yml';
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    $path = $this->name;
    $route_name = str_replace('/', '.', $path);

    $route_defaults = [];
    if (!empty($this->component_data['controller']['controller_property'])) {
      $route_defaults[$this->component_data['controller']['controller_property']] = $this->component_data['controller']['controller_value'];
    }
    $route_defaults['_title'] = $this->component_data['title'];

    $routing_data['%module.' . $route_name] = array(
      // Prepend a slash to the path for D8.
      'path' => '/' . $path,
      'defaults' => $route_defaults,
      'requirements' => array(
        '_permission' => 'TODO: set permission machine name',
      ),
    );

    return [
      'route' => [
        'role' => 'yaml',
        'content' => $routing_data,
      ],
    ];
  }

}
