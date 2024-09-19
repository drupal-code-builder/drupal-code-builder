<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use CaseConverter\CaseString;
use DrupalCodeBuilder\Attribute\DrupalCoreVersion;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PresetDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\OptionDefinition;

/**
 * Generator for a class holding Drus commands.
 */
class DrushCommandsClass extends PHPClassFileWithInjection {

  /**
   * {@inheritdoc}
   */
  protected $hasStaticFactoryMethod = TRUE;

  /**
   * {@inheritdoc}
   */
  protected string $containerInterface = '\\Psr\\Container\\ContainerInterface';

}
