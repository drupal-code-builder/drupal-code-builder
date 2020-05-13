<?php

namespace DrupalCodeBuilder\ExpressionLanguage;

use MutableTypedData\Data\DataItem;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Provides Expression Language custom functions for acquired data expressions.
 */
class AcquisitionExpressionLanguageProvider implements ExpressionFunctionProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new ExpressionFunction('getRootComponentName',
        function ($requester) {},
        function ($arguments, DataItem $requester) {
          if ($requester->hasProperty('root_name')) {
            return $requester->root_name->value;
          }
          else {
            return $requester->root_component_name->value;
          }
       }),
    ];
  }

}
