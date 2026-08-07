<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\ExpressionLanguage\FrontEndFunctionsProvider;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\DefaultDefinition;

class FrontEndExpressions extends Base {

  // TODO DrupalCodeBuilderDataAddressExpressionLanguage as const.
  const Foo = '';

  protected $sanity_level = 'none';

  /**
   * Produces JavaScript code for handling .... TODO
   *
   * @return string
   *   JS snippet containing a declaration of the DrupalCodebuilderDataAddressExpressionLanguage object. This holds the methods which correspond to expression language functions.
   *
   */
  public function getFrontEndExpressionsCode(): string {
    $code_pieces = [];
    $code_pieces[] = 'var DrupalCodeBuilderDataAddressExpressionLanguage = {';

    // Add a JavaScript version of the function from
    // MutableTypedData\ExpressionLanguage\DataAddressLanguageProvider.
    $code_pieces[] = <<<'EOT'
      get: function(address) {
        let $item = jQuery("input[data-typed-data-address='" + address + "']");

        return $item.val();
      },
    EOT;

    // Get Expression Language functions from the provider.
    $provider = new FrontEndFunctionsProvider();
    $functions = $provider->getHybridFunctions();

    // Get the JavaScript function from each item.
    foreach ($functions as $name => $code_snippets) {
      $code_pieces[] = '  ' . $name . ': ' . $code_snippets['js'];
    }

    $code_pieces[] = '};';

    return implode("\n", $code_pieces);
  }

  public function getDefaultExpressionAsJavaScript(DefaultDefinition $default_definition, DataItem $data): string {
    $expression = $default_definition->getExpressionWithAbsoluteAddresses($data);

    $provider = new FrontEndFunctionsProvider();

    $function_names = array_keys($provider->getHybridFunctions());
    $function_names[] = 'get';

    foreach ($function_names as $function_name) {
      $expression = str_replace("{$function_name}(", "DrupalCodeBuilderDataAddressExpressionLanguage.{$function_name}(", $expression);
    }

    return $expression;
  }

}
