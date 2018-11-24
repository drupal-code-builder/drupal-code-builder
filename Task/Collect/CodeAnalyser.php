<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

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
   * Constructs a new helper.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment object.
   */
  public function __construct(EnvironmentInterface $environment) {
    $this->environment = $environment;
  }

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
    $status = $this->sendCommand('CLASS', $qualified_classname);

    if (!$status) {
      // The process has crashed.
      // Clean up so the script is reinitialized the next time this method is
      // called.
      $this->closeScript();
    }

    // Return the status of the class check: TRUE means the class is usable,
    // FALSE means it is broken and a class_exists() causes a crash.
    return $status;
  }

  /**
   * Starts a process running the class safety script.
   */
  protected function setupScript() {
    // The PHP that proc_open() will find may not be the same one as run by
    // the webserver, and indeed, on some systems may be an out-of-date version,
    // so detect the PHP that we're currently running and ensure we use that.
    $php = PHP_BINDIR . '/php';

    $script_name = __DIR__ . '/../../class_safety_checker.php';
    $drupal_root = $this->environment->getRoot();
    $autoloader_filepath = $drupal_root . '/autoload.php';

    // We need to pass all the dynamic namespaces to the script, as Composer's
    // generated autoloader knows only about /vendor and /core/lib, but not
    // modules.
    // This code is taken from DrupalKernel::attachSynthetic().
    $container = $this->environment->getContainer();
    $namespaces = $container->getParameter('container.namespaces');
    $psr4 = [];
    foreach ($namespaces as $prefix => $paths) {
      if (is_array($paths)) {
        foreach ($paths as $key => $value) {
          $paths[$key] = $drupal_root . '/' . $value;
        }
      }
      elseif (is_string($paths)) {
        $paths = $drupal_root . '/' . $paths;
      }

      // Build a list of data to pass to the script on STDIN.
      // $paths is never an array, AFAICT.
      $psr4[] = $prefix . '\\' . '::' . $paths;
    }

    // Debug option for the script.
    $debug_int = (int) $this->debug;

    $command = "{$php} {$script_name} '{$autoloader_filepath}' {$debug_int}";

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
      $this->sendCommand('PSR4', $line);
    }
  }

  /**
   * Send a single command to the script.
   *
   * @param string $command
   *   The command to send to the script. One of:
   *    - 'PSR4': A PSR4 namespace and folder to add to the autoloader.
   *    - 'CLASS': A class to check.
   * @param string $payload
   *   The value to send to the script. Format depends on the command.
   *
   * @return bool
   *  Returns TRUE if the command was successful; FALSE if the process appears
   *  to have crashed.
   *
   * @throws \Exception
   *  Throws an exception if the command failed.
   */
  protected function sendCommand($command, $payload) {
    fwrite($this->pipes[0], $command . ':' . $payload . PHP_EOL);

    $status_line = trim(fgets($this->pipes[1]));

    // An empty status line means the script has stopped responding, and has
    // presumably crashed.
    if (empty($status_line)) {
      return FALSE;
    }

    // An expected status line that matches the sent command means everything
    // went fine.
    if ($status_line == "$command OK") {
      return TRUE;
    }

    // If something's gone wrong, get any further output from the script so we
    // can see error messages.
    while ($line = fgets($this->pipes[1])) {
      dump($line);
    }

    throw new \Exception("Command $command with payload '$payload' failed.");
  }

  /**
   * Close the script.
   *
   * Note this is called automatically when this object is destroyed.
   */
  public function closeScript() {
    fclose($this->pipes[0]);
    fclose($this->pipes[1]);
    proc_close($this->checking_script_resource);
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
      $this->closeScript();
    }
  }

}
