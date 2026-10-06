<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Exception\InvalidComponentDefinitionException;
use DrupalCodeBuilder\ExpressionLanguage\FrontEndFunctionsProvider;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Provides JavaScript code for front-ends to set defaults.
 *
 * This allows front-ends to use dynamically provide default values in
 * JavaScript for properties where the default value depends on other
 * user-entered data.
 *
 * To use this, a front end needs to:
 *  - Include the JavaScript code from static::getFrontEndExpressionsCode().
 *  - Add a method get() to the method-holding JavaScript object (see below for
 *    details).
 *  - Pass default definitions to static::getDefaultExpressionAsJavaScript() to
 *    get the equivalent JavaScript code.
 *
 * Expression Language expressions are (fairly naively!) converted to JavaScript
 * by:
 *  - Prefixing all Expression Language function calls with the name of the
 *    object which provides their JavaScript equivalents.
 *  - Converting Expression Language string concatenation to JavaScript string
 *    concatenation.
 *
 * @see DrupalCodeBuilder\ExpressionLanguage\FrontEndFunctionsProvider
 */
class FrontEndExpressions extends Base {

  /**
   * {@inheritdoc}
   */
  protected $sanity_level = 'none';

  /**
   * The name of the JavaScript object used to hold methods.
   */
  const string JAVASCRIPT_OBJECT_NAME = 'DrupalCodeBuilderDataAddressExpressionLanguage';


  /**
   * Produces JavaScript code for Expression Language functions.
   *
   * This provides a JavaScript snippet containing a declaration of the
   * DrupalCodebuilderDataAddressExpressionLanguage object, which holds
   * JavaScript methods which correspond to Expression Language functions.
   *
   * It is the responsibility of front-ends to add to this object a method
   * get(address), which returns the value of data item given the full address
   * and is the equivalent of
   * \MutableTypedData\ExpressionLanguage\DataAddressLanguageProvider's get().
   * This is not provided here, as its operation will depend on the structure of
   * the front-end form.
   *
   * @return string
   *   A JS snippet containing a declaration of the
   *   DrupalCodebuilderDataAddressExpressionLanguage object.
   */
  public function getFrontEndExpressionsCode(): string {
    $code_pieces = [];
    $code_pieces[] = 'var ' . static::JAVASCRIPT_OBJECT_NAME . ' = {';

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

  /**
   * Converts a default expression into JavaScript.
   *
   * This is fairly naive and will not handle anything too complex!
   *
   * The returned JavaScript code expects the code from
   * self::getFrontEndExpressionsCode() to also be present, as well as the
   * DrupalCodebuilderDataAddressExpressionLanguage.get() method.
   *
   * @param \MutableTypedData\Definition\DefaultDefinition $default_definition
   *   The default definition.
   * @param \MutableTypedData\Data\DataItem $data
   *   The data item the default is on.
   *
   * @return string
   *   The default expression, converted to JavaScript.
   *
   * @throws \DrupalCodeBuilder\Exception\InvalidComponentDefinitionException
   *   Throws an exception if the given default definition does not use an
   *   expression.
   */
  public function getDefaultExpressionAsJavaScript(DefaultDefinition $default_definition, DataItem $data): string {
    if ($default_definition->getType() != 'expression') {
      throw new InvalidComponentDefinitionException(sprintf("Default used on non-internal property %s does not use an expression.", $data->getAddress()));
    }

    $expression = $default_definition->getExpressionWithAbsoluteAddresses($data);

    $provider = new FrontEndFunctionsProvider();

    // Get a list of the Expression Language front-end function names.
    $function_names = array_keys($provider->getHybridFunctions());
    // Also handle calls to get(), which front-ends are expected to define.
    $function_names[] = 'get';

    // Replace all function calls with a call on the JavaScript object which
    // holds the equivalent methods.
    foreach ($function_names as $function_name) {
      $expression = str_replace("{$function_name}(", static::JAVASCRIPT_OBJECT_NAME . '.' . $function_name . '(', $expression);
    }

    // Convert string concatenation.
    $expression = str_replace(' ~ ', ' + ', $expression);

    return $expression;
  }

}
