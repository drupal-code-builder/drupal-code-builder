<?php

namespace DrupalCodeBuilder\Test\Unit\Parsing;

use PHPUnit\Framework\Assert;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Helper class for parsing and testing Yaml.
 */
class YamlTester {

  /**
   * The YAML string being tested.
   *
   * @var string
   */
  protected $originalYaml;

  /**
   * The data array parsed from the given YAML.
   *
   * @var array
   */
  protected $parsedYamlData;

  /**
   * Construct a new YamlTester.
   *
   * @param string $yaml_code
   *   The YAML code that should be tested.
   */
  public function __construct($yaml_code) {
    try {
      $value = Yaml::parse($yaml_code);
    }
    catch (ParseException $e) {
      // Turn a YAML parser exception into a test failure.
      Assert::fail($e->getMessage());
    }

    // Now that parsing has worked, store the original and the parsed data
    // for subsequent assertions.
    $this->originalYaml = $yaml_code;
    $this->parsedYamlData = $value;

    // dump($this->originalYaml);
    // dump($this->parsedYamlData);
  }

  /**
   * Assert the YAML has the given property.
   *
   * @param mixed $property_address
   *   The address of the property. An array address for the property; may be
   *   a scalar string for a top-level property.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasProperty($property_address, $message = NULL) {
    $message = $message ?? 'The YAML file has the expected property.';

    if (!is_array($property_address)) {
      $property_address = [$property_address];
    }

    Assert::assertTrue($this->keyExists($this->parsedYamlData, $property_address), $message);
  }

  /**
   * Assert the YAML property has the given value.
   *
   * @param mixed $property_address
   *   The address of the property. An array address for the property; may be
   *   a scalar string for a top-level property.
   * @param $expected_value
   *   The expected value.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertPropertyHasValue($property_address, $expected_value, $message = NULL) {
    $message = $message ?? 'The YAML file property has the expected value';

    if (!is_array($property_address)) {
      $property_address = [$property_address];
    }

    $actual_value = $this->getValue($this->parsedYamlData, $property_address);

    Assert::assertEquals($expected_value, $actual_value, $message);
  }

  /**
   * Copy of Drupal's NestedArray::keyExists().
   */
  protected function keyExists(array $array, array $parents) {
    // Although this function is similar to PHP's array_key_exists(), its
    // arguments should be consistent with getValue().
    $key_exists = NULL;
    $this->getValue($array, $parents, $key_exists);
    return $key_exists;
  }

  /**
   * Copy of Drupal's NestedArray::getValue().
   */
  protected function &getValue(array &$array, array $parents, &$key_exists = NULL) {
    $ref =& $array;
    foreach ($parents as $parent) {
      if (is_array($ref) && array_key_exists($parent, $ref)) {
        $ref =& $ref[$parent];
      }
      else {
        $key_exists = FALSE;
        $null = NULL;
        return $null;
      }
    }
    $key_exists = TRUE;
    return $ref;
  }
}
