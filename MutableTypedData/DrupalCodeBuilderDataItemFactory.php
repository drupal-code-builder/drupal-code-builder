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
use DrupalCodeBuilder\MutableTypedData\Validator\FormReference;
use DrupalCodeBuilder\MutableTypedData\Validator\MachineName;
use DrupalCodeBuilder\MutableTypedData\Validator\Path;
use DrupalCodeBuilder\MutableTypedData\Validator\PluginExists;
use DrupalCodeBuilder\MutableTypedData\Validator\PluginName;
use DrupalCodeBuilder\MutableTypedData\Validator\ServiceName;
use DrupalCodeBuilder\MutableTypedData\Validator\YamlPluginName;
use MutableTypedData\DataItemFactory;
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
  static protected $multipleData = MergeableArrayData::class;

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
    'form_ref' => FormReference::class,
    'yaml_plugin_name' => YamlPluginName::class,
    'service_name' => ServiceName::class,
    'path' => Path::class,
  ];

}
