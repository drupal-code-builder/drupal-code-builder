<?php

namespace DrupalCodeBuilder\Utility\CodeAnalysis;

use ReflectionMethod as PHPReflectionMethod;

/**
 * Analyses a method using reflection.
 */
class Method extends PHPReflectionMethod {

  /**
   * Gets the body code of the method.
   *
   * @return string
   *   The body of the method's code.
   */
  public function getBody() {
    $filename = $this->getFileName();
    $start_line = $this->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
    $end_line = $this->getEndLine();
    $length = $end_line - $start_line;
    $file_source = file($filename);
    $body = implode("", array_slice($file_source, $start_line, $length));

    return $body;
  }

  /**
   * Returns an array of parameter names and types.
   *
   * Types are deduced from reflection, with a fallback to the documented type
   * for native types (e.g. 'int') which Drupal doesn't yet typehint for.
   *
   * @return array
   *  A numeric array where each item is an array containing:
   *    - 'type': The typehint for the parameter.
   *    - 'name': The name of the parameter without the leading '$'.
   */
  public function getParamData() {
    $data = [];

    $parameter_reflections = $this->getParameters();
    $docblock_types = $this->getDocblockParams();

    foreach ($parameter_reflections as $parameter_reflection) {
      $name = $parameter_reflection->getName();

      // Get the typehint. We try the reflection first, which gives us class
      // and interface typehints and 'array'. If that gets nothing, we scrape
      // it from the docblock so that we have something to generate docblocks
      // with.
      $type = (string) $parameter_reflection->getType();

      if (empty($type)) {
        if (isset($docblock_types[$name])) {
          $type = $docblock_types[$name];
        }
        else {
          // Account for badly-written docs where we couldn't extract a type.
          $type = '';
        }
      }

      $data[] = [
        'type' => $type,
        'name' => $name,
      ];
    }

    return $data;
  }

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
