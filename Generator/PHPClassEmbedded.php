<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\DocBlock;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Data\DataItem;

/**
 * Generator for PHP class files.
 */
class PHPClassEmbedded extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  public function getFileInfo() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return $this->component_data->containing_component->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'fixture_class';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    return $this->phpClassCode();
  }


}
