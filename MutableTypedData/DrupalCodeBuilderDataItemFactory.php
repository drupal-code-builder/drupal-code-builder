<?php

namespace DrupalCodeBuilder\MutableTypedData;

use DrupalCodeBuilder\ExpressionLanguage\ArrayOperationsExpressionLanguageProvider;
use DrupalCodeBuilder\ExpressionLanguage\ChangeCaseExpressionLanguageProvider;
use DrupalCodeBuilder\MutableTypedData\Data\ComplexDataWithArrayAccess;
use DrupalCodeBuilder\MutableTypedData\Data\MutableDataWithArrayAccess;
use DrupalCodeBuilder\MutableTypedData\Data\MappingData;
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
 * conversions. AND replace classes!!
 */
class DrupalCodeBuilderDataItemFactory extends DataItemFactory {

  /**
   * {@inheritdoc}
   */
  static protected $types = [] + [
    'string' => StringData::class,
    'boolean' => BooleanData::class,
    // Override to allow array access as a backwards-compatibility shim. This
    // is basically just to save having to immediately convert ALL of the code
    // in Generator classes that accesses component data.
    'complex' => ComplexDataWithArrayAccess::class,
    'mutable' => MutableDataWithArrayAccess::class,
    // Mapping data stored arbitrary arrays that don't need to have their
    // structure defined. This is basically for the YAML data.
    'mapping' => MappingData::class,
  ];

  /**
   * {@inheritdoc}
   */
  static protected $expressionLanguageProviders = [
    DataAddressLanguageProvider::class,
    ChangeCaseExpressionLanguageProvider::class,
    // This is only for internal defaults; UIs are not expected to support it.
    // TODO: find a way to segregate this.
    ArrayOperationsExpressionLanguageProvider::class
  ];

}
