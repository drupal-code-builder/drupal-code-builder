<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use Ckr\Util\ArrayMerger;

/**
 * Abstract generator for module info data.
 */
class InfoModule extends InfoComponent {

  /**
   * The filename for the info file.
   *
   * TODO: Remove this when %module token is removed. Too many things rely on
   * this at the moment to allow using the parent class value.
   */
  protected const INFO_FILENAME = '%module.info.yml';

  /**
   * {@inheritdoc}
   */
  protected const INFO_LINE_ORDER = [
    'name',
    'type',
    'description',
    'package',
    'version',
    'lifecycle',
    'experimental',
    'core',
    'core_version_requirement',
    'dependencies',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $propertiesAcquiredFromRoot = [
    'base',
    'readable_name',
    'short_description',
    'module_dependencies',
    'module_package',
    'lifecycle',
  ];

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('module_dependencies')
      ->setMultiple(TRUE)
      ->setAcquiringExpression('requester.module_dependencies.export()')
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
    $module_data = $this->component_data;
    // dump("FILE BODY");
    // dump($module_data->export());

    $lines = $this->getInfoFileEmptyLines();
    $lines['name'] = $module_data->readable_name->value;
    $lines['type'] = $module_data->base->value;
    $lines['description'] = $module_data->short_description->value;
    // For lines which form a set with the same key and array markers,
    // simply make an array.
    foreach ($module_data->module_dependencies as $dependency) {
      $lines['dependencies'][] = $dependency->value;
    }

    if ($module_data->hasProperty('lifecycle') && $module_data->lifecycle->value) {
      $lines['lifecycle'] = $module_data->lifecycle->value;
    }

    if (!$module_data->module_package->isEmpty()) {
      $lines['package'] = $module_data->module_package->value;
    }

    $lines['core_version_requirement'] = $this->getCoreVersionCompatibilityValue();

    if (!empty($extra_lines = $this->getContainedComponentInfoLines())) {
      $lines = array_merge($lines, $extra_lines);
    }

    return $lines;
  }

}
