<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\PHPFunction.
 */

namespace ModuleBuider\Generator;

/**
 * Generator base class for functions.
 *
 * (We can't call this 'Function', as that's a reserved word.)
 */
class PHPFunction extends Base {

  /**
   * The code file this function belongs in.
   *
   * This is in the form of a relative path to the module folder, with
   * placeholders such as '%module'.
   * TODO: add a @see to the function that does placeholder replacement.
   */
  public $code_file;

  /**
   * Called by ModuleCodeFile to collect functions from its child components.
   *
   * @return
   *  An array keyed by function name (placeholders allowed), whose properties
   *  are:
   *    - 'doxygen_first': The text of the first line of doxygen.
   *    - 'declaration': The function declaration, including the function name
   *      and parameters, up to the closing parenthesis. Should not however
   *      include the opening brace of the function body.
   *    - 'body' The code of the function, not including the function's
   *      enclosing braces.
   */
  public function componentFunctions() {
    return array();
  }

}
