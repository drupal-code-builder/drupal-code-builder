<?php

namespace DrupalCodeBuilder\Test\Unit\Parsing;

use DrupalCodeBuilder\Utility\NestedArray;
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
   * The YAML string as an array of lines.
   *
   * @var string[]
   */
  protected $originalYamlLines;

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
    $this->originalYamlLines = explode("\n", $this->originalYaml);
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
    if (!is_array($property_address)) {
      $property_address = [$property_address];
    }

    $property_string = $this->getPropertyString($property_address);
    $message = $message ?? "The YAML file has the expected property $property_string.";

    Assert::assertTrue(NestedArray::keyExists($this->parsedYamlData, $property_address), $message);
  }

  /**
   * Assert the YAML does not have the given property.
   *
   * @param mixed $property_address
   *   The address of the property. An array address for the property; may be
   *   a scalar string for a top-level property.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasNotProperty($property_address, $message = NULL) {
    if (!is_array($property_address)) {
      $property_address = [$property_address];
    }

    $property_string = $this->getPropertyString($property_address);
    $message = $message ?? "The YAML file does not have the property $property_string.";

    Assert::assertFalse(NestedArray::keyExists($this->parsedYamlData, $property_address), $message);
  }

  /**
   * Asserts the YAML property is formatted with a blank line before it.
   *
   * @param mixed $property_address
   *   The address of the property. An array address for the property; may be
   *   a scalar string for a top-level property.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertPropertyHasBlankLineBefore($property_address, $message = NULL) {
    if (!is_array($property_address)) {
      $property_address = [$property_address];
    }

    $property_string = $this->getPropertyString($property_address);
    $message = $message ?? "The property $property_string has a blank line before it.";

    $this->assertHasProperty($property_address);

    $line_index = $this->findYamlLine($property_address);

    $previous_line = $this->originalYamlLines[$line_index - 1];

    Assert::assertEmpty($previous_line, $message);
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
    if (!is_array($property_address)) {
      $property_address = [$property_address];
    }

    $property_string = $this->getPropertyString($property_address);
    $message = $message ?? "The YAML file property $property_string has the expected value";

    $actual_value = NestedArray::getValue($this->parsedYamlData, $property_address);

    Assert::assertEquals($expected_value, $actual_value, $message);
  }

  /**
   * Asserts a property is formatted as expanded.
   *
   * @param string[] $property_address
   *   The address of the property.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertPropertyIsExpanded($property_address, $message = NULL) {
    $message = $message ?? 'The YAML property is formated as expanded.';

    $line_index = $this->findYamlLine($property_address);

    Assert::assertNotSame($line_index, FALSE, $message);
  }

  /**
   * Asserts a property is formatted as inline, with parent expanded.
   *
   * @param string[] $property_address
   *   The address of the property.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertPropertyIsInlined($property_address, $message = NULL) {
    $message = $message ?? 'The YAML property is formated as inline.';

    // Assert the property actually exists.
    $this->assertHasProperty($property_address);

    // If we can find a line for the property, it's expanded, not inline.
    $line_index = $this->findYamlLine($property_address);
    Assert::assertSame($line_index, FALSE, $message);

    // We want the property one level up to be expanded, so check for that.
    $property_parent_address = $property_address;
    array_pop($property_parent_address);
    $line_index = $this->findYamlLine($property_parent_address);
    Assert::assertNotSame($line_index, FALSE, $message);
  }

  /**
   * Finds the index for a property's line.
   *
   * @param string[] $property_address
   *   The address of the property.
   *
   * @return int|false
   *   The line number if the property is found on its own line. FALSE if the
   *   property is not found.
   */
  public function findYamlLine($property_address) {
    // A copy of the property address array to search with destructively.
    $property_address_search = $property_address;

    // Get an array of the original YAML lines, using string keys so that
    // we can use array_slice() on it without losing the line numbers.
    $yaml_string_lines = [];
    foreach ($this->originalYamlLines as $index => $line) {
      $yaml_string_lines["line_{$index}"] = $line;
    }

    // Look for the each property in the property address.
    // The current address level.
    $level = 0;
    $last_found_level = NULL;
    // A copy of the YAML lines array to search in destructively.
    $yaml_string_lines_slice_to_search = $yaml_string_lines;

    while ($property_address_search) {
      // (Can't array_shift() in the while() as a property may be a 0.)
      $property = array_shift($property_address_search);

      // The expected number of indented spaces for this level's property.
      $indent_count = $level * 2;

      if (is_numeric($property)) {
        // Numeric property.
        // We know how many children in we need to be.
        // Check the prior children, if any.
        $previous_lines = array_slice($yaml_string_lines_slice_to_search, 0, $property);

        // All the previous lines should have the right indent.
        foreach ($previous_lines as $line) {
          if (!preg_match("@^ {{$indent_count}}- @", $line)) {
            // A previous line is not a child of the previous level property:
            // it does not have enough child numeric properties.
            return FALSE;
          }
        }

        $expected_line_array = array_slice($yaml_string_lines_slice_to_search, $property, 1);
        $expected_line = reset($expected_line_array);
        if (!preg_match("@^ {{$indent_count}}- @", $expected_line)) {
          // The expected line is not a child numeric property.
          return FALSE;
        }

        $last_found_level = $level;
        $found_line_key = key($expected_line_array);
        $found_line_index = substr($line_key, 5);
      }
      else {
        // String property.
        foreach ($yaml_string_lines_slice_to_search as $line_key => $line) {
          if (!preg_match("@^ {{$indent_count}}@", $line)) {
            // We've reached a line that is not a child of the previous property:
            // we've failed to find the current one as a child of it.

            break;
          }

          if (preg_match("@^ {{$indent_count}}{$property}:@", $line)) {
            $last_found_level = $level;
            $found_line_key = $line_key;
            $found_line_index = substr($line_key, 5);

            break;
          }
        } // foreach YAML
      }

      // The break statements come here.

      if ($last_found_level != $level) {
        // We didn't find anything for this level: fail.
        return FALSE;
      }

      // Trim the YAML lines to search in, so we start at the line after
      // the one we've just found.
      $yaml_string_lines_slice_to_search = array_slice($yaml_string_lines, $found_line_index + 1);

      $level++;
    } // foreach property

    return $found_line_index;
  }

  protected function getPropertyString($property_address) {
    return implode(':', $property_address);
  }

}
