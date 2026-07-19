<?php

namespace DrupalCodeBuilder\Task\Collect;

use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Task helper hacking the Drupal kernel to get a Container to inspect services.
 */
class ContainerBuilderGetter {

  /**
   * The container builder.
   */
  protected ContainerBuilder $containerBuilder;

  /**
   * Gets the ContainerBuilder by hacking the Drupal kernel.
   *
   * @return \Drupal\Core\DependencyInjection\ContainerBuilder
   *   The compiled service container
   */
  public function getContainerBuilder(): ContainerBuilder {
    if (!isset($this->containerBuilder)) {
      // Get the kernel, and hack it to get a compiled container.
      // We need this rather than the normal cached container, as that doesn't
      // allow us to get the full service definitions.
      $kernel = \Drupal::service('kernel');
      $kernel_R = new \ReflectionClass($kernel);

      $compileContainer_R = $kernel_R->getMethod('compileContainer');
      if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        $compileContainer_R->setAccessible(TRUE);
      }

      $this->containerBuilder = $compileContainer_R->invoke($kernel);
    }

    return $this->containerBuilder;
  }

}
