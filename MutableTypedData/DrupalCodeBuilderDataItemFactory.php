<?php

namespace DrupalCodeBuilder\MutableTypedData;

use MutableTypedData\DataItemFactory;
use MutableTypedData\ExpressionLanguage\DataAddressLanguageProvider;
use DrupalCodeBuilder\ExpressionLanguage\ChangeCaseExpressionLanguageProvider;

class DrupalCodeBuilderDataItemFactory extends DataItemFactory {

  static protected $expressionLanguageProviders = [
    DataAddressLanguageProvider::class,
    ChangeCaseExpressionLanguageProvider::class,
  ];

}
