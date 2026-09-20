<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Attribute\DrupalCoreVersion;
use DrupalCodeBuilder\Attribute\RelatedBaseClass;
use DrupalCodeBuilder\Generator\Render\Docblock;

/**
 * Component generator: PHPUnit test class for Drupal 10 and lower.
 *
 * This also generates the PHPUnit attributes from the parent class for
 * forward-compatibility.
 */
#[DrupalCoreVersion(10)]
#[DrupalCoreVersion(9)]
#[DrupalCoreVersion(8)]
#[RelatedBaseClass('PHPUnitTest')]
class PHPUnitTestWithAnnotations extends PHPUnitTest {

  /**
   * {@inheritdoc}
   */
  protected function getClassDocBlock(): DocBlock {
    $docblock = parent::getClassDocBlock();

    $docblock->group('%module');

    return $docblock;
  }

}
