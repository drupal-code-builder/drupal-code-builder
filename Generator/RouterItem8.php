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
   * The component name is taken to be the fully-qualified class name.
   */
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    // Create a controller name from the route name.
    $controller_class_name = $this->toCamel($component_name) . 'Controller';
    $controller_qualified_class_name = implode('\\', [
      'Drupal',
      '%module',
      'Controller',
      $controller_class_name
    ]);

    $component_data += [
      'controller_qualified_class' => $controller_qualified_class_name,
      'controller_method' => 'content',
    ];

    parent::__construct($component_name, $component_data, $generate_task, $root_generator);
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

    $controller_qualified_class = $this->component_data['controller_qualified_class'];
    $components[$controller_qualified_class] = array(
      // Add a sample build method to this.
      'component_type' => 'PHPClassFile',
    );

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

    $routing_data['%module.' . $route_name] = array(
      // Prepend a slash to the path for D8.
      'path' => '/' . $path,
      'defaults' => array(
        '_controller' => '\\' . $this->component_data['controller_qualified_class'] .
          '::' . $this->component_data['controller_method'],
        '_title' => $this->component_data['title'],
      ),
      'requirements' => array(
        '_permission' => 'TODO: set permission machine name',
      ),
    );

    return $routing_data;
  }

}
