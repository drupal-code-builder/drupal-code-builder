<?php

namespace DrupalCodeBuilder\MutableTypedData;

use DrupalCodeBuilder\ExpressionLanguage\InternalFunctionsExpressionLanguageProvider;
use DrupalCodeBuilder\ExpressionLanguage\FrontEndFunctionsProvider;
use DrupalCodeBuilder\MutableTypedData\Data\ComplexDataWithArrayAccess;
use DrupalCodeBuilder\MutableTypedData\Data\MutableDataWithArrayAccess;
use DrupalCodeBuilder\MutableTypedData\Data\MappingData;
use DrupalCodeBuilder\MutableTypedData\Validator\ClassName;
use DrupalCodeBuilder\MutableTypedData\Validator\MachineName;
use DrupalCodeBuilder\MutableTypedData\Validator\Path;
use DrupalCodeBuilder\MutableTypedData\Validator\PluginName;
use DrupalCodeBuilder\MutableTypedData\Validator\ServiceName;
use MutableTypedData\Data\StringData;
use MutableTypedData\Data\BooleanData;
use MutableTypedData\Data\ArrayData;
use MutableTypedData\Data\MutableData;
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
    'string' => StringData::class,
    'boolean' => BooleanData::class,
    // Override to allow array access as a backwards-compatibility shim. This
    // is basically just to save having to immediately convert ALL of the code
    // in Generator classes that accesses component data.
    'complex' => ComplexDataWithArrayAccess::class,
    'mutable' => MutableDataWithArrayAccess::class,
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
    'service_name' => ServiceName::class,
    'path' => Path::class,
  ];

}
