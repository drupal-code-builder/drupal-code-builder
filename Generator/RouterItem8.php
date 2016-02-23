<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\RouterItem8.
 */

namespace ModuleBuilder\Generator;

/**
 * Generator for router item on Drupal 8.
 *
 * This adds a routing item to the routing component.
 */
class RouterItem8 extends RouterItem {

  /**
   * Declares the subcomponents for this component.
   *
   * @return
   *  An array of subcomponent names and types.
   */
  protected function requiredComponents() {
    return array(
      // Each RouterItem that gets added will cause a repeat request of these
      // components.
      '%module.routing.yml' => array(
        'component_type' => 'Routing',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%module.routing.yml';
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
    $path = $this->name;
    $route_name = str_replace('/', '.', $path);

    $routing_data[$route_name] = array(
      // Prepend a slash to the path for D8.
      'path' => '/' . $path,
      'defaults' => array(
        '_title' => $this->component_data['title'],
      ),
      'requirements' => array(
        '_permission' => 'TODO: set permission machine name',
      ),
    );

    return $routing_data;
  }

}
