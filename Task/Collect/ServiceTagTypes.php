<?php

namespace DrupalCodeBuilder\Task\Collect;

/**
 *  Task helper for collecting data on tagged services.
 *
 * TODO: there is no way of collecting these for test sample data.
 */
class ServiceTagTypes {

  /**
   * Collect data on tagged services.
   *
   * @return
   *  An array whose keys are service tags, and whose values are the interface
   *  that each tagged service must implement.
   */
  public function collectServiceTagTypes() {
    // Get the kernel, and hack it to get a compiled container.
    // We need this rather than the normal cached container, as that doesn't
    // allow us to get the full service definitions.
    $kernel = \Drupal::service('kernel');
    $kernelR = new \ReflectionClass($kernel);

    $compileContainerR = $kernelR->getMethod('compileContainer');
    $compileContainerR->setAccessible(TRUE);

    $container_builder = $compileContainerR->invoke($kernel);

    // Get the details of all service collector services.
    $collectors_info = $container_builder->findTaggedServiceIds('service_collector');

    $data = [];

    foreach ($collectors_info as $service_name => $tag_infos) {
      // A single service collector service can collect on more than one tag.
      foreach ($tag_infos as $tag_info) {
        $tag = $tag_info['tag'];

        if (!isset($tag_info['call'])) {
          // Shouldn't normally happen, but protected against badly-declated
          // services.
          continue;
        }

        $collecting_method = $tag_info['call'];

        $service_definition = $container_builder->getDefinition($service_name);
        $service_class = $service_definition->getClass();
        $collecting_methodR = new \ReflectionMethod($service_class, $collecting_method);
        $collecting_method_paramR = $collecting_methodR->getParameters();

        // TODO: skip if more than 1 param.
        // getNumberOfParameters

        $type = (string) $collecting_method_paramR[0]->getType();

        $data[$tag] = $type;
      }
    }

    return $data;
  }

}
