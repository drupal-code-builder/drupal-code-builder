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
        'routing_items' => array(
          array(
            // TODO: further items.
            'path' => $this->name,
            'title' => $this->component_data['title'],
          ),
        ),
      ),
    );
  }

}
