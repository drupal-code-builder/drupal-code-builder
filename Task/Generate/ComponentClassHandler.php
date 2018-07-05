<?php

namespace DrupalCodeBuilder\Task\Generate;

/**
 *  Task helper for working with generator classes and instantiating them.
 */
class ComponentClassHandler {

  /**
   * Cache of classes for types.
   *
   * Keys are the types in Title case, values are the class names.
   *
   * @var array
   */
  protected $classes = [];

  /**
   * Gets the data definition from a component.
   *
   * @param $component_type
   *   The type of the component. This is use to build the class name: see
   *   getGeneratorClass().
   *
   * @return
   *   The component generator's raw data definition.
   */
  public function getComponentDataDefinition($component_type) {
    $class = $this->getGeneratorClass($component_type);

    return $class::componentDataDefinition();
  }

  /**
   * Generator factory.
   *
   * @param $component_type
   *   The type of the component. This is use to build the class name: see
   *   getGeneratorClass().
   * @param $component_data
   *   An array of data for the component. This is passed to the generator's
   *   __construct().
   *
   * @return
   *   A generator object, with the component name and data set on it, as well
   *   as a reference to this task handler.
   *
   * @throws \DrupalCodeBuilder\Exception\InvalidInputException
   *   Throws an exception if the given component type does not correspond to
   *   a component class.
   */
  public function getGenerator($component_type, $component_data) {
    $class = $this->getGeneratorClass($component_type);

    if (!class_exists($class)) {
      throw new \DrupalCodeBuilder\Exception\InvalidInputException(strtr("Invalid component type !type.", array(
        '!type' => htmlspecialchars($component_type, ENT_QUOTES, 'UTF-8'),
      )));
    }

    $generator = new $class($component_data);

    // Quick hack for the benefit of the Hooks generator.
    $generator->classHandlerHelper = $this;

    return $generator;
  }

  /**
   * Helper function to get the desired Generator class.
   *
   * @param $type
   *  The type of the component. This is the name of the class, without the
   *  version suffix. For classes in camel case, the string given here may be
   *  all in lower case.
   *
   * @return
   *  A fully qualified class name for the type and, if it exists, version, e.g.
   *  'DrupalCodeBuilder\Generator\Info6'.
   */
  public function getGeneratorClass($type) {
    $type = ucfirst($type);

    if (!isset($this->classes[$type])) {
      $version  = \DrupalCodeBuilder\Factory::getEnvironment()->getCoreMajorVersion();
      $class    = 'DrupalCodeBuilder\\Generator\\' . $type . $version;

      // If there is no version-specific class, use the base class.
      if (!class_exists($class)) {
        $class  = 'DrupalCodeBuilder\\Generator\\' . $type;
      }

      $this->classes[$type] = $class;
    }

    return $this->classes[$type];
  }

}
