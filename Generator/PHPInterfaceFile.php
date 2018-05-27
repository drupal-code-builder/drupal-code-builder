<?php

namespace DrupalCodeBuilder\Generator;

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
  public static function componentDataDefinition() {
    $definition = parent::componentDataDefinition();

    // Remove properties that are not relevant.
    unset($definition['abstract']);
    unset($definition['interfaces']);
    unset($definition['parent_class_name']);
    unset($definition['traits']);

    $definition['parent_interface_names'] = [
      'label' => 'Parent interface names',
      'format' => 'array',
      'internal' => TRUE,
      'default' => [],
    ];

    return $definition;
  }

  /**
   * Produces the interface declaration.
   */
  function class_declaration() {
    $line = '';
    $line .= "interface {$this->component_data['plain_class_name']}";
    if ($this->component_data['parent_interface_names']) {
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
