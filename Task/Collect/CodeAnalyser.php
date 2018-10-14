<?php

namespace DrupalCodeBuilder\Task\Collect;

/**
 * Task helper for analysing PHP code.
 */
class CodeAnalyser {

  /**
   * Determines whether a class may be instantiated safely.
   *
   * This is needed because:
   * - lots of contrib modules have plugins that simply crash on class load,
   *   typically because they fail the interface implementation.
   * - modules can have services which are meant for another non-dependent
   *   module's use, and which can thus implement interfaces or inherit from
   *   classes which are not present. For example, in the case of a tagged
   *   service, if the collecting module is not enabled, we have no way of
   *   detecting that the service's tag is a service collector tag.
   *
   * @param string $qualified_classname
   *   The fully-qualified class name, without the initial \.
   *
   * @return boolean
   *   TRUE if the class may be used (but note this does not say whether it
   *   actually exists); FALSE if the class should not be used as attempting to
   *   will cause a PHP fatal error.
   */
  public function classIsUsable($qualified_classname) {
    $script_name = __DIR__ . '/../../class_safety_checker.php';
    $autoloader_filepath = DRUPAL_ROOT . '/autoload.php';

    // We need to pass all the dynamic namespaces to the script, as Composer's
    // generated autoloader knows only about /vendor and /core/lib, but not
    // modules.
    // This code is taken from DrupalKernel::attachSynthetic().
    $container = \Drupal::getContainer();
    $root = \Drupal::root();
    $namespaces = $container->getParameter('container.namespaces');
    $psr4 = [];
    foreach ($namespaces as $prefix => $paths) {
      if (is_array($paths)) {
        foreach ($paths as $key => $value) {
          $paths[$key] = $root . '/' . $value;
        }
      }
      elseif (is_string($paths)) {
        $paths = $root . '/' . $paths;
      }

      // Build a list of data to pass to the script on STDIN.
      // $paths is never an array, AFAICT.
      $psr4[] = $prefix . '\\' . '::' . $paths;
    }

    $command = "php {$script_name} '{$autoloader_filepath}' '{$qualified_classname}'";

    // Open pipes for both input and output.
    $descriptorspec = array(
       0 => array("pipe", "r"),
       1 => array("pipe", "w")
    );
    $process = proc_open($command, $descriptorspec, $pipes);

    if (!is_resource($process)) {
      throw new \Exception("Could not create process for classIsUsable().");
    }

    foreach ($psr4 as $line) {
      fwrite($pipes[0], $line . PHP_EOL);
    }
    fclose($pipes[0]);

    // Output is used only for debugging.
    $output = stream_get_contents($pipes[1]);

    // Get the exit code to see if the script crashed or not.
    $exit = proc_close($process);

    // If the script crashed, the class is bad.
    if ($exit != 0) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

}
