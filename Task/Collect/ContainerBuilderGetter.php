<?php

namespace DrupalCodeBuilder\Task\Collect;

/**
 * Task helper hacking the Drupal kernel to get a Container to inspect services.
 */
class ContainerBuilderGetter {

  /**
   * The container builder.
   */
  protected $containerBuilder;

  /**
   * Gets the ContainerBuilder by hacking the Drupal kernel.
   *
   * @return
   *   The container builder.
   */
  public function getContainerBuilder() {
    if (!isset($this->containerBuilder)) {
      // Get the kernel, and hack it to get a compiled container.
      // We need this rather than the normal cached container, as that doesn't
      // allow us to get the full service definitions.
      $kernel = \Drupal::service('kernel');
      $kernel_R = new \ReflectionClass($kernel);

      $compileContainer_R = $kernel_R->getMethod('compileContainer');
      $compileContainer_R->setAccessible(TRUE);

      $this->containerBuilder = $compileContainer_R->invoke($kernel);
    }

    return $this->containerBuilder;
  }

}
