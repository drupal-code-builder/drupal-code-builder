<?php

namespace DrupalCodeBuilder\Task\Generate;

use DI\Attribute\Inject;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\Generator\ClassHandlerAware;
use DrupalCodeBuilder\Generator\EnvironmentAware;

/**
 *  Task helper for working with generator classes and instantiating them.
 */
class ComponentClassHandler {

  /**
   * Constructor.
   *
   * @param EnvironmentInterface $environment
   *   The environment object.
   * @param array $generator_classmap
   *   The classmap of version-specific generator classes. Keys are the base
   *   class name, then the version, value is the short class name of the
   *   version-specific class. For example:
   *   @code
   *   [
   *     'AdminSettingsForm' => [
   *       7 => AdminSettingsForm7,
   *     ]
   *   ]
   *   @endcode
   */
  public function __construct(
    protected EnvironmentInterface $environment,
    #[Inject('generator_classmap')]
    protected array $generator_classmap
  ) { }

  /**
   * Gets the data definition for a standalone component.
   *
   * This is for use by the requirement components process and variant
   * generators.
   *
   * @param string $component_type
   *   The component type.
   * @param string $machine_name
   *   (optional) The machine name for the root definition.
   *
   * @return \DrupalCodeBuilder\Definition\PropertyDefinition
   *   The definition.
   *
   * @throws \InvalidArgumentException
   *   Throws an exception if there is no class found for the component type.
   */
  public function getStandaloneComponentPropertyDefinition(string $component_type, string $machine_name = NULL): PropertyDefinition {
    $definition = MergingGeneratorDefinition::createFromGeneratorType($component_type);

    if (!$definition->getName()) {
      // TODO: Clean up all this machine name hackery.
      if (empty($machine_name)) {
        // Quick hack.
        $machine_name = $machine_name ?? strtolower($component_type);
      }

      // Some component types contain ':' characters! Argh!
      $machine_name = str_replace(':', '-', $machine_name);

      $definition->setName($machine_name);
    }

    return $definition;
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
      throw new \DrupalCodeBuilder\Exception\InvalidInputException(strtr("Invalid component type !type.", [
        '!type' => htmlspecialchars($component_type, ENT_QUOTES, 'UTF-8'),
      ]));
    }

    $generator = new $class($component_data, $this);

    // Inject the class handler if needed.
    if ($generator instanceof ClassHandlerAware) {
      $generator->setClassHandler($this);
    }

    // Inject the environment if needed.
    if ($generator instanceof EnvironmentAware) {
      $generator->setEnvironment($this->environment);
    }

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
   *  'DrupalCodeBuilder\Generator\Info6'. Note that this class has not been
   *  checked for existence.
   */
  public function getGeneratorClass($type) {
    $type = ucfirst($type);

    $version  = \DrupalCodeBuilder\Factory::getEnvironment()->getCoreMajorVersion();

    if (isset($this->generator_classmap[$type][$version])) {
      $short_class = $this->generator_classmap[$type][$version];
    }
    else {
      $short_class = $type;
    }

    $class = 'DrupalCodeBuilder\\Generator\\' . $short_class;

    return $class;
  }

}
