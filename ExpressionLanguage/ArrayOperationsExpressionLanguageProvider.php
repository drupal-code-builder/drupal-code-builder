<?php

namespace DrupalCodeBuilder\ExpressionLanguage;

use MutableTypedData\Data\DataItem;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Provides Expression Language custom functions for acquired data expressions.
 */
class ArrayOperationsExpressionLanguageProvider implements ExpressionFunctionProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new ExpressionFunction('arrayAppend',
        function (array $array, string $suffix) {},
        function ($arguments, array $array, string $suffix) {
          $array[] = $suffix;
          return $array;
       }),
      new ExpressionFunction(
        'arrayMerge',
        function (array $array1, array $array2) {
        },
        function ($arguments, array $array1, array $array2) {
          return array_merge($array1, $array2);
        }
      ),
      new ExpressionFunction('arrayLast',
        function (array $array) {},
        function ($arguments, array $array) {
          return end($array);
       }),
      ExpressionFunction::fromPhp('implode'),
      ExpressionFunction::fromPhp('explode'),
      // FFS TEMP!
      new ExpressionFunction(
        'relativeClassName',
        function (string $relative_namespace, string $plain_classname) {
        },
        function ($arguments, string $relative_namespace, string $plain_classname) {
          if ($relative_namespace) {
            $relative_namespace .= '\\';
          }
          return $relative_namespace . $plain_classname;
        }
      ),
      // TODO: rename this class operations, and change all functions from
      // array-ish to class name manipulation??
      // This also works with relative qualified class names.
      new ExpressionFunction('plainClassNameFromQualified',
        function (string $qualified_class_name) {},
        function ($arguments, string $qualified_class_name) {
          $pieces = explode('\\', $qualified_class_name);
          return end($pieces);
       }),
    ];
  }

}
