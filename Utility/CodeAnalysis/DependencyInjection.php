<?php

namespace DrupalCodeBuilder\Utility\CodeAnalysis;

/**
 * Provides code analysis relating to dependency injection.
 */
class DependencyInjection {

  /**
   * Gets data on the parameters injected into the class constructor.
   *
   * @param string $class
   *   The class name.
   * @param int $fixed_parameter_count
   *   The number of fixed parameters which are independent of dependency
   *   injection. These are the parameters which get passed to __construct()
   *
   *
   * @return
   *   An numeric array where each item is data for a parameter, containing:
   *    - 'type': The typehint for the parameter.
   *    - 'name': The name of the parameter without the leading '$'.
   *    - 'extraction': The code snippet used in the create() method to extract
   *      the service from the container.
   */
  public static function getInjectedParameters($class, $fixed_parameter_count) {
    if (!method_exists($class, '__construct')) {
      return [];
    }
    if (!method_exists($class, 'create')) {
      return [];
    }

    // Get the parameter data for the __construct() method from the reflection
    // helper.
    $construct_R = new Method($class, '__construct');
    $parameter_data = $construct_R->getParamData();

    $injection_parameter_data = array_slice($parameter_data, $fixed_parameter_count);

    // Extract code from the call to 'new Class()' from the body of the
    // create() method.
    $create_R = new Method($class, 'create');
    $create_method_body = $create_R->getBody();

    $matches = [];
    preg_match('@ new \s+ static \( ( [^;]+ ) \) ; @x', $create_method_body, $matches);

    // Bail if we didn't find the call.
    if (empty($matches[1])) {
      // TODO: some classes call parent::create()!
      return [];
    }

    $parameters = explode(',', $matches[1]);

    $create_container_extractions = [];
    foreach (array_slice($parameters, 3) as $i => $parameter) {
      $injection_parameter_data[$i]['extraction'] = trim($parameter);
    }

    return $injection_parameter_data;
  }

}
