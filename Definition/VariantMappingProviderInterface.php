<?php

namespace DrupalCodeBuilder\Definition;

/**
 * Interface for providing a variant mapping.
 */
interface VariantMappingProviderInterface {

  public function getVariantMapping(): array;

}
