<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\Tests.
 */

namespace ModuleBuider\Generator;

/**
 * Component generator: tests.
 */
class Tests extends PHPFile {

  /**
   * Return an array of subcomponent types.
   */
  protected function requiredComponents() {
    // We have no subcomponents.
    return array();
  }

  /**
   * Build the code files.
   */
  function collectFiles(&$files) {
    $module_root_name = $this->base_component->component_data['module_root_name'];
    $test_file_name = "$module_root_name.test";

    // The key is arbitrary (at least so far!).
    $files['module.test'] = array(
      'path' => '', // Means base folder.
      'filename' => 'tests/' . $test_file_name,
      'body' => $this->file_contents(),
      'join_string' => "\n",
      'contains_classes' => TRUE,
    );
  }

  /**
   * Return the summary line for the file docblock.
   */
  function file_doc_summary() {
    $module_readable_name = $this->base_component->component_data['module_readable_name'];
    return "Contains tests for the $module_readable_name module.";
  }

  /**
   * Return the main body of the file code.
   */
  function code_body() {
    $module_root_name = $this->base_component->component_data['module_root_name'];
    $module_camel_case = $this->base_component->component_data['module_camel_case_name'];
    $module_readable_name = $this->base_component->component_data['module_readable_name'];

    $code = <<<EOT
/**
 * Test case.
 */
class {$module_camel_case}TestCase extends DrupalWebTestCase {

  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => t('$module_readable_name tests'),
      'description' => t('TODO: write me.'),
      'group' => t('$module_readable_name'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    // Call the parent with an array of modules to enable for the test.
    parent::setUp(array('$module_root_name'));

    // TODO: perform additional setup tasks here if required.
  }

  /**
   * Test the module's functionality.
   */
  function testTodoChangeThisName() {
    // TODO: write test!
  }

}

EOT;

    return array($code);
  }

}
