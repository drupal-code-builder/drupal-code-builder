<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator\Mogrifier;

use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\VariantDefinition;

class MogrifierMutableComponent extends BaseGenerator {

  protected static $dataType = 'mutable';

  public static function addProperties(PropertyDefinition $definition) {
    $definition->setProperties([
      'type' => PropertyDefinition::create('string')
        ->setLabel('Plugin discovery type')
      ])
    ->setVariants([
      'alpha' => VariantDefinition::create()
        ->setLabel('Annotation plugin')
        ->setProperties([
          'plugin_type' => PropertyDefinition::create('string')
            ->setLabel('Plugin type ID')
            ->setDescription("The identifier of the plugin type. This is used to form the name of the manager service by prepending 'plugin.manager.'.")
            ->setRequired(TRUE)
            ->setValidators('machine_name'),
        ]),
      'beta' => VariantDefinition::create()
        ->setLabel('Annotation plugin')
        ->setProperties([
          'plugin_type' => PropertyDefinition::create('string')
            ->setLabel('Plugin type ID')
            ->setDescription("The identifier of the plugin type. This is used to form the name of the manager service by prepending 'plugin.manager.'.")
            ->setRequired(TRUE)
            ->setValidators('machine_name'),
        ]),
    ]);


    // $definition->addProperties([
    //   // 'string_property' => PropertyDefinition::create('string'),
    // ]);
  }

}