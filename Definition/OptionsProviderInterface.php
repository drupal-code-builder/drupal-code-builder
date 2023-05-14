<?php

namespace DrupalCodeBuilder\Definition;

/**
 * Interface for providing option definitions.
 */
interface OptionsProviderInterface {

  /**
   * Provides options for a property.
   *
   * @return \MutableTypedData\Definition\OptionDefinition[]
   *   An array of option definitions, keyed by the option ID.
   */
  public function getOptions(): array;

}
