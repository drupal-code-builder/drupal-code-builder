<?php

namespace DrupalCodeBuilder\Utility\CodeAnalysis;

use ReflectionMethod as PHPReflectionMethod;

/**
 * Analyses a method using reflection.
 *
 * TODO: move code analysis methods from \Task\Collect\CodeAnalyser to here.
 */
class Method extends PHPReflectionMethod {

  /**
   * Extract parameter types from the docblock.
   *
   * @return
   *   An array keyed by the parameter name (without the initial $), whose
   *   values are the type in the docblock, such as 'mixed', 'int', or an
   *   interface. (Although note that interface typehints which are also used
   *   in the actual code are best detected with reflection).
   */
  public function getDocblockParams() {
    $docblock = $this->getDocComment();

    $matches = [];
    preg_match_all('/\* @param (?P<type>\S+) \$(?P<name>\w+)/', $docblock, $matches, PREG_SET_ORDER);

    // TODO: complain if no match.

    $param_data = [];
    foreach ($matches as $match_set) {
      $param_data[$match_set['name']] = $match_set['type'];
    }

    return $param_data;
  }


}
