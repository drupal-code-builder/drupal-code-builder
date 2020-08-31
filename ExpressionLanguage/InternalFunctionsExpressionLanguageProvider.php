<?php

namespace DrupalCodeBuilder\ExpressionLanguage;

use CaseConverter\CaseString;
use MutableTypedData\Data\DataItem;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Provides Expression Language custom functions for internal expressions.
 *
 * TODO WTF are all these AfromB? makes no fucking sense!! AtoB surely!
 */
class InternalFunctionsExpressionLanguageProvider implements ExpressionFunctionProviderInterface {

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
      // TODO: rename this class operations, and change all functions from
      // array-ish to class name manipulation??
      // This also works with relative qualified class names.
      new ExpressionFunction('plainClassNameFromQualified',
        function (string $qualified_class_name) {},
        function ($arguments, string $qualified_class_name) {
          $pieces = explode('\\', $qualified_class_name);
          return end($pieces);
       }),
      new ExpressionFunction(
        'pathFromQualifiedClassNamePieces',
        function (array $qualified_class_name_pieces) {},
        function ($arguments, array $qualified_class_name_pieces) {
          // Lop off the initial Drupal\module and the final class name to
          // build the path.
          $path_pieces = array_slice($qualified_class_name_pieces, 2, -1);
          // Add the initial src to the front.
          array_unshift($path_pieces, 'src');
          // dump($path_pieces);
          // crash();

          return implode('/', $path_pieces);
      }),
      new ExpressionFunction(
        'namespaceFromPieces',
        function (array $qualified_class_name_pieces) {
        },
        function ($arguments, array $qualified_class_name_pieces) {
          // Lop off the the final class name.
          $path_pieces = array_slice($qualified_class_name_pieces, 0, -1);

          return implode('\\', $path_pieces);
        }
      ),
      new ExpressionFunction(
        'machineFromPlainClassName',
        function (string $plain_classname) {
        },
        function ($arguments, string $plain_classname) {
          return CaseString::pascal($plain_classname)->snake();
        }
      ),
      //
    ];
  }

}
