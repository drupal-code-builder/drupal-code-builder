<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\ExpressionLanguage\FrontEndFunctionsProvider;

class FrontEndExpressions extends Base {

  protected $sanity_level = 'none';

  public function getFrontEndExpressionsCode(): string {
    $provider = new FrontEndFunctionsProvider();
    $functions = $provider->getHybridFunctions();
    /// return a JS code stuff.
    ///
    /// call getHybridFunctions(), extract JS code and build the code file string
    return '';
  }

}
