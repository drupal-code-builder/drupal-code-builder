<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

/**
 * Generator for the class holding Drush command methods.
 */
class DrushCommandFile extends PHPClassFileWithInjection {

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return 'drush-commands';
  }

}
