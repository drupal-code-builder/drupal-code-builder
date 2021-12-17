<?php

namespace DrupalCodeBuilder\MutableTypedData\Validator;

use MutableTypedData\Data\DataItem;
use MutableTypedData\Validator\ValidatorInterface;

/**
 * Validates a plugin ID exists.
 *
 * WARNING: this is tightly coupled to the PluginAnnotationDiscovery
 * generator!
 */
class PluginExists implements ValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(DataItem $data): bool {
    // WARNING: expects 'plugin_type_data to exist.
    $plugin_type_manager_service_id = $data->getParent()->plugin_type_data->value['service_id'];
    $plugin_type_manager_service = \DrupalCodeBuilder\Factory::getEnvironment()->getContainer()->get($plugin_type_manager_service_id);

    try {
      $plugin_type_manager_service->getDefinition($data->value);
      return TRUE;
    }
    catch (\Drupal\Component\Plugin\Exception\PluginNotFoundException $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function message(DataItem $data): string {
    return "The '@value' plugin does not exist.";
  }

}
