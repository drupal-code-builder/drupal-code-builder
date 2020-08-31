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
            $return = $requester->root_name->value;

            // Getting the root component name should always result in a value,
            // so complain if not.
            if (empty($return)) {
              throw new \Exception(sprintf("Got empty root_name from requester %s",
                $requester->getAddress()
              ));
            }
          }
          else {
            $return = $requester->root_component_name->value;

            if (empty($return)) {
              // dump($requester);
              throw new \Exception(sprintf(
                "Got empty root_component_name from requester %s",
                $requester->getAddress()
              ));
            }
          }

          return $return;
       }),
    ];
  }

}
