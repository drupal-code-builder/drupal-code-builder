<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\File\DrupalExtension;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\OptionDefinition;

/**
 * Abstract Generator for root components.
 *
 * Root components are those with which the generating process may begin, such
 * as Module and Theme.
 *
 * These are used by
 * \DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory to
 * instantiate data objects.
 */
abstract class RootComponent extends BaseGenerator implements RootComponentInterface {

  /**
   * The base of this root component.
   *
   * This is expected to match, within case difference, the plain class name
   * of the generator. See self::getDefinition().
   *
   * This is typically the Drupal extension type that it generates.
   *
   * Must be overriden by child classes.
   */
  const BASE = NULL;

  /**
   * The sanity level this generator requires to operate.
   */
  protected static $sanity_level = 'none';

  /**
   * Returns this generator's sanity level.
   *
   * @return string
   *  The sanity level name.
   */
  public static function getSanityLevel() {
    return static::$sanity_level;
  }

  /**
   * {@inheritdoc}
   */
  public static function configurationDefinition(): PropertyDefinition {
    // Return an empty data definition by default.
    // NOTE: this can't have a root name set because it's also embedded into
    // data by self::addToGeneratorDefinition().
    return PropertyDefinition::create('complex');
  }

  /**
   * Implements DefinitionProviderInterface's method.
   */
  public static function getDefinition(): PropertyDefinition {
    $component_type = ucfirst(static::BASE);
    $definition = MergingGeneratorDefinition::createFromGeneratorType($component_type);

    // Root label is set in the component-specific subclass, but name must be
    // set here as it can't be changed by any further subclasses, e.g.
    // Module / TestModule.
    $definition->setName(static::BASE);

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    // Define this here for completeness; child classes should specialize it.
    $definition->addProperties([
      // The type of Drupal extension: 'module'.
      'base' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault(static::BASE)
        ->setRequired(TRUE),
      // The machine name for the extension.
      'root_name' => PropertyDefinition::create('string')
        ->setLabel('Extension machine name')
        ->setValidators('machine_name')
        ->setRequired(TRUE),
      'root_name_pascal' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(fn ($component_data) => CaseString::snake($component_data->getParent()->root_name->value)->pascal()),
    ]);

    // Remove the root_component_name property that's come from the parent
    // class.
    $definition->removeProperty('root_component_name');

    // Override the component_base_path property to be computed rather than
    // inherited.
    $definition->addProperties([
      'component_base_path' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setLiteral('')
        ),
      // Add the configuration data definition as internal.
      'configuration' => static::configurationDefinition()
        ->setInternal(TRUE),
      'lifecycle' => PropertyDefinition::create('string')
        ->setLabel('Lifecycle')
        ->setOptions(
          OptionDefinition::create('experimental', 'Experimental', weight: 0),
          OptionDefinition::create('deprecated', 'Deprecated', weight: 10),
          OptionDefinition::create('obsolete', 'Obsolete', weight: 20),
        ),
    ]);
  }

  /**
   * Gets root component data for an existing Drupal extension.
   *
   * @param \DrupalCodeBuilder\File\DrupalExtension $existing_extension
   *   The existing extension.
   *
   * @return \MutableTypedData\Data\DataItem
   *   The component data for the new root component.
   */
  public static function adoptRootComponent(DrupalExtension $existing_extension): DataItem {
    $info_file_data = $existing_extension->getFileYaml($existing_extension->name . '.info.yml');

    $value = [
      'base' => $existing_extension->type,
      'root_name' => $existing_extension->name,
      'readable_name' => $info_file_data['name'],
      'short_description' => $info_file_data['description'] ?? '',
    ];

    $data = DrupalCodeBuilderDataItemFactory::createFromProvider(static::class);
    $data->import($value);

    return $data;
  }

  public function isRootComponent(): bool {
    return TRUE;
  }

  /**
   * Filter the file info array to just the requested build list.
   *
   * @param &$files
   *  The array of built file info.
   * @param $build_list
   *  The build list parameter from the original Generate component data.
   * @param $component_data
   *  The original component data.
   */
  public function applyBuildListFilter(&$files, $build_list, $component_data) {
  }

  /**
   * {@inheritdoc}
   */
  function getReplacements() {
    // Root components should override this.
    return [];
  }

}
