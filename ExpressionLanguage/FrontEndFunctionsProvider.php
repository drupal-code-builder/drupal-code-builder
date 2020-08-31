<?php

namespace DrupalCodeBuilder\ExpressionLanguage;

use CaseConverter\CaseString;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Provides Expression Language custom functions that front ends may implement.
 *
 * TODO: implement compiling, as these get used LOTS!
 */
class FrontEndFunctionsProvider implements ExpressionFunctionProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      // Converts a machine name in snake case to a label in title case.
      new ExpressionFunction('machineToLabel', function ($str) { },
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

      // Converts a pascal class name to a machine name in snake case.
      // Note that this doesn't need to be implemented in Module Builder's JS
      // (yet!) because it's only used during a form submit, in TestModule.
      new ExpressionFunction('classToMachine', function ($str) { },
      function ($arguments, $str) {
        if (!is_string($str)) {
          return $str;
        }

        return CaseString::pascal($str)->snake();
      }),

      // Removes the portion of the given string before the marker.
      // For example, from 'prefix:main' get 'main'.
      new ExpressionFunction('stripBefore', function ($string, $marker) { },
      function ($arguments, $string, $marker) {
        if (strpos($string, $marker) === FALSE) {
          return $string;
        }

        $pieces = explode($marker, $string, 2);
        return $pieces[1];
      }),

    ];
  }

}
