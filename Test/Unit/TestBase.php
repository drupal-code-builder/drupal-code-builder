<?php

/**
 * @file
 * Contains TestBase.
 */

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\File\DrupalExtension;
use DrupalCodeBuilder\Test\Fixtures\File\MockableExtension;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Test\VarDumperSetupTrait;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use PHP_CodeSniffer;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Base class for unit tests that work with the generator system.
 *
 * Contains helper methods and assertions.
 */
abstract class TestBase extends TestCase {

  use ProphecyTrait;
  use VarDumperSetupTrait;

  /**
   * The service container.
   */
  protected $container;

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = NULL;

  /**
   * This expects the class property $drupalMajorVersion to be defined.
   */
  protected function setUp(): void {
    // TODO - only include this if run manually and not by CI?
    include_once(__DIR__ . '/../debug.php');

    $this->setUpVarDumper();

    if (empty($this->drupalMajorVersion)) {
      throw new \Exception(sprintf("Drupal major version not set on test class %s.",
        static::class
      ));
    }

    $this->setupDrupalCodeBuilder($this->drupalMajorVersion);
    $this->container = \DrupalCodeBuilder\Factory::getContainer();
  }

  /**
   * Perform the factory setup, spoofing in the given core major version.
   *
   * @param $version
   *  A core major version number,
   */
  protected function setupDrupalCodeBuilder($version) {
    $environment = new \DrupalCodeBuilder\Environment\TestsSampleLocation;

    $version_helper = new \DrupalCodeBuilder\Environment\VersionHelperTestsPHPUnit;
    $version_helper->setFakeCoreMajorVersion($version);

    \DrupalCodeBuilder\Factory::setEnvironment($environment)->setCoreVersionHelper($version_helper);
  }

  /**
   * Gets the empty data item for the root component.
   *
   * @param string $type
   *   The component type.
   *   TODO: make this optional in getTask()?
   *
   * @return \MutableTypedData\Data\DataItem
   *   The data item.
   */
  protected function getRootComponentBlankData(string $type) :DataItem {
    $task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', $type);
    $component_data = $task_handler_generate->getRootComponentData();
    return $component_data;
  }

  /**
   * Gets a mocked Extension object for use to mock existing files.
   *
   * The mocked extension will include the .info.yml file at
   * Test/Fixtures/modules/existing, which exists to avoid the need to mock it
   * in every test using this.
   *
   * @param string $type
   *   The extension type, e.g. 'module'.
   *
   * @return \DrupalCodeBuilder\Test\Fixtures\File\MockableExtension
   *   The mocked extension.
   */
  protected function getMockedExtension(string $type) {
    return new MockableExtension('module', __DIR__ . '/../Fixtures/modules/existing');
  }

  /**
   * Generate module files from component data.
   *
   * @param \MutableTypedData\Data\DataItem $component_data
   *  The data for the generator.
   *
   * @param
   *  An array of files.
   */
  protected function generateComponentFilesFromData(DataItem $component_data, DrupalExtension $extension = NULL) {
    $violations = $component_data->validate();

    if ($violations) {
      $message = [];
      foreach ($violations as $address => $address_violations) {
        $message[] = $address . ': ' . implode(',', $address_violations);
      }
      throw new \DrupalCodeBuilder\Test\Exception\ValidationException(implode('; ', $message));
    }

    $task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', $component_data->base->value);
    $files = $task_handler_generate->generateComponent($component_data, NULL, NULL, $extension);
    return $files;
  }

  /**
   * Generate module files from a data array.
   *
   * @param $module_data
   *  An array of module data for the module generator.
   *
   * @param
   *  An array of files.
   */
  protected function generateModuleFiles(array $module_data, DrupalExtension $extension = NULL) {
    $component_data = $this->getRootComponentBlankData('module');

    $component_data->set($module_data);

    $files = $this->generateComponentFilesFromData($component_data, $extension);

    return $files;
  }

  /**
   * Asserts the count of generated files.
   *
   * This is just a wrapper around assertCount() that outputs the list of
   * filenames if the assertion fails.
   *
   * @param int $expected_count
   *   The expected number of files.
   * @param array $actual_files_array
   *   The array of generated files
   * @param string $message
   *   (optional) The assertion message.
   */
  public static function assertFileCount($expected_count, $actual_files_array, $message = NULL) {
    $message = $message ?? "Expected number of files is returned:";
    $message .= ' ' . print_r(array_keys($actual_files_array), TRUE);

    static::assertCount($expected_count, $actual_files_array, $message);
  }

  /**
   * Asserts the names of the generated files.
   *
   * @param string[] $filenames
   *   An array of filenames.
   * @param array $actual_files
   *   The array of files returned from the generator.
   */
  public static function assertFiles($filenames, $actual_files) {
    $actual_file_names = array_keys($actual_files);

    sort($filenames);
    sort($actual_file_names);

    // TODO! min PHPUnit 7.5?
    static::assertEquals($filenames, $actual_file_names, "The expected files were generated.");
  }

  /**
   * Assert a string has no whitespace at line ends.
   *
   * @param $string
   *  The code string.
   * @param $message = NULL
   *  The assertion message.
   */
  public static function assertNoTrailingWhitespace($code, $message = NULL) {
    $message = $message ?? "The code has no trailing whitespace.";

    $whitespace_regex = "[( +)$]m";

    static::assertDoesNotMatchRegularExpression($whitespace_regex, $code, $message);
  }

  /**
   * Assert a string contains a function whose body contains specific code.
   *
   * TODO: replace this with something using PHPTester.
   *
   * @param $string
   *  The text to check for a function declaration.
   * @param $function_name
   *  The name of the function.
   * @param $function_code
   *  The string of code to check is in the function.
   * @param $message = NULL
   *  The assertion message.
   */
  public static function assertFunctionCode($string, $function_name, $function_code, $message = NULL) {
    if (empty($message)) {
      $message = "Expected function code was found in $function_name().";
    }

    // Account for an indent if this is a class method.
    $indent = '(?:  )?';

    // Extract the function's body from the whole string.
    $matches = [];
    $function_body_regex = "^{$indent}(?:\w+ )*function {$function_name}.*?{\n(.*?)^{$indent}}";
    $match = preg_match("[$function_body_regex]ms", $string, $matches);

    // Run the regex again as an assertion so if the function isn't found, the
    // test fails.
    static::assertMatchesRegularExpression("[$function_body_regex]ms", $string, "The function is found in the string");

    $function_body = $matches[1];

    // Quote the code, as it may contain regex characters.
    $function_code = preg_quote($function_code);
    $expected_regex = "[$function_code]";

    static::assertMatchesRegularExpression($expected_regex, $function_body, $message);
  }

  /**
   * Assert a string contains a .info file property declaration.
   *
   * @param $string
   *  The text to check.
   * @param $property
   *  The property name, e.g. 'core'.
   * @param $value
   *  The value to check, e.g., '7.x'.
   * @param $message = NULL
   *  The assertion message.
   */
  public static function assertInfoLine($string, $property, $value, $message = NULL) {
    // Quote the given strings, as they may contain regex characters.
    $property = preg_quote($property);
    $value    = preg_quote($value);
    $expected_regex = "@^{$property} = {$value}$@m";

    static::assertMatchesRegularExpression($expected_regex, $string, $message);
  }

}
