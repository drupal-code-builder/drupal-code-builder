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
 * The process for this script then expects TWO lists of data on STDIN, as
 * follows:
 *  - any number of lines giving PSR4 namespaces to be added to the autoloader.
 *    These should be of the form namespace_prefix::directory_path, e.g.
 *    '\Drupal\mymodule\::/var/www/drupal/modules/contrib/mymodule/src'.
 *  - a single blank line to indicate the first line is done.
 * - any number of lines giving a fully-qualified PHP class name to check.
 *
 * For each class name, the script will response on STDOUT with a line:
 * '[CLASSNAME] OK'. This indicates that the attempt to load the class file did
 * not crash the script. Note this does not mean that the class actually exists,
 * just that it is safe to call class_exists() with it.
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
  // The fully-qualified name of the class to attempt to load.
  // $class_to_load,
  // Whether to enable debug output: 0 or 1. This prints to STDOUT.
  // TODO: allow this parameter to be absent -- make it a command-line option.
  $debug
) = $argv;

$autoloader = require_once $autoloader_path;

// Add PSR4 namespaces to the autoloader.
// Trim immediately so we get an empty string for a line that's only a newline.
// A newline indicates that all the autoloader namespaces have been passed in,
// and the the calling process is now going to pass in names of classes to
// check.
while ($line = trim(fgets(STDIN))) {
  $line = trim($line);
  list($prefix, $path) = explode('::', $line);

  $autoloader->addPsr4($prefix, $path);
}

while ($class_to_load = trim(fgets(STDIN))) {
  // Moment of truth: this will crash if the class is malformed.
  $class_exists = class_exists($class_to_load);

  // Print a confirmation statement back to the calling process.
  // This allows it to detect that this script is still running and has not
  // crashed.
  print "$class_to_load OK\n";
}
