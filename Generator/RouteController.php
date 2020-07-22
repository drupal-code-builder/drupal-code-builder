<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\VariantDefinition;

/**
 * Generator TODO.
 *
 * REMOVE
 *
 * conceptual.
 */
class RouteController extends BaseGenerator {

  public static function getPropertyDefinition($data_type = 'complex'): PropertyDefinition {
    // No wait, GEnerator def so it knows the class
    return PropertyDefinition::create('mutable')
      ->setProperties([
        'controller_type' => PropertyDefinition::create('string')
          ->setLabel('Controller type')
          // ->setOptionsArray([
          //   'controller' => 'Controller class',
          //   'form' => 'Form',
          //   'entity_view' => 'Entity view mode',
          //   'entity_form' => 'Entity form',
          //   'entity_list' => 'Entity list',
          // ])
      ])
      ->setVariants([
      'controller' => VariantDefinition::create()
        ->setLabel('Controller class')
        ->setProperties([]),
      'form' => VariantDefinition::create()
        ->setLabel('Form')
        ->setProperties([]),
      'entity_view' => VariantDefinition::create()
        ->setLabel('Form')
        ->setProperties([
          // TODO: 4.1
          // Needs entity type data gathering!
          // 'entity_type'
        ]),
      'entity_form' => VariantDefinition::create()
        ->setLabel('Form')
        ->setProperties([]),
      'entity_list' => VariantDefinition::create()
        ->setLabel('Form')
        ->setProperties([]),
      ]);
  }

  public function requiredComponents() {
    $components = [];

    return $components;
  }

}
