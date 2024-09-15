<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for router item for forms on D8 and higher.
 */
class RouterItemForm extends RouterItem {

  use NameFormattingTrait;

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('controller')
      ->setInternal(TRUE);

    $definition->getProperty('controller')->getProperty('controller_type')
      ->setLiteralDefault('form')
      ->setInternal(TRUE);

    $definition->getProperty('controller')->getVariants()['form']->getProperty('form_class')
      ->setInternal(TRUE)
      ->setExpressionDefault("'\\\\' ~ get('..:..:..:qualified_class_name')");
  }

}
