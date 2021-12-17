<?php

namespace DrupalCodeBuilder\MutableTypedData;

use DrupalCodeBuilder\ExpressionLanguage\InternalFunctionsExpressionLanguageProvider;
use DrupalCodeBuilder\ExpressionLanguage\FrontEndFunctionsProvider;
use DrupalCodeBuilder\MutableTypedData\Data\MergeableComplexDataWithArrayAccess;
use DrupalCodeBuilder\MutableTypedData\Data\MergeableMutableDataWithArrayAccess;
use DrupalCodeBuilder\MutableTypedData\Data\MappingData;
use DrupalCodeBuilder\MutableTypedData\Data\MergeableArrayData;
use DrupalCodeBuilder\MutableTypedData\Data\MergeableBooleanData;
use DrupalCodeBuilder\MutableTypedData\Data\MergeableStringData;
use DrupalCodeBuilder\MutableTypedData\Validator\ClassName;
use DrupalCodeBuilder\MutableTypedData\Validator\MachineName;
use DrupalCodeBuilder\MutableTypedData\Validator\Path;
use DrupalCodeBuilder\MutableTypedData\Validator\PluginExists;
use DrupalCodeBuilder\MutableTypedData\Validator\PluginName;
use DrupalCodeBuilder\MutableTypedData\Validator\ServiceName;
use DrupalCodeBuilder\MutableTypedData\Validator\YamlPluginName;
use MutableTypedData\Data\MutableData;
use MutableTypedData\Data\DataItem;
use MutableTypedData\DataItemFactory;
use MutableTypedData\Definition\DataDefinition;
use MutableTypedData\Exception\InvalidDefinitionException;
use MutableTypedData\ExpressionLanguage\DataAddressLanguageProvider;

/**
 * Provides a custom Mutable Typed Data Item factory.
 *
 * This allows us to add our own Expression Language functions for case
 * conversions and replace data item classes.
 */
class DrupalCodeBuilderDataItemFactory extends DataItemFactory {

  /**
   * {@inheritdoc}
   */
  static protected $types = [
    'string' => MergeableStringData::class,
    'boolean' => MergeableBooleanData::class,
    // Override to allow array access as a backwards-compatibility shim. This
    // is basically just to save having to immediately convert ALL of the code
    // in Generator classes that accesses component data.
    'complex' => MergeableComplexDataWithArrayAccess::class,
    'mutable' => MergeableMutableDataWithArrayAccess::class,
    // Mapping data stores arbitrary arrays that don't need to have their
    // structure defined. This is basically for the YAML data.
    'mapping' => MappingData::class,
  ];

  /**
   * {@inheritdoc}
   */
  static protected $expressionLanguageProviders = [
    DataAddressLanguageProvider::class,
    FrontEndFunctionsProvider::class,
    // This is only for internal defaults; UIs are not expected to support it.
    // TODO: find a way to segregate this.
    InternalFunctionsExpressionLanguageProvider::class
  ];

  /**
   * {@inheritdoc}
   */
  static protected $validators = [
    'class_name' => ClassName::class,
    'machine_name' => MachineName::class,
    'plugin_name' => PluginName::class,
    'plugin_exists' => PluginExists::class,
    'yaml_plugin_name' => YamlPluginName::class,
    'service_name' => ServiceName::class,
    'path' => Path::class,
  ];

  // TODO: this exists only to override ArrayData. Move ability to do this
  // upstream!
  public static function createFromDefinition(DataDefinition $definition, DataItem $parent = NULL, int $delta = NULL): DataItem {
    // Ensure a machine name.
    if ($parent) {
      $machine_name = $definition->getName();

      // Allow an empty machine name if this is a delta.
      // TODO: this is inconsistent, as this only happens with a root that has
      // no name! See TODO on ArrayData::createItem().
      if (is_null($machine_name) && is_int($delta)) {
        $machine_name = $delta;
      }

      if (!is_string($machine_name) && !is_numeric($machine_name)) {
        throw new InvalidDefinitionException("Machine name must be a string or number.");
      }

      //  We allow a 0 machine name because the first delta in a multiple value
      //  will be 0.
      if (is_null($machine_name)) {
        throw new InvalidDefinitionException("Non-root properties must have a machine name.");
      }
    }

    if ($definition->isMultiple()) {
      $item = new MergeableArrayData($definition);
    }
    else {
      if (!isset(static::$types[$definition->getType()])) {
        throw new InvalidDefinitionException(sprintf("Unknown data type '%s' at '%s'.",
          $definition->getType(),
          $definition->getName()
        ));
      }

      $class = static::$types[$definition->getType()];

      $item = new $class($definition);
    }

    if ($parent) {
      $item->setParent($parent, $delta);
    }

    $item->setFactory(static::class);

    return $item;
  }

}
