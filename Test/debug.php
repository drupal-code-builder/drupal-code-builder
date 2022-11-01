<?php

/**
 * @file
 *
 * Contains debugging functions.
 *
 * Include this file when developing this package.
 */

/**
 * Dumps the exported component from which this was called.
 */
function dc() {
  $backtrace = debug_backtrace();
  $component = $backtrace[1]['object'];

  dump($component->component_data->export());
}
