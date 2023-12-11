<?php

namespace DrupalCodeBuilder\Test\Fixtures\Generator\Mogrifier;

use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\VariantDefinition;

class MogrifierMutableComponent extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected static $dataType = 'mutable';

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyDefinition $definition) {
    $definition->setProperties([
      'type' => PropertyDefinition::create('string')
        ->setLabel('Plugin discovery type')
      ])
    ->setVariants([
      'alpha' => VariantDefinition::create()
        ->setLabel('Annotation plugin')
        ->setProperties([
          'alpha_property' => PropertyDefinition::create('string')
            ->setLabel('Alpha property'),
        ]),
      'beta' => VariantDefinition::create()
        ->setLabel('Annotation plugin')
        ->setProperties([
          'beta_property' => PropertyDefinition::create('string')
            ->setLabel('Beta property'),
        ]),
    ]);
  }

}