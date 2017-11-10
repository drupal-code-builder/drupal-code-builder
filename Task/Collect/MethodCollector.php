<?php

namespace DrupalCodeBuilder\Task\Collect;

/**
 * Task helper for collecting data on methods of classes or interfaces.
 *
 * TODO: This is currently used to help collectors with their own data; change
 * this into a system that keeps data on all interfaces and classes we're
 * interested in.
 */
class MethodCollector {

  /**
   * Get data for the methods of a class or interface.
   *
   * TODO: remove this, use getMethodData() from other classes.
   *
   * @param $name
   *  The fully-qualified name of the class or interface.
   *
   * @return
   *  An array keyed by method name, where each value is an array containing:
   *  - 'name: The name of the method.
   *  - 'declaration': The function declaration line.
   *  - 'description': The description from the method's docblock first line.
   */
  public function collectMethods($name) {
    // Get a reflection class for the interface.
    $reflection = new \ReflectionClass($name);
    $methods = $reflection->getMethods();

    $data = [];

    foreach ($methods as $method) {
      // Dev trapdoor.
      if ($method->getName() != 'storageSettingsForm') {
        //continue;
      }

      $data[$method->getName()] = $this->getMethodData($method);
    }

    return $data;
  }

  /**
   * Gets data for a method.
   *
   * @param \ReflectionMethod $method
   *  The reflection object for the method.
   *
   * @return
   *  An array containing:
   *  - 'name: The name of the method.
   *  - 'declaration': The function declaration line.
   *  - 'description': The description from the method's docblock first line.
   */
  public function getMethodData(\ReflectionMethod $method) {
    $interface_method_data = [];

    $interface_method_data['name'] = $method->getName();

    // Methods may be in parent interfaces, so not all in the same file.
    $filename = $method->getFileName();
    $source = file($filename);
    $start_line = $method->getStartLine();

    // Trim whitespace from the front, as this will be indented.
    $interface_method_data['declaration'] = trim($source[$start_line - 1]);

    // Get the docblock for the method.
    $method_docblock_lines = explode("\n", $method->getDocComment());
    foreach ($method_docblock_lines as $line) {
      // Take the first actual docblock line to be the description.
      // Need to use preg_match() rather than match exactly to account for
      $matches = [];
      if (preg_match('@^ +\* (.+)@', $line, $matches)) {
        $interface_method_data['description'] = $matches[1];
        break;
      }
    }

    // Replace class typehints on method parameters with their full namespaced
    // versions, as typically these will be short class names. The PHPFile
    // generator will then take care of extracting namespaces and creating
    // import statements.
    // Get the typehint classes on parameters.
    $parameters = $method->getParameters();
    $parameter_hinted_class_short_names = [];
    $parameter_hinted_class_full_names = [];
    foreach ($parameters as $parameter) {
      $parameter_hinted_class = $parameter->getClass();

      // Skip a parameter that doesn't have a class hint.
      if (is_null($parameter_hinted_class)) {
        continue;
      }

      // Create arrays for str_replace() of short and long classnames.
      $parameter_hinted_class_short_names[] = $parameter_hinted_class->getShortName();
      // The PHPFile generator works with fully-qualified classnames, with
      // an initial '\', so we need to prepend that.
      $parameter_hinted_class_full_names[] = '\\' . $parameter_hinted_class->getName();
    }

    $interface_method_data['declaration'] = str_replace(
      $parameter_hinted_class_short_names,
      $parameter_hinted_class_full_names,
      $interface_method_data['declaration']
    );

    return $interface_method_data;
  }

}
