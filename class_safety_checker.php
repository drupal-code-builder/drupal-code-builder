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
 *  $ class_safety_checker.php autoloader_path class_to_load
 * @endcode
 *
 * Where:
 *  - autoloader_path is an absolute filepath to Drupal root's autoload.php.
 *  - class_to_load is the fully-qualified classname of the class to try to
 *    load.
 *
 * The process for this script then needs a list of PSR4 namespaces for the
 * autoloader. This is because Drupal's autoloader is generated with only
 * classes from /vendor and /core/lib/ and all classes from modules are added on
 * the fly by DrupalKernel::attachSynthetic(). We need to do the same to the
 * autoloader here, so that any module classes can be loaded.
 * Each line passed to STDIN must be of the form:
 *
 * namespace_prefix::directory_path
 *
 * for example:
 *
 * @code
 *   \Drupal\mymodule\::/var/www/drupal/modules/contrib/mymodule/src
 * @endcode
 *
 * The exit code for the script should be examined by the running script:
 *  - 0 means the attempt to load the class file did not crash the script. Note
 *    this does not mean that the class actually exists, just that it is safe to
 *    call class_exists() with it.
 *  - anything else means the script crashed, and the class is therefore broken.
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
while ($line = trim(fgets(STDIN))) {
  $line = trim($line);
  list($prefix, $path) = explode('::', $line);

  $autoloader->addPsr4($prefix, $path);
}

while ($class_to_load = fgets(STDIN)) {
  print "about to check $class_to_load.\n";

  // Moment of truth: this will crash if the class is malformed.
  $class_exists = class_exists($class_to_load);

  if ($debug) {
    print "$class_to_load did not crash.\n";
  }
}
