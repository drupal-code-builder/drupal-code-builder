<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for PHP interface files.
 *
 * TODO: extending from class file is hacky and will cause problems if we
 * expect too much of this.
 */
class PHPInterfaceFile extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    // Remove properties that are not relevant.
    $definition->removeProperty('abstract');
    $definition->removeProperty('interfaces');
    $definition->removeProperty('parent_class_name');
    $definition->removeProperty('traits');

    $definition->addProperties([
      'parent_interface_names' => PropertyDefinition::create('string')
        ->setLabel('Parent interface names')
        ->setMultiple(TRUE)
        ->setInternal(TRUE)
    ]);
  }

  /**
   * Produces the interface declaration.
   */
  function classDeclaration() {
    $line = '';
    $line .= "interface {$this->component_data['plain_class_name']}";
    if (!$this->component_data->parent_interface_names->isEmpty()) {
      $line .= ' extends ';
      $line .= implode(', ', $this->component_data['parent_interface_names']);
    }
    $line .= ' {';

    return [
      $line,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    // Override the parent so we don't try to collect traits that aren't a
    // property here.
  }

}
