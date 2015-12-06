<?php

/**
 * @file
 * Definition of ModuleBuilder\Task\Collect5.
 */

namespace ModuleBuilder\Task;

// Dirty hack because Task classes don't have autoloading yet.
include_once(dirname(__FILE__) . "/Collect6.php");

/**
 * Task handler for collecting and processing hook definitions.
 */
class Collect5 extends Collect6 {

  // Drupal 5 version is the same as Drupal 6.

}
