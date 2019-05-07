<?php

/**
 * @file
 * Contains TestBase.
 */

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use PHP_CodeSniffer;

/**
 * Base class for unit tests that work with the generator system.
 *
 * Contains helper methods and assertions.
 */
abstract class TestBase extends TestCase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = NULL;

  /**
   * This expects the class property $drupalMajorVersion to be defined.
   *
   * Classes that don't have this yet should override this.
   */
  protected function setUp() {
    $this->setupDrupalCodeBuilder($this->drupalMajorVersion);
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
   * Generate module files from a data array.
   *
   * @param $module_data
   *  An array of module data for the module generator.
   *
   * @param
   *  An array of files.
   */
  protected function generateModuleFiles($module_data) {
    $mb_task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
    $component_data_info = $mb_task_handler_generate->getRootComponentDataInfo();

    $files = $mb_task_handler_generate->generateComponent($module_data);

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
  protected function assertFileCount($expected_count, $actual_files_array, $message = NULL) {
    $message = $message ?? "Expected number of files is returned:";
    $message .= ' ' . print_r(array_keys($actual_files_array), TRUE);

    $this->assertCount($expected_count, $actual_files_array, $message);
  }

  /**
   * Asserts the names of the generated files.
   *
   * @param string[] $filenames
   *   An array of filenames.
   * @param array $actual_files
   *   The array of files returned from the generator.
   */
  protected function assertFiles($filenames, $actual_files) {
    $actual_file_names = array_keys($actual_files);

    sort($filenames);
    sort($actual_file_names);

    $this->assertEquals($filenames, $actual_file_names, "The expected files were generated.");
  }

  /**
   * Assert a string has no whitespace at line ends.
   *
   * @param $string
   *  The code string.
   * @param $message = NULL
   *  The assertion message.
   */
  protected function assertNoTrailingWhitespace($code, $message = NULL) {
    $message = $message ?? "The code has no trailing whitespace.";

    $whitespace_regex = "[( +)$]m";

    $this->assertNotRegExp($whitespace_regex, $code, $message);
  }

  /**
   * Assert a class file's formatting and line spacing.
   *
   * This checks that blocks of code are in the right order and spaced out
   * correctly. It does not check the actual contents of the code, such as class
   * names, function names, and so on.
   *
   * @param $string
   *  The code string for the class file.
   * @param $message = NULL
   *  The assertion message.
   */
  protected function assertClassFileFormatting($code) {
    $lines = explode("\n", $code);

    $empty_line_regex = '[^$]';

    $this->assertRegExp("@^<\?php@", array_shift($lines), 'The first line of the file is the PHP open tag.');
    $this->assertRegExp($empty_line_regex, array_shift($lines), 'The second line of the file is empty.');

    $docblock_regexes = [
      'start'   => '@^/\*\*@',
      // Need to ensure this doesn't also match the end line, so use a negative
      // lookahead to prevent matching ' */'.
      'middle'  => '@^ \*(?!/)@',
      'end'     => '@^ \*/@',
    ];

    $this->assertRegExp('@^namespace @', array_shift($lines), 'The file has a namespace declaration.');
    $this->assertRegExp($empty_line_regex, array_shift($lines), 'There is a blank line after the namespace.');

    // Import statements are optional.
    $import_regexes = [
      // Need to ensure this doesn't also match the end line.
      'repeated'  => '@use @',
      'end'       => $empty_line_regex,
    ];
    $this->helperRegexpRepeatedLines($lines, $import_regexes, [], 'import statements');

    // Class docblock.
    $this->helperRegexBlockLines($lines, $docblock_regexes, 'docblock');

    $this->assertRegExp('@^class @', array_shift($lines), 'The file has a class declaration.');

    // Multiple class body lines.
    $class_regexes = [
      // Empty line or indented.
      'repeated'  => '@^$|^  @',
      'end'       => '@}@',
    ];
    $this->helperRegexpRepeatedLines($lines, $class_regexes, ['expect_end_if_empty' => TRUE], 'class');

    $this->assertRegExp($empty_line_regex, array_shift($lines), 'There is a blank line after the class.');

    $this->assertEmpty($lines, "The end of the lines was reached.");
  }

  /**
   * Helper for assertClassFileFormatting() for an optional variable-length block.
   *
   * @param &$lines
   *  An array of code lines, passed by reference. The code to test for should
   *  be at the start of this; code which follows is ignored. Lines which are
   *  tested are removed.
   * @param $regexes
   *  An array of regexes to test for the block:
   *  - 'repeated': The regex for the lines of the block. This must not match
   *    the end line.
   *  - 'end': The regex for the terminal line of the block, such as a blank
   *    line which is only expected if the block is present. This may be empty
   *    to indicate there is no such line. This is not checked for if the
   *    block is found to be empty, in other words, if 'repeated' does not match
   *    any lines.
   * @param $options
   *  (optional) An array of additional options. May include:
   *    - 'min_count': The minimum number of lines of the block, that is, the
   *      the minium number of times that the 'repeated' regex is expected to
   *      match.
   *    - 'expect_end_if_empty': A boolean indicating whether the 'end' regex is
   *      expected if no lines match the 'repeated' regex. If TRUE, it is not
   *      possible for the block to be completely empty: the end line is
   *      required. If FALSE, then the absence of repeated lines means the end
   *      line won't be looked for, and the entire block is allowed to contain
   *      no lines at all.
   * @param $block_name = 'block'
   *  A string to describe the block, to use in assertion messages.
   */
  protected function helperRegexpRepeatedLines(&$lines, $regexes, $options = [], $block_name = 'block') {
    $options += [
      'min_count' => 0,
      'expect_end_if_empty' => FALSE,
    ];

    $lines_count = 0;

    // Keep taking off lines until one doesn't match.
    // Need to test the loop against is_null() rather than just truthiness, as
    // the shifted line may be empty.
    while (!is_null($line = array_shift($lines))) {
      try {
        $this->assertRegExp($regexes['repeated'], $line, "The line index $lines_count of the $block_name is as expected.");
        // Count a successful line.
        $lines_count++;
      }
      catch (ExpectationFailedException $e) {
        // Restore the line that didn't match.
        array_unshift($lines, $line);

        break;
      }
    }

    // Check we get the expected minimum of repeated lines.
    $this->assertGreaterThanOrEqual($options['min_count'], $lines_count, "The $block_name has at least {$options['min_count']} middle line");

    // Test the terminal line regex if there is one and it's expected.
    // An end line is only expected if a regex was given for it.
    $expect_end_line = !empty($regexes['end']);
    if ($expect_end_line) {
      // Additionally, if no middle lines were found, we only expect an end line
      // if the option for that was set.
      if (($lines_count == 0)) {
        $expect_end_line = $options['expect_end_if_empty'];
      }
    }

    if ($expect_end_line) {
      $this->assertRegExp($regexes['end'], array_shift($lines), "The end line of the $block_name is as expected.");
    }
  }

  /**
   * Helper for assertClassFileFormatting() for a variable-length block.
   *
   * @param &$lines
   *  An array of code lines, passed by reference. The code to test for should
   *  be at the start of this; code which follows is ignored. Lines which are
   *  tested are removed.
   * @param $regexes
   *  An array of regexes to test for the block:
   *  - 'start': The regex for the first line of the block. This may be left
   *    empty to indicate there is no different start line.
   *  - 'middle': The regex for any number of intermediate lines. This must not
   *    match the end line.
   *  - 'end': The regex for the last line of the block.
   * @param $block_name = 'block'
   *  A string to describe the block, to use in assertion messages.
   */
  protected function helperRegexBlockLines(&$lines, $regexes, $block_name = 'block') {
    if (!empty($regexes['start'])) {
      $this->assertRegExp($regexes['start'], array_shift($lines), "The first line of the $block_name is as expected.");
    }

    $middle_lines_count = 0;
    while ($line = array_shift($lines)) {
      try {
        $this->assertRegExp($regexes['middle'], $line, "The intermediate line of the $block_name is as expected.");
        // Count a successful middle line.
        $middle_lines_count++;
      }
      catch (ExpectationFailedException $e) {
        // Catch a failed middle line assertion failure. This is expected to be
        // the last line, so don't allow this to fail the test.
        break;
      }
    }

    // There should be at least one middle line.
    $this->assertGreaterThanOrEqual(1, $middle_lines_count, "The $block_name has at least one middle line");

    // Test the line that failed the middle regex, but this time against the end
    // regex.
    $this->assertRegExp($regexes['end'], $line, "The last line of the $block_name is as expected.");
  }

  /**
   * Assert a string contains a docblock with specified lines.
   *
   * @param $lines
   *  An array of the expected lines of the docblock, without the docblock
   *  formatting. For example, for this docblock the first item of the array
   *  would be:
   *    'Assert a string contains a docblock with specified lines.'
   * @param $string
   *  The text to check for a docblock.
   * @param $message = NULL
   *  (optional) The assertion message.
   * @param $indent
   *  (optional) The number of spaces the expected docblock is indented by.
   *  Internal use only. Defaults to 0.
   */
  function assertDocBlock($lines, $string, $message = NULL, $indent = 0) {
    if (!isset($message)) {
      $message = "Code contains a docblock with the expected lines.";
    }

    $indent = str_repeat('\ ', $indent);

    $expected_regex = "$indent/\*\*\n" ;

    foreach ($lines as $line) {
      $line = preg_quote($line);

      if (empty($line)) {
        $expected_regex .= "$indent \*\n";
      }
      else {
        $expected_regex .= "$indent \* $line\n";
      }
    }

    // Would be nice to check that there's the closing newline, but a docblock
    // retrieved by PHP Parser doesn't contain it.
    // The PHP CodeSniffer assertion should pick up a problem with that though.
    $expected_regex .= "$indent \*/";

    // Wrap the regex.
    $expected_regex = '[' . $expected_regex . ']';

    $this->assertRegExp($expected_regex, $string, $message);
  }

  /**
   * Assert a string contains a docblock, in a class, with specified lines.
   *
   * @param $lines
   *  An array of the expected lines of the docblock, without the docblock
   *  formatting. For example, for this docblock the first item of the array
   *  would be:
   *    'Assert a string contains a docblock with specified lines.'
   * @param $string
   *  The text to check for a docblock.
   * @param $message = NULL
   *  (optional) The assertion message.
   */
  function assertDocBlockInClass($lines, $string, $message = NULL) {
    $this->assertDocBlock($lines, $string, $message, 2);
  }

  /**
   * Assert a string contains the correct file header.
   *
   * TODO: Remove this; it's completely covered by PHPCS sniff
   * Drupal.Commenting.FileComment.FileTag.
   *
   * @param $string
   *  The text to check for a docblock.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertFileHeader($string, $message = NULL) {
    $expected_regex =
      "[" .
      "^<\?php\n" .
      "\n" .
      "/\*\*\n" .
      " \* @file.*\n" .
      " \* .*\n" .
      " \*/\n" .
      "]";

    $this->assertRegExp($expected_regex, $string, $message);
  }

  /**
   * Assert a string contains a class declaration.
   *
   * TODO: add checking of inheritance, interfaces, namespace.
   *
   * @param $class_name
   *  The name of the class.
   * @param $string
   *  The text to check for a class declaration.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertClass($class_name, $string, $message = NULL) {
    $expected_regex = "@^class {$class_name}@m";

    $this->assertRegExp($expected_regex, $string, $message);
  }

  /**
   * Assert a string contains a namespace declaration.
   *
   * @param $namespace_pieces
   *  A PHP namespace, as an array of pieces to concatenate with '\'.
   * @param $string
   *  The text to check for a namespace declaration.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertNamespace($namespace_pieces, $string, $message = NULL) {
    $namespace = implode('\\', $namespace_pieces);
    $namespace = preg_quote($namespace);
    $expected_regex = "@^namespace {$namespace};@m";

    $this->assertRegExp($expected_regex, $string, $message);
  }

  /**
   * Assert a string contains an import statement.
   *
   * @param $qualified_class_pieces
   *  The expected qualified class name, as an array of pieces to concatenate
   *  with '\'.
   * @param $string
   *  The text to check for a class declaration.
   * @param $message = NULL
   *  (optional) The assertion message.
   */
  function assertClassImport($qualified_class_pieces, $string, $message = NULL) {
    $qualified_class = implode('\\', $qualified_class_pieces);
    $qualified_class = preg_quote($qualified_class);
    $expected_regex = "@^use {$qualified_class};@m";

    $this->assertRegExp($expected_regex, $string, $message);
  }

  /**
   * Assert a string contains a class annotation.
   *
   * @param $annotation_class
   *  The expected class of the annotation block.
   * @param $annotation_values
   *  An array of properties and values the annotation should have. To only
   *  check that a property is present, without checking its value, pass in
   *  a value of NULL for the array key.
   * @param $string
   *  The text to check for a class annotation.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertClassAnnotation($annotation_class, $annotation_values, $string, $message = NULL) {
    // First, check the class has something that looks like an annotation.
    $annotation_regex = "[
    ^ /\*\* \\n # Open docblock.
    ^ \ \* \ .+ \\n # Docblock first line.
    ^ \ \* \\n # Empty line.
    ^ \ \* \ @$annotation_class \( \\n    # Annotation class.
    ( ^ \ \* \ {3} \w+ \ = \ .* , \\n )+  # Multiple properties.
    ^ \ \* \ \) \\n
    ^ \ \* / \\n # Close docblock.
    ^ class
    ]mx";
    $this->assertRegExp($annotation_regex, $string, $message);

    // Now check the annotation properties and values.
    $matches = [];
    preg_match_all($annotation_regex, $string, $matches);
    $annotation_string = $matches[0][0];

    $annotation_property_regex = "[ ^ \ \* \ {3} (?<key> \w+) \ = \ (?<value> .+? ) ,? \\n ]xm";
    $matches = [];
    preg_match_all($annotation_property_regex, $annotation_string, $matches,  PREG_SET_ORDER);

    $found_annotation_values = [];
    // Group the found annotation keys and values.
    foreach ($matches as $match_set) {
      $found_annotation_values[$match_set['key']] = $match_set['value'];
    }

    foreach ($annotation_values as $expected_key => $expected_value) {
      $this->assertArrayHasKey($expected_key, $found_annotation_values, "The annotation has the property $expected_key.");

      if (!is_null($expected_value)) {
        $found_value = trim($found_annotation_values[$expected_key], '"\'');
        $this->assertEquals($expected_value, $found_value, "The annotation has the expected value for the property $expected_key.");
      }
    }
  }

  /**
   * Assert a string contains a class property declaration.
   *
   * @param $function_name
   *  The name of the function.
   * @param $string
   *  The text to check for a function declaration.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertClassProperty($property_name, $string, $message = NULL) {
    $expected_regex =
      "[" .
      "  /\*\*\n" .
      "   \* .*\n" . // Property name.
      "   \*\n" .
      "   \* @var.*\n" . // @var.
      "   \*/\n" .
      '  (public|protected|private)? \\$' . $property_name . ";\n" .
      "]";

    $this->assertRegExp($expected_regex, $string, $message);
  }

  /**
   * Assert a string contains a function declaration.
   *
   * @param $function_name
   *  The name of the function.
   * @param $string
   *  The text to check for a function declaration.
   * @param $message = NULL
   *  The assertion message.
   * @param $indent
   *  (optional) The number of spaces the function declaration is indented by.
   *  Internal use only. Defaults to 0.
   */
  function assertFunction($function_name, $string, $message = NULL, $indent = 0) {
    if (empty($message)) {
      $message = "The code string contains the function $function_name().";
    }

    $indent = str_repeat('\ ', $indent);

    $expected_regex = "[
      ^
      {$indent}
      (?<modifiers> \w+ \ ) *
      function \  {$function_name} \( (?<params> .* ) \)
    ]mx";
    $this->assertRegExp($expected_regex, $string, $message);

    // Run the regex again so that we can pull out matches.
    $matches = array();
    $match = preg_match($expected_regex, $string, $matches);

    // Check the function parameters are properly formed.
    if (!empty($matches['params'])) {
      $parameters = explode(', ', $matches['params']);

      foreach ($parameters as $parameter) {
        $this->assertFunctionParameter('', $parameter, "Function parameter '$parameter' for $function_name() is correctly formed.");
      }
    }
  }

  /**
   * Assert a code string contains a function with the specified parameters.
   *
   * This doesn't check for the declaration being well-formed, so use
   * assertFunction() as well.
   *
   * @param $function_name
   *  The name of the function.
   * @param $parameters
   *  An array of parameter names, in the expected order, without the initial $.
   * @param $string
   *  The string containing the parameters from the function declaration.
   * @param $message = NULL
   *  (optional) The assertion message.
   */
  public function assertFunctionHasParameters($function_name, $parameters, $string, $message = NULL) {
    $function_capturing_regex = "[function {$function_name}\((?<params>.*)\)]";
    $matches = array();
    $match = preg_match($function_capturing_regex, $string, $matches);

    if (empty($matches['params'])) {
      self::fail("Function $function_name was not found in the string.");
    }

    $found_parameters = explode(', ', $matches['params']);

    $this->assertEquals(count($parameters), count($found_parameters), "The expected number of parameters was found.");

    foreach ($parameters as $parameter) {
      $found_parameter = array_shift($found_parameters);
      $this->assertFunctionParameter($parameter, $found_parameter, "Function parameter '$parameter' for $function_name() is correctly formed.");
    }
  }

  /**
   * Assert a function parameter is correctly formed.
   *
   * @param $expected_parameter_name
   *  The name of the parameter that is expected in the given $parameter,
   *  without the initial $. If this is empty, then the name is not checked.
   * @param $parameter
   *  The parameter from the function declaration, without the trailing comma.
   * @param $message = NULL
   *  (optional) The assertion message.
   */
  protected function assertFunctionParameter($expected_parameter_name, $parameter, $message = NULL) {
    if (empty($message)) {
      $message = "Function parameter '$parameter' is correctly formed.";
    }

    $param_regex = '@
      ^
      ( [\w\\\\] + \  ) ?  # type hint.
        # May be a qualified class: need FOUR \ to actually make 2!
        # Also, the /x modifier appears to not hold within character classes!
      & ?           # pass by reference
      \$ (?<parameter> \w+ )       # parameter name
      ( \  = \      # default value, one of:
        (
          \d+ |             # numerical
          [[:upper:]]+ |    # constant
          \' .* \' |        # string
          array\(\)         # empty array
        )
      ) ?
      @x';

    if (!empty($expected_parameter_name)) {
      $matches = array();
      $match = preg_match($param_regex, $parameter, $matches);
      $found_parameter_name = $matches['parameter'];

      $this->assertEquals($expected_parameter_name, $found_parameter_name, "The function parameter $expected_parameter_name has the expected name.");
    }

    $this->assertRegExp($param_regex, $parameter, $message);
  }

  /**
   * Assert a string contains a class method declaration.
   *
   * @param $function_name
   *  The name of the function.
   * @param $string
   *  The text to check for a function declaration.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertMethod($function_name, $string, $message = NULL) {
    $this->assertFunction($function_name, $string, $message, 2);
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
  function assertFunctionCode($string, $function_name, $function_code, $message = NULL) {
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
    $this->assertRegExp("[$function_body_regex]ms", $string, "The function is found in the string");

    $function_body = $matches[1];

    // Quote the code, as it may contain regex characters.
    $function_code = preg_quote($function_code);
    $expected_regex = "[$function_code]";

    $this->assertRegExp($expected_regex, $function_body, $message);
  }

  /**
   * Assert a string contains a hook implementation function declaration.
   *
   * @param $code
   *  The code to check for a function declaration.
   * @param $hook_name
   *  The full name of the hook, e.g. 'hook_menu'.
   * @param $module_name
   *  The name of the implementing module.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertHookImplementation($code, $hook_name, $module_name, $message = NULL) {
    $hook_short_name = substr($hook_name, 5);
    $function_name = $module_name . '_' . $hook_short_name;

    $this->assertFunction($function_name, $code, $message);
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
  function assertInfoLine($string, $property, $value, $message = NULL) {
    // Quote the given strings, as they may contain regex characters.
    $property = preg_quote($property);
    $value    = preg_quote($value);
    $expected_regex = "@^{$property} = {$value}$@m";

    $this->assertRegExp($expected_regex, $string, $message);
  }

}
