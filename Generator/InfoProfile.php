<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use Ckr\Util\ArrayMerger;

/**
 * Abstract generator for profile info data.
 */
class InfoProfile extends InfoComponent {

  /**
   * {@inheritdoc}
   */
  protected const INFO_LINE_ORDER = [
    'name',
    'type',
    'description',
    'version',
    'dependencies',
    'install',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $propertiesAcquiredFromRoot = [
    'base',
    'readable_name',
    'short_description',
    'dependencies',
    'install',
  ];

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    // TODO: make auto-acquired work with multiple values.
    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('dependencies')
      ->setMultiple(TRUE)
      ->setAcquiringExpression('requester.dependencies.export()')
    );
    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('install')
      ->setMultiple(TRUE)
      ->setAcquiringExpression('requester.install.export()')
    );
  }

  /**
   * Builds the array of data for the info file.
   *
   * @return array
   *   An array of data whose keys and values correspond to info file
   *   properties. This array may have empty lines, which are preserved in the
   *   return value to allow subclasses to still have the order from
   *   static::INFO_LINE_ORDER. Callers should run the return through
   *   array_filter() to remove these.
   */
  function infoData(): array {
    $lines = $this->getInfoFileEmptyLines();
    $lines['name'] = $this->component_data->readable_name->value;
    $lines['type'] = $this->component_data->base->value;
    $lines['description'] = $this->component_data->short_description->value;
    // For lines which form a set with the same key and array markers,
    // simply make an array.
    foreach ($this->component_data->dependencies as $dependency) {
      $lines['dependencies'][] = $dependency->value;
    }
    foreach ($this->component_data->install as $install) {
      $lines['install'][] = $install->value;
    }

    $lines['core_version_requirement'] = $this->getCoreVersionCompatibilityValue();

    if (!empty($extra_lines = $this->getContainedComponentInfoLines())) {
      $lines = array_merge($lines, $extra_lines);
    }

    return $lines;
  }

}
