<?php

/**
 * @file
 * Contains ModuleBuilderTestBase.
 */

/**
 * Base class for PHPUnit tests.
 *
 * Contains helper methods and assertions.
 */
abstract class ModuleBuilderTestBase extends PHPUnit_Framework_TestCase {

  /**
   * Perform the factory setup, spoofing in the given core major version.
   *
   * @param $version
   *  A core major version number,
   */
  protected function setupModuleBuilder($version) {
    $environment = new \ModuleBuilder\Environment\TestsSampleLocation;
    $version_helper = new \ModuleBuilder\Environment\VersionHelperTestsPHPUnit;
    $version_helper->setFakeCoreMajorVersion(7);
    \ModuleBuilder\Factory::setEnvironment($environment, $version_helper);
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
    $mb_task_handler_generate = \ModuleBuilder\Factory::getTask('Generate', 'module');
    $root_generator = $mb_task_handler_generate->getRootGenerator();
    $component_data_info = $root_generator->getComponentDataInfo();

    // Perform final processing on the component data.
    // This prepares data, for example expands options such as hook presets.
    $mb_task_handler_generate->getRootGenerator()->processComponentData($component_data_info, $module_data);

    $files = $mb_task_handler_generate->generateComponent($module_data);

    return $files;
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
    $matches = array();
    $match = preg_match_all("[( +)$]m", $code, $matches);
    $this->assertEquals($match, 0, $message);
  }

  /**
   * Assert a string is correctly-formed PHP.
   *
   * @param $string
   *  The text of PHP to check. This is expected to begin with a '<?php' tag.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertWellFormedPHP($code, $message = NULL) {
    if (!isset($message)) {
      $message = "String evaluates as correct PHP.";
    }

    // Code passed to eval() should not have an opening PHP tag, which our
    // module code does. However, we can close a PHP tag at the start and then
    // our opening tag is acceptable.
    // It should be safe to eval as all of the functions will be prefixed with
    // the module name (unless something has gone horribly wrong, in which case
    // case the test should fail anyway.)
    $eval = eval('?>' . $code);
    // eval() returns FALSE if there is a parse error.
    $this->assertTrue($eval === NULL, $message);
  }

  /**
   * Assert a string contains the correct file header.
   *
   * @param $string
   *  The text to check for a docblock.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertFileHeader($string, $message = NULL) {
    $expected_string =
      "^<\?php\n" .
      "\n" .
      "/\*\*\n" .
      " \* @file.*\n" .
      " \* .*\n" .
      " \*/\n";

    $match = preg_match("[$expected_string]", $string);
    $this->assertEquals($match, 1, $message);
  }

  /**
   * Assert a string contains a PHP docblock for a hook.
   *
   * @param $string
   *  The text to check for a docblock.
   * @param $hook_name
   *  The full name of the hook, e.g. 'hook_menu'.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertHookDocblock($string, $hook_name, $message = NULL) {
    $docblock =
      "/**\n" .
      " * Implements {$hook_name}().\n" .
      " */";
    $position = strstr($string, $docblock);
    $this->assertTrue($position !== FALSE, $message);
  }

  /**
   * Assert a string does not contain a PHP docblock for a hook.
   *
   * @param $string
   *  The text to check for a docblock.
   * @param $hook_name
   *  The full name of the hook, e.g. 'hook_menu'.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertNoHookDocblock($string, $hook_name, $message = NULL) {
    $docblock =
      "/**\n" .
      " * Implements {$hook_name}().\n" .
      " */";
    $position = strstr($string, $docblock);
    $this->assertTrue($position === FALSE, $message);
  }

  /**
   * Assert a string contains a function declaration.
   *
   * @param $string
   *  The text to check for a function declaration.
   * @param $function_name
   *  The name of the function.
   * @param $message = NULL
   *  The assertion message.
   */
  function assertFunction($string, $function_name, $message = NULL) {
    $expected_regex = "^function {$function_name}\((.*)\)";

    $matches = array();
    $match = preg_match("[$expected_regex]m", $string, $matches);

    if (empty($message)) {
      $message = "The code string contains the function $function_name().";
    }

    $this->assertEquals($match, 1, $message);

    // Check the function parameters are properly formed.
    if (!empty($matches[1])) {
      $parameters = explode(', ', $matches[1]);

      $param_regex = '@
        ^
        ( \w+ \  ) ?  # type hint
        & ?           # pass by reference
        \$ \w+        # parameter name
        ( \  = \      # default value, one of:
          (
            \d+ |             # numerical
            [[:upper:]]+ |    # constant
            \' .* \' |        # string
            array\(\)         # empty array
          )
        ) ?
        @x';

      foreach ($parameters as $parameter) {
        $match = preg_match($param_regex, $parameter);

        $this->assertEquals($match, 1, "Function parameter '$parameter' for $function_name() is correctly formed.");
      }
    }
  }

  /**
   * Assert a string contains a function whose body contains specific code.
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
    // Extract the function's body from the whole string.
    $matches = [];
    $function_body_regex = "^function {$function_name}.*?{\n(.*)^}";
    $match = preg_match("[$function_body_regex]ms", $string, $matches);
    $function_body = $matches[1];

    // Quote the code, as it may contain regex characters.
    $function_code = preg_quote($function_code);
    $match = preg_match("[$function_code]", $function_body);

    $this->assertEquals($match, 1, "Expected function code was found in $function_name().");
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

    $this->assertFunction($code, $function_name, $message);
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
    $expected_regex = "^{$property} = {$value}$";

    $match = preg_match("[$expected_regex]m", $string);
    $this->assertEquals($match, 1, $message);
  }

}


function ddpr($data, $message = '') {
  if (!empty($message)) {
    print_r("\n" . $message . ':');
  }
  print_r($data);
}
