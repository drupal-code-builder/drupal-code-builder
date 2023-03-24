<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for module info file for Drupal 9.
 */
class Info9 extends Info {

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
