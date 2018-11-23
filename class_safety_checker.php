<?php
/**
 * Script to load a class via Drupal's autoloader to check PHP doesn't crash.
 *
 * This is needed because:
 *  - lots of contrib modules have plugins that simply crash on class load,
 *    typically because they fail the interface implementation.
 *  - modules can have services which are meant for another non-dependent
 *    module's use, and which can thus implement interfaces or inherit from
 *    classes which are not present.
 *
 * This script needs input via the command line parameters *and* STDIN:
 *
 * @code
 *  $ class_safety_checker.php autoloader_path
 * @endcode
 *
 * Where:
 *  - autoloader_path is an absolute filepath to Drupal root's autoload.php.
 *
 * The process for this script then expects to receive commands as single lines
 * on STDIN, in the format:
 *   [COMMAND]:[PAYLOAD]
 * where COMMAND can be one if:
 *  - 'PSR4': The payload is a line giving a PSR4 namespace to be added to the
 *    autoloader. The payload should be of the form
 *    namespace_prefix::directory_path, e.g.
 *    '\Drupal\mymodule\::/var/www/drupal/modules/contrib/mymodule/src'.
 * - 'CLASS': A fully-qualified PHP class name to check.
 *
 * For each command, the script will response on STDOUT with a line:
 *   [COMMAND] OK
 *
 * In particular for the CLASS command, this indicates that the attempt to load
 * the class file did not crash the script. Note this does not mean that the
 * class actually exists, just that it is safe to call class_exists() with it.
 *
 * If the script does not respond, the caller should assume that it has crashed
 * while attempting to load the most recently given class, and that the class
 * is therefore broken.
 *
 * @see \DrupalCodeBuilder\Task\Collect\CodeAnalyser::classIsUsable()
 */

// Get the script parameters.
list(
  $script,
  // The file to include to load Drupal's autoloader. Being given it here
  // saves us faffing about figuring out where the Drupal root is relative to
  // us.
  $autoloader_path,
  // Whether to enable debug output: 0 or 1. This prints to STDOUT.
  // TODO: allow this parameter to be absent -- make it a command-line option.
  $debug
) = $argv;

// print "debug: $debug";

$autoloader = require_once $autoloader_path;

// Main execution loop.
while ($line = fgets(STDIN)) {
  $line = trim($line);
  list($command, $payload) = explode(':', $line, 2);

  switch ($command) {
    case 'PSR4':
      // Add a PSR4 namespace to the autoloader.
      list($prefix, $path) = explode('::', $payload);

      $autoloader->addPsr4($prefix, $path);

      // Return a confirmation.
      print "PSR4 OK\n";
      break;

    case 'CLASS':
      $class_to_load = $payload;

      // Moment of truth: this will crash if the class is malformed.
      $class_exists = class_exists($class_to_load);

      // Return a confirmation.
      print "CLASS OK\n";
      break;

    case 'EXIT':
      exit();
  }
}
