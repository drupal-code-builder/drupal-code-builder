<?php

namespace DrupalCodeBuilder\Test\Unit\Parsing;

use DrupalCodeBuilder\Utility\NestedArray;
use PHPUnit\Framework\Assert;

/**
 * Helper class for parsing and testing annotations.
 *
 * Get this from the PHPTester with:
 * @code
 * $annotation_tester = $php_tester->getAnnotationTesterForClass();
 * @endcode
 */
class AnnotationTester {

  /**
   * The original docblock text.
   */
  protected $docblockText;

  /**
   * The class of the whole annotation.
   *
   * @var string.
   */
  protected $annotationClass;

  /**
   * The plain data structure of the annotation.
   *
   * @var array
   */
  protected $data;

  /**
   * The text contents of the annotation, for an ID-only annotation.
   */
  protected $contents;

  /**
   * The classes used within the annotation.
   *
   * An array whose keys are addresses in the $data array (separated with a ':')
   * and whose values are the classes.
   *
   * @var array
   */
  protected $dataAnnotationClasses;

  /**
   * Construct a new AnnotationTester.
   *
   * @param string $docblock_text
   *   The docblock text that should be tested.
   */
  public function __construct($docblock_text) {
    $this->docblockText = $docblock_text;

    $this->parseAnnotation($docblock_text);
  }

  /**
   * Does a debug dump() of the annotation text.
   */
  public function dump() {
    dump($this->docblockText);
  }

  /**
   * Parse the annotation text into data structures.
   *
   * @param string $docblock_text
   *   The docblock text that should be tested.
   */
  protected function parseAnnotation($docblock_text) {
    $lines = explode("\n", $docblock_text);

    // We assemble two structures:
    // - the structure of all keys and values
    // - a partial structure of any annotation classes that apply to values.
    $data = [];
    $classes = [];

    // Find the start and end of the annotation.
    foreach ($lines as $index => $line) {
      if (preg_match('/^ \* @\w+\(.+\)$/', $line)) {
        $only_line = $line;
        break;
      }

      if (preg_match('/^ \* @\w+\($/', $line)) {
        $start_index = $index;
      }
      if (preg_match('/^ \* +\)$/', $line)) {
        $end_index = $index;
        break;
      }
    }

    // Handle the single-line case.
    if (isset($only_line)) {
      // Special case: single-line, plugin ID only annotation.
      $matches = [];
      if (preg_match('/^ \* @(?P<class>\w+)\("(?P<contents>\w+)"\)$/', $only_line, $matches)) {
        $this->annotationClass = $matches['class'];
        $this->contents = $matches['contents'];

        return;
      }
    }

    // Discard everything around the annotation.
    $lines = array_slice($lines, $start_index, $end_index - $start_index + 1);

    // Trim the PHP comment formatting.
    $lines = array_map(function($line) {
      return preg_replace("/^ \* /", '', $line);
    }, $lines);

    $current_parent = [];
    $current_indent_level = 0;
    foreach ($lines as $line) {
      // Popping out of a nested array or annotation class.
      if (preg_match('/^ *[)}],/', $line)) {
        array_pop($current_parent);

        continue;
      }

      // Special case: first line.
      // Extract the annotation class.
      $matches = [];
      if (preg_match('/^@(?P<class>\w+)\(/', $line, $matches)) {
        $this->annotationClass = $matches['class'];

        continue;
      }

      // Special case: last line.
      if ($line == ')') {
        continue;
      }

      // The key can be explicit, or if not given, an implicit numeric key.
      if (preg_match('@ = @', $line)) {
        // Line with a key.
        // Extract the key and whatever is to the right of it.
        // Only root keys of an annotation class are not quoted; deeper ones
        // are.
        $matches = [];
        // Keys may contain word characters and hyphens.
        $matched = preg_match('/^(?P<indent_spaces> +)"?(?P<key>[\w-]+)"? = (?P<value>.*)$/', $line, $matches);
        if (!$matched) {
          throw new \Exception("Unable to match annotation line: $line");
        }

        //dump($matches);

        $indent = strlen($matches['indent_spaces']) / 2;

        $key = $matches['key'];
        $value = $matches['value'];

        // Numeric keys should not be explicit, so should not have been found
        // with an '='.
        if (is_numeric($key)) {
          Assert::fail("Explicit numric key found for annotation line: '$line'.");
        }
      }
      else {
        $parent_array = NestedArray::getValue($data, $current_parent);

        $parent_count = count($parent_array);
        $key = $parent_count;
        $value = trim($line);
      }

      $address = $current_parent;
      $address[] = $key;

      // There are following cases for the value:
      // id = "kitty_cat",
      // "kitty_cat"
      // label = @Translation("Kitty Cat"),
      // label_count = @PluralTranslation(
      // handlers = {
      $value_matches = [];

      // key = @Translation("Kitty Cat"),
      if (preg_match('/^@(?P<class>\w+)\("(?P<value>.*)"\)/', $value, $value_matches)) {
        // dump($value_matches);
        NestedArray::setValue($data, $address, $value_matches['value']);

        $flat_address = implode(':', $address);
        $classes[$flat_address] = $value_matches['class'];
        continue;
      }
      // key = "kitty_cat",
      // key = TRUE,
      // Match these cases after the annotation-wrapped value, as otherwise
      // that will get caught here.
      // Non-greedy match so that a possible final '"' doesn't get included in
      // the extraction.
      if (preg_match('/^"?(?P<value>.+?)"?,$/', $value, $value_matches)) {
        NestedArray::setValue($data, $address, $value_matches['value']);
        continue;
      }
      // key = @PluralTranslation(
      if (preg_match('/^@(?P<class>\w+)\($/', $value, $value_matches)) {
        // Start a new nesting level.
        NestedArray::setValue($data, $address, []);

        $flat_address = implode(':', $address);
        $classes[$flat_address] = $value_matches['class'];

        // Got one level in.
        $current_parent[] = $key;
        continue;
      }
      // handlers = {
      if ($value == '{') {
        // Start a new nesting level.
        NestedArray::setValue($data, $address, []);
        $current_parent[] = $key;
        continue;
      }

      throw new \Exception("Unable to match annotation value in line: '$line' for value: '$value'");
    }

    $this->data = $data;
    $this->dataAnnotationClasses = $classes;
  }

  /**
   * Assert the class for the whole annotation.
   *
   * @param $class
   *   The expected class.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertAnnotationClass($class_name, $message = NULL) {
    $message = $message ?? "The annotation has the expected class {$class_name}.";

    Assert::assertEquals($class_name, $this->annotationClass, $message);
  }

  /**
   * Assert the content of a text-only annotation.
   *
   * @param string $content
   *   The expected content.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertAnnotationTextContent($content, $message = NULL) {
    $message = $message ?? "The annotation has the expected content {$content}.";

    Assert::assertEquals($content, $this->contents, $message);
  }

  /**
   * Asserts the annotation's root properties, in the given order.
   *
   * @param string[] $property_names
   *   An array of property names.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasRootProperties($property_names, $message = NULL) {
    $message = $message ?? "The annotation has the expected properties: " . implode(', ', $property_names);

    Assert::assertSame($property_names, array_keys($this->data), $message);
  }

  /**
   * Asserts a set of properties, in the given order.
   *
   * @param string[] $property_names
   *   An array of property names.
   * @param string[] $parent_address
   *   The address of the parent property. An array address for the property;
   *   may be a scalar string for a top-level property.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasProperties($property_names, $parent_address, $message = NULL) {
    if (!is_array($parent_address)) {
      $parent_address = [$parent_address];
    }

    $property_string = $this->getPropertyString($parent_address);
    $property_names_string = implode(', ', $property_names);

    $message = $message ?? "The property {$property_string} has the expected child properties {$property_names_string} in the expected order.";

    $child_value = NestedArray::getValue($this->data, $parent_address);

    Assert::assertSame($property_names, array_keys($child_value), $message);
  }

  /**
   * Assert the annotation has the given property.
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
    $message = $message ?? "The annotation has the expected property $property_string.";

    Assert::assertTrue(NestedArray::keyExists($this->data, $property_address), $message);
  }

  /**
   * Assert the annotation does not have the given property.
   *
   * @param mixed $property_address
   *   The address of the property. An array address for the property; may be
   *   a scalar string for a top-level property.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertNotHasProperty($property_address, $message = NULL) {
    if (!is_array($property_address)) {
      $property_address = [$property_address];
    }

    $property_string = $this->getPropertyString($property_address);
    $message = $message ?? "The annotation does not have the property $property_string.";

    Assert::assertFalse(NestedArray::keyExists($this->data, $property_address), $message);
  }

  /**
   * Assert the annotation property has the given value.
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
    $message = $message ?? "The annotation property $property_string has the expected value";

    $actual_value = NestedArray::getValue($this->data, $property_address);

    Assert::assertEquals($expected_value, $actual_value, $message);
  }

  /**
   * Assert the annotation property uses the translation annotation class.
   *
   * @param mixed $property_address
   *   The address of the property. An array address for the property; may be
   *   a scalar string for a top-level property.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertPropertyHasTranslation($property_address, $message = NULL) {
    $this->assertPropertyHasAnnotationClass($property_address, 'Translation', $message);
  }

  /**
   * Assert the annotation property uses the given annotation class.
   *
   * @param mixed $property_address
   *   The address of the property. An array address for the property; may be
   *   a scalar string for a top-level property.
   * @param $class
   *   The expected class.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertPropertyHasAnnotationClass($property_address, $class, $message = NULL) {
    if (!is_array($property_address)) {
      $property_address = [$property_address];
    }

    $property_string = $this->getPropertyString($property_address);
    $message = $message ?? "The annotation property $property_string has the class $class.";

    Assert::assertEquals($class, $this->dataAnnotationClasses[$property_string], $message);
  }

  protected function getPropertyString($property_address) {
    return implode(':', $property_address);
  }

}
