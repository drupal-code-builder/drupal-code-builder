<?php

namespace DrupalCodeBuilder\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;
use PHP_CodeSniffer\Runner;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Reporter;
use PHP_CodeSniffer\Files\DummyFile;

/**
 * PHPUnit constraint for a code file conforming to Drupal Coding standards.
 */
class CodeAdheresToCodingStandards extends Constraint {

  protected static $composerVendorDir;

  protected string $code;

  protected array $reportData;

  protected Reporter $reporting;

  /**
   * The configuration for PHP_CodeSniffer.
   *
   * @var \PHP_CodeSniffer\Config
   */
  protected $PHPCodeSnifferConfig;

  /**
   * Constructor.
   *
   * @param int $drupalMajorVersion
   *   The major version of Drupal for the code being tested.
   * @param array $excluded_sniffs
   *   An array of names of PHPCS sniffs to exclude.
   * @param string $phpCodeFilePath
   *   The fictitious filepath of the code file being tested. Some sniffs use
   *   this, for example to check a class name matches its filename. If this is
   *   not available, pass an empty string.
   */
  public function __construct(
    protected int $drupalMajorVersion,
    protected array $excluded_sniffs,
    protected string $phpCodeFilePath,
    )
  {
    $this->findComposerVendorDir();
  }

  /**
   * Evaluates the constraint for parameter $other. Returns true if the
   * constraint is met, false otherwise.
   *
   * @throws Exception
   */
  protected function matches($other): bool {
    $phpcs_runner = $this->setUpPHPCS($this->excluded_sniffs);

    // Process the file with PHPCS.

    // Create and process a single file, faking the path so the report looks nice.
    $file = new DummyFile($other, $phpcs_runner->ruleset, $phpcs_runner->config);
    // We need to pass in a value for the filename, even though the file does
    // not exist. Use a dummy if the PHPTester was constructed without a
    // filepath.
    $file->path = $this->phpCodeFilePath ?: '/path/to/my/file.php';
    // Process the file.
    $phpcs_runner->processFile($file);
    // Print out the reports.
    // $phpcs_runner->reporter->printReports();

    $error_count   = $file->getErrorCount();
    $warning_count = $file->getWarningCount();

    $total_error_count = $error_count + $warning_count;

    if (empty($total_error_count)) {
      return TRUE;
    }

    // Store data on this object for failureDescription() to access.
    $this->code = $other;

    // Get the reporting to process the errors.
    $this->reporting = new Reporter($this->PHPCodeSnifferConfig);
    // $reportClass = $this->reporting->factory('full');
    // Prepare the report, but don't call generateFileReport() as that echo()s
    // it!
    $this->reportData  = $this->reporting->prepareFileReport($file);
    //$reportClass->generateFileReport($reportData, $phpcsFile);

    return FALSE;
  }

  public function toString(): string {
    return 'adheres to Drupal coding standards';
}

  /**
   * Returns the description of the failure.
   *
   * The beginning of failure messages is "Failed asserting that" in most
   * cases. This method should return the second part of that sentence.
   *
   * @param mixed $other evaluated value or object
   */
  protected function failureDescription($other): string {
    // Get the code lines as an array so we can add the line numbers.
    $code_lines = explode("\n", $this->code);
    // Re-key it so the line numbers start at 1.
    $code_lines = array_combine(range(1, count($code_lines)), $code_lines);

    $indent_size = strlen((string) count($code_lines));

    array_walk($code_lines, function(&$line, $number) use ($indent_size) {
      $line = str_pad($number, $indent_size, ' ', \STR_PAD_LEFT) . ' ' . $line;
    });

    $error_messages = [];
    foreach ($this->reportData['messages'] as $line_number => $columns) {
      foreach ($columns as $column_number => $messages) {
        $code_line = $code_lines[$line_number];
        $before = substr($code_line, 0, $column_number - 1);
        $after = substr($code_line, $column_number - 1);
        $error_messages[] = $before . '^' . $after;
        foreach ($messages as $message_info) {
          $error_messages[] = "{$message_info['type']}: line $line_number, column $column_number: {$message_info['message']} - {$message_info['source']}";
        }
      }
    }

    return 'the file is valid PHP, with errors: ' .
      implode("\n", $error_messages) .
      "\nin the following code:\n" .
      implode("\n", $code_lines);
  }

  /**
   * Sets up PHPCS.
   *
   * Helper for assertDrupalCodingStandards().
   */
  protected function setUpPHPCS($excluded_sniffs) {
    // Need to define this to avoid a deprecation error from PHP!
    if (defined('PHP_CODESNIFFER_CBF') === false) {
      define('PHP_CODESNIFFER_CBF', false);
    }

    // PHPCS has its own autoloader...
    require static::$composerVendorDir . '/squizlabs/php_codesniffer/autoload.php';

    $runner = new Runner();
    // We need to pass in a non-empty array of fake command-line arguments to
    // the Config class constructor, as otherwise it will take them from the
    // real command- line arguments to the phpunit command, and will crash if it
    // finds PHPUnit's '--group' options, as it doesn't recognize it. The '--'
    // is treated as a null argument.
    $runner->config = new Config(['--']);
    $runner->config->setConfigData('installed_paths', implode(',', [
      static::$composerVendorDir . '/drupal/coder/coder_sniffer',
      static::$composerVendorDir . '/slevomat/coding-standard',
      // Need to register our config data dir for both the case where this repo
      // is the main project in CI testing, and the case where this repo is
      // installed as a library in local development. PHPCS does not seem to
      // mind a directory that doesn't exist.
      getcwd() . '/Test/PHP_CodeSniffer',
      static::$composerVendorDir . '/drupal-code-builder/drupal-code-builder/Test/PHP_CodeSniffer',
    ]));

    $runner->config->setConfigData('drupal_core_version', $this->drupalMajorVersion);
    $runner->config->standards = [
      'Drupal',
      'DrupalCodeBuilder',
    ];
    $runner->config->exclude = $excluded_sniffs;
    $runner->init();
    // Hard-code some other config settings.
    // Do this after init() so these values override anything that was set in
    // the rulesets we processed during init(). Or do this before if you want
    // to use them like defaults instead.
    $runner->config->reports      = ['summary' => null, 'full' => null];
    $runner->config->verbosity    = 0;
    $runner->config->showProgress = false;
    $runner->config->interactive  = false;
    $runner->config->cache        = false;
    $runner->config->showSources  = true;
    // Create the reporter, using the hard-coded settings from above.
    $runner->reporter = new Reporter($runner->config);

    // Store the config, as we need it for the reporter.
    $this->PHPCodeSnifferConfig = $runner->config;

    return $runner;
  }

  /**
   * Determines the vendor folder for the current project.
   *
   * This is required because some code testing tools we use expect to be run
   * as command-line scripts and their code needs to be accessed directly.
   *
   * We can't simply go up directories from this file, as this would not all
   * Drupal Code Builder to be aliased into a Composer project for development,
   * since PHP's __DIR__ resolves symlinks.
   */
  protected function findComposerVendorDir() {
    if (!isset(static::$composerVendorDir)) {
      $reflection = new \ReflectionClass('\Composer\Autoload\ClassLoader');
      $filename = $reflection->getFileName();

      static::$composerVendorDir = dirname($filename, 2);
    }
  }

}
