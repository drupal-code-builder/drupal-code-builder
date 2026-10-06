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
   * Defines custom functions as both PHP and JavaScript code.
   *
   * @return array
   *   An array keyed by the function name. Values are an array with keys:
   *   - php: The PHP callable suitable for use as a constructor parameter to
   *     ExpressionFunction.
   *   - js: The JavaScript function.
   */
  public function getHybridFunctions(): array {
    return [
      // Converts a machine name in snake case to a label in title case.
      'machineToLabel' => [
        'php' => function ($arguments, $str) {
          if (!is_string($str)) {
            return $str;
          }

          return CaseString::snake($str)->title();
        },
        // The JavaScript 'function' keyword needs to not be indented at all, as
        // it will get glued to the function declaration in the final code.
        'js' => <<<EOT
        function(value) {
            var pieces = value.split('_');
            pieces = pieces.map(x => x.charAt(0).toUpperCase() +  x.slice(1));
            return pieces.join(' ');
          },
        EOT,
      ],

      // Converts a machine name in snake case to a pascal case class name.
      'machineToClass' => [
        'php' => function ($arguments, $str) {
          if (!is_string($str)) {
            return $str;
          }

          return CaseString::snake($str)->pascal();
        },
        'js' => <<<EOT
        function(value) {
            var pieces = value.split('_');
            pieces = pieces.map(x => x.charAt(0).toUpperCase() +  x.slice(1));
            return pieces.join('');
          },
        EOT,
      ],

      // Converts a pascal class name to a machine name in snake case.
      // Note that this doesn't need to be implemented in JS (yet!) because it's
      // only used during a form submit, in TestModule.
      'classToMachine' => [
        'php' => function ($arguments, $str) {
          if (!is_string($str)) {
            return $str;
          }

          return CaseString::pascal($str)->snake();
        },
        'js' => <<<EOT
        function(value) {
            // Not yet in use.
          },
        EOT,
      ],

      // Removes the portion of the given string before the marker.
      // For example, from 'prefix:main' get 'main'.
      'stripBefore' => [
        'php' => function ($arguments, $string, $marker) {
          if (strpos($string, $marker) === FALSE) {
            return $string;
          }

          $pieces = explode($marker, $string, 2);
          return $pieces[1];
        },
        'js' => <<<EOT
        function(string, marker) {
            return string.substring(string.indexOf(marker) + 1);
          },
        EOT,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    $functions = [];

    // Get the PHP callables from the hybrid function definitions.
    foreach ($this->getHybridFunctions() as $name => $data) {
      $callable = $data['php'];

      $functions[] = new ExpressionFunction(
        $name,
        fn () => NULL,
        $callable,
      );
    }

    return $functions;
  }

}
