<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use DrupalCodeBuilder\Definition\VariantGeneratorDefinition;
use MutableTypedData\Data\MutableData;

class MergeableMutableDataWithArrayAccess extends MutableData implements \ArrayAccess, MergeableDataInterface {

  use DataItemArrayAccessTrait;

  use MergeableComplexDataTrait;

  /**
   * @see \DrupalCodeBuilder\Definition\GeneratorDefinitionInterface::getComponentType()
   */
  public function getComponentType(): string {
    if (is_a($this->getVariantDefinition(), VariantGeneratorDefinition::class)) {
      return $this->getVariantDefinition()->getComponentType();
    }
    else {
      return $this->definition->getComponentType();
    }
  }

}
