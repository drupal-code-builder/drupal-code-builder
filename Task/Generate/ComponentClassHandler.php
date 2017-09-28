<?php

namespace DrupalCodeBuilder\Task\Generate;

/**
 *  Task helper for working with generator classes and instantiating them.
 */
class ComponentClassHandler {

  // TODO: this is not yet in use!
  // Basically a wrapper around the static call, so we can mock this helper
  // in tests.
  public function getComponentDataDefinition($component_type) {
    $class = $this->getGeneratorClass($component_type);

    return $class::componentDataDefinition();
  }

  /**
   * Gets the repeat handling a component type specifies.
   *
   * This wraps around the static call so it can be mocked in tests.
   *
   * @param $component_type
   *   The component type.
   *
   * @return
   *   The handle type, as returned by the generator class's
   *   requestedComponentHandling().
   */
  public function getRepeatComponentHandling($component_type) {
    $class = $this->getGeneratorClass($component_type);

    return $class::requestedComponentHandling();
  }

  /**
   * Generator factory.
   *
   * @param $component_type
   *   The type of the component. This is use to build the class name: see
   *   getGeneratorClass().
   * @param $component_name
   *   The identifier for the component. This is often the same as the type
   *   (e.g., 'module', 'hooks') but in the case of types used multiple times
   *   this will be a unique identifier.
   * @param $component_data
   *   An array of data for the component. This is passed to the generator's
   *   __construct().
   * @param $root_generator
   *   The root generator, or NULL if it's the root generator itself that is
   *   being created.
   *
   * @return
   *   A generator object, with the component name and data set on it, as well
   *   as a reference to this task handler.
   *
   * @throws \DrupalCodeBuilder\Exception\InvalidInputException
   *   Throws an exception if the given component type does not correspond to
   *   a component class.
   */
  public function getGenerator($component_type, $component_name, $component_data, $root_generator) {
    $class = $this->getGeneratorClass($component_type);

    if (!class_exists($class)) {
      throw new \DrupalCodeBuilder\Exception\InvalidInputException(strtr("Invalid component type !type.", array(
        '!type' => htmlspecialchars($component_type, ENT_QUOTES, 'UTF-8'),
      )));
    }

    // TODO: this passes in the NULL $root_generator when we're constructing
    // the root generator itself! Clean this up!
    $generator = new $class($component_name, $component_data, $root_generator);

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
    $type     = ucfirst($type);
    $version  = \DrupalCodeBuilder\Factory::getEnvironment()->getCoreMajorVersion();
    $class    = 'DrupalCodeBuilder\\Generator\\' . $type . $version;

    // If there is no version-specific class, use the base class.
    if (!class_exists($class)) {
      $class  = 'DrupalCodeBuilder\\Generator\\' . $type;
    }
    return $class;
  }

}
