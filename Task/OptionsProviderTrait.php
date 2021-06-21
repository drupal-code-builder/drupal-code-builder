<?php

namespace DrupalCodeBuilder\Task;

use MutableTypedData\Definition\OptionDefinition;

/**
 * Trait implementing \DrupalCodeBuilder\Definition\OptionsProviderInterface.
 *
 * Expects using class to define the static property $optionsMethod.
 *
 * TODO: standardize the method for array options so this property is not
 * needed.
 */
trait OptionsProviderTrait {

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    $options = [];
    foreach ($this->{static::$optionsMethod}() as $id => $label) {
      $options[$id] = OptionDefinition::create($id, $label);
    }

    return $options;
  }

}
