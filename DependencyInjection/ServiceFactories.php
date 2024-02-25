<?php

namespace DrupalCodeBuilder\DependencyInjection;

use DI\Factory\RequestedEntry;
use DrupalCodeBuilder\Environment\EnvironmentInterface;
use Psr\Container\ContainerInterface;

/**
 * Contains factory methods for services.
 */
class ServiceFactories {

  /**
   * Factory for versioned services which have no construction parameters.
   *
   * This is registered as the factory for the service name without a version
   * suffix, for example, 'Foo'. Instead of 'Foo', it will return:
   *  1. The service with the version suffix for the current major version, for
   *     example 'Foo10', if it exists.
   *  2. The service formed by adding the '.unversioned' suffix otherwise. This
   *     is an alias for the 'Foo' class, which is needed because trying to get
   *     'Foo' from the container will bring us right back to this factory
   *     method.
   *
   * @param \Psr\Container\ContainerInterface $container
   *   The container.
   * @param \DI\Factory\RequestedEntry $entry
   *   The requested service name.
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment.
   */
  public static function createVersioned(
    ContainerInterface $container,
    RequestedEntry $entry,
    EnvironmentInterface $environment
  ) {
    $requested_name = $entry->getName();
    $versioned_name = $requested_name . $environment->getCoreMajorVersion();

    if ($container->has($versioned_name)) {
      return $container->get($versioned_name);
    }
    else {
      // Get the plain version of the requested service, as otherwise we'd just
      // be requesting the service that brought us here.
      if (!$container->has($requested_name . '.unversioned')) {
        throw new \LogicException("There is no service '$versioned_name' or its unversioned fallback '$requested_name'.");
      }

      return $container->get($requested_name . '.unversioned');
    }
  }

  /**
   * Factory for flavours of the Generate task.
   *
   * @param \Psr\Container\ContainerInterface $container
   *   The container.
   * @param string $root_component_type
   *   The root component type.
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment.
   */
  public static function createGenerator(
    ContainerInterface $container,
    string $root_component_type,
    EnvironmentInterface $environment
  ) {
    return new \DrupalCodeBuilder\Task\Generate(
      $environment,
      $root_component_type,
      $container->get('Generate\ComponentClassHandler'),
      $container->get('Generate\ComponentCollector'),
      $container->get('Generate\FileAssembler'),
    );
  }

}