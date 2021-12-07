<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * NO! Generator for a Drush command.
 *
 * Needed??
 */
class DrushCommandsService extends Service {

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    // TODO: add a way to set merge tag in properties, without a new class.
    return 'drush-commands';
  }

}
