<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\File\DrupalExtension;
use MutableTypedData\Data\DataItem;

/**
 * Interface for generators which support adopting existing components.
 *
 * @see \DrupalCodeBuilder\Task\Adopt
 */
interface AdoptableInterface {

  /**
   * Finds adoptable components of this generator's type.
   *
   * @param \DrupalCodeBuilder\File\DrupalExtension $extension
   *   The existing extension to analyse.
   *
   * @return string[]
   *   An array of identifiers of components of this generator's type in the
   *   existing extension which can be adopted. The identifiers need not be
   *   related to anything else, but must uniquely identify a component and be
   *   recognised by static::adoptComponent().
   */
  public static function findAdoptableComponents(DrupalExtension $extension): array;

  /**
   * Adopts a component from an existing extension.
   *
   * @param \MutableTypedData\Data\DataItem $component_data
   *   The existing root component data. Values should be added to this for the
   *   component being adopted.
   * @param \DrupalCodeBuilder\File\DrupalExtension $extension
   *   The existing extension to analyse.
   * @param string $property_name
   *   The address in the component data of the property to adopt the component
   *   into.
   * @param string $name
   *   The identifier of the component to adopt. This must be a value returned
   *   from static::findAdoptableComponents().
   */
  public static function adoptComponent(DataItem $component_data, DrupalExtension $extension, string $property_name, string $name): void;

}
