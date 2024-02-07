<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module info data for Drupal 9.
 */
class InfoModule9 extends InfoModule {

  /**
   * {@inheritdoc}
   */
  function infoData(): array {
    $lines = parent::infoData();

    if ($this->component_data->lifecycle->value) {
      // Remove the Drupal 10+ line.
      unset($lines['lifecycle']);

      // On Drupal <9 we only support the value 'experimental'.
      if ($this->component_data->lifecycle->value == 'experimental') {
        $lines['experimental'] = TRUE;
      }
    }

    return $lines;
  }

}
