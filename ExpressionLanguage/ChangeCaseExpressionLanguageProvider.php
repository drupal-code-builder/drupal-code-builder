<?php

namespace DrupalCodeBuilder\ExpressionLanguage;

use CaseConverter\CaseString;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Provides Expression Language custom functions for changing case.
 */
class ChangeCaseExpressionLanguageProvider implements ExpressionFunctionProviderInterface {

  public function getFunctions() {
    return [
      new ExpressionFunction('machineToLabel', function ($str) {
        return sprintf('(is_string(%1$s) ? CaseString::snake(%1$s) : %1$s)->title()', $str);
      },
      function ($arguments, $str) {
        if (!is_string($str)) {
          return $str;
        }

        return CaseString::snake($str)->title();
      }),
      new ExpressionFunction('machineToClass', function ($str) {
        return sprintf('(is_string(%1$s) ? CaseString::snake(%1$s) : %1$s)->pascal()', $str);
      },
      function ($arguments, $str) {
        if (!is_string($str)) {
          return $str;
        }

        return CaseString::snake($str)->pascal();
      }),

    ];
  }

}
