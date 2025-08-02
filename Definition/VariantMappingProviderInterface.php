<?php

namespace DrupalCodeBuilder\Definition;

/**
 * Interface for providing a variant mapping.
 */
interface VariantMappingProviderInterface {

  /**
   * Gets the variant mapping.
   *
   * @return array
   *   An array in the same format as
   *   \MutableTypedData\Definition\DataDefinition::setVariantMapping().
   */
  public function getVariantMapping(): array;

}
