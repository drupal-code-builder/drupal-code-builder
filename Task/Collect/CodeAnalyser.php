<?php

namespace DrupalCodeBuilder\Task\Collect;

/**
 * Task helper for analysing PHP code.
 */
class CodeAnalyser {

  /**
   * Determines whether a class may be instantiated safely.
   *
   * This checks whether the class exists, and whether its parent also exists.
   *
   * This is for use with services which one module may define as tagged for
   * use by another module for collection. If the collecting module is not
   * enabled, we have no way of detecting that the service's tag is a service
   * collector tag.
   *
   * @param string $qualified_classname
   *   The fully-qualified class name, without the initial \.
   *
   * @return boolean
   *   TRUE if the class may be used; FALSE if the class should not be used as
   *   attempting to will cause a PHP fatal error.
   */
  public function classIsUsable($qualified_classname) {
    // To find the parent class without loading the class, we hack the code
    // with regex.
    // TODO: In future, use roave/better-reflection. This is not currently usable
    // as the 1.0 version conflicts with Drupal dependencies, and the 2.0 version
    // requires PHP 7.1 which is rather high for use in a Drupal site.

    // Use the Drupal class finder, which gets us the filename for a class
    // from the autoloader.
    $class_finder = new \Drupal\Component\ClassFinder\ClassFinder;

    $filepath = $class_finder->findFile($qualified_classname);

    if (empty($filepath)) {
      return FALSE;
    }

    $class_code = file_get_contents($filepath);

    // Get the parent class name from the class declaration.
    $classname_pieces = explode('\\', $qualified_classname);
    $short_classname = end($classname_pieces);
    $matches = [];
    preg_match("@class {$short_classname} extends (\w+)@", $class_code, $matches);

    if (empty($matches)) {
      // The class has no parent, and the autoloader found the file.
      return TRUE;
    }

    $parent_short_classname = $matches[1];

    // Find the full class name for this from the import statements.
    $matches = [];
    // TODO: handle aliased imports.
    preg_match("@use ([\\\\\\w]+\\\\{$parent_short_classname});@", $class_code, $matches);

    if (empty($matches)) {
      // TODO: use the namespace of the file!
      return FALSE;
    }

    $parent_qualified_classname = $matches[1];

    if (class_exists($parent_qualified_classname)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Gets the body code of a method.
   *
   * @param \ReflectionMethod $method_reflection
   *   The reflection class for the method.
   *
   * @return string
   *   The body of the method's code.
   */
  public function getMethodBody(\ReflectionMethod $method_reflection) {
    // Get the method's body code.
    // We could use the PHP parser library here rather than sniff with regexes,
    // but it would probably end up being just as much work poking around in
    // the syntax tree.
    $filename = $method_reflection->getFileName();
    $start_line = $method_reflection->getStartLine() - 1; // it's actually - 1, otherwise you wont get the function() block
    $end_line = $method_reflection->getEndLine();
    $length = $end_line - $start_line;
    $file_source = file($filename);
    $body = implode("", array_slice($file_source, $start_line, $length));

    return $body;
  }

  /**
   * Extract parameter types from a method's docblock.
   *
   * @param \ReflectionMethod $method_reflection
   *   The reflection object for the method.
   *
   * @return
   *   An array keyed by the parameter name (without the initial $), whose
   *   values are the type in the docblock, such as 'mixed', 'int', or an
   *   interface. (Although note that interface typehints which are also used
   *   in the actual code are best detected with reflection).
   */
  public function getMethodDocblockParams(\ReflectionMethod $method_reflection) {
    $docblock = $method_reflection->getDocComment();

    $matches = [];
    preg_match_all('/\* @param (?P<type>\S+) \$(?P<name>\w+)/', $docblock, $matches, PREG_SET_ORDER);

    $param_data = [];
    foreach ($matches as $match_set) {
      $param_data[$match_set['name']] = $match_set['type'];
    }

    return $param_data;
  }

}
