<?php

namespace DrupalCodeBuilder\ExpressionLanguage;

use CaseConverter\CaseString;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Provides Expression Language custom functions for changing case.
 *
 * TODO: rename this so it's about common front-end/backend functions.
 *
 * TODO: implement compiling, as these get used LOTS!
 */
class ChangeCaseExpressionLanguageProvider implements ExpressionFunctionProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      // Converts a machine name in snake case to a label in title case.
      new ExpressionFunction('machineToLabel', function ($str) {
        return sprintf('(is_string(%1$s) ? CaseString::snake(%1$s) : %1$s)->title()', $str);
      },
      function ($arguments, $str) {
        if (!is_string($str)) {
          return $str;
        }

        return CaseString::snake($str)->title();
      }),
      // Converts a machine name in snake case to a pascal case class name.
      new ExpressionFunction('machineToClass', function ($str) { },
      function ($arguments, $str) {
        if (!is_string($str)) {
          return $str;
        }

        return CaseString::snake($str)->pascal();
      }),
      new ExpressionFunction('stripBefore', function ($string, $marker) { },
      function ($arguments, $string, $marker) {
        if (strpos($string, $marker) === FALSE) {
          return $string;
        }

        $pieces = explode($marker, $string, 2);
        return $pieces[1];
      })
    ];
  }

}
