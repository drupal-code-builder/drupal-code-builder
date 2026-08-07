<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\ExpressionLanguage\FrontEndFunctionsProvider;

class FrontEndExpressions extends Base {

  protected $sanity_level = 'none';

  public function getFrontEndExpressionsCode(): string {
    $provider = new FrontEndFunctionsProvider();
    $functions = $provider->getHybridFunctions();

    $code = '';
    $code_pieces = [];

    // todo namespace!
    $code_pieces[] = 'var DataAddressExpressionLanguage = {';

    foreach ($functions as $name => $code_snippets) {
      $code_pieces[] = '  ' . $name . ': ' . $code_snippets['js'];
    }

    $code_pieces[] = '};';

    // machineToClass: function(value) {
    //   var pieces = value.split('_');
    //   pieces = pieces.map(x => x.charAt(0).toUpperCase() +  x.slice(1));
    //   return pieces.join('');
    // },




    /// return a JS code stuff.
    ///
    /// call getHybridFunctions(), extract JS code and build the code file string
    return implode("\n", $code_pieces);
  }

}
