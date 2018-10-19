<?php

namespace DrupalCodeBuilder\Task\Collect;

/**
 * Task helper for analysing PHP code.
 */
class CodeAnalyser {

  /**
   * Whether to have the checker script output debugging statements.
   *
   * Set to TRUE to get logging from the script's output.
   *
   * TODO: restore the functionality for this.
   */
  protected $debug = FALSE;

  /**
   * The resource for the class safety script process.
   *
   * @var resource
   */
  protected $checking_script_resource = NULL;

  /**
   * The pipes for the class safety script.
   *
   * @var array
   */
  protected $pipes = [];

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
    // Set up the script with its autoloader if not already done so. We keep the
    // resource to the process open between calls to this method.
    // This means we only need to start a new process at the start of a request
    // to DCB and after a bad class has caused the script to crash.
    if (!is_resource($this->checking_script_resource)) {
      $this->setupScript();
    }

    // Write the class to check to the script's STDIN.
    fwrite($this->pipes[0], $qualified_classname . PHP_EOL);

    // We expect a single line back from the script to confirm the class is
    // OK. This is necessary because proc_get_status() can't immediately
    // confirm the script is no longer running due to concurrency issues:
    // see https://stackoverflow.com/questions/52871286/why-doesnt-proc-get-status-show-me-a-process-has-crashed
    // The line needs to be trimmed as if there's a crash, fgets() produces
    // a string containing only a newline character.
    $status_line = trim(fgets($this->pipes[1]));

    if (empty($status_line)) {
      // The process has crashed.
      // Clean up so the script is reinitialized the next time this
      // method is called.
      fclose($this->pipes[0]);
      fclose($this->pipes[1]);
      proc_close($this->checking_script_resource);

      return FALSE;
    }
    else {
      // The process returned a confirmation, therefore the class did not
      // crash it.
      return TRUE;
    }
  }

  /**
   * Starts a process running the class safety script.
   */
  protected function setupScript() {
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

    // Debug option for the script.
    $debug_int = (int) $this->debug;

    $command = "php {$script_name} '{$autoloader_filepath}' {$debug_int}";

    // Open pipes for both input and output.
    $descriptorspec = array(
       0 => array("pipe", "r"),
       1 => array("pipe", "w")
    );
    $this->checking_script_resource = proc_open($command, $descriptorspec, $this->pipes);

    if (!is_resource($this->checking_script_resource)) {
      throw new \Exception("Could not create process for classIsUsable().");
    }

    foreach ($psr4 as $line) {
      fwrite($this->pipes[0], $line . PHP_EOL);
    }

    // Write a blank line to the script to tell it we are done with PSR4
    // namespaces.
    fwrite($this->pipes[0], PHP_EOL);
  }

  /**
   * Magic method.
   *
   * Cleans up the process when this task helper is destroyed.
   */
  public function __destruct() {
    // Close the script when this task service is destroyed, or at the end of
    // the request.
    // Allow for the script to not have been started at all, e.g. in debugging
    // scenarios.
    if (is_resource($this->checking_script_resource)) {
      fclose($this->pipes[0]);
      fclose($this->pipes[1]);
      proc_close($this->checking_script_resource);
    }
  }

}
