<?php

namespace DrupalCodeBuilder\MutableTypedData;

use MutableTypedData\DataItemFactory;
use MutableTypedData\ExpressionLanguage\DataAddressLanguageProvider;
use DrupalCodeBuilder\ExpressionLanguage\ChangeCaseExpressionLanguageProvider;

/**
 * Provides a custom Mutable Typed Data Item factory.
 *
 * This allows us to add our own Expression Language functions for case
 * conversions.
 */
class DrupalCodeBuilderDataItemFactory extends DataItemFactory {

  /**
   * {@inheritdoc}
   */
  static protected $expressionLanguageProviders = [
    DataAddressLanguageProvider::class,
    ChangeCaseExpressionLanguageProvider::class,
  ];

}
