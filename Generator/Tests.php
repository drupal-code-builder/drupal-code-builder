<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\Tests.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Component generator: tests.
 */
class Tests extends PHPFile {

  /**
   * {@inheritdoc}
   */
  public static function requestedComponentHandling() {
    return 'singleton';
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    // We have no subcomponents.
    return array();
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $module_root_name = $this->root_component->component_data['camel_case_name'];
    $test_file_name = $module_root_name . "Test.php";

    // The key is arbitrary (at least so far!).
    $files['%module.test'] = array(
      'path' => 'src/Tests',
      'filename' => $test_file_name,
      'body' => $this->file_contents(),
      'join_string' => "\n",
      'contains_classes' => TRUE,
    );
    return $files;
  }

  /**
   * Return the summary line for the file docblock.
   */
  function file_doc_summary() {
    $module_readable_name = $this->root_component->component_data['readable_name'];
    return "Contains tests for the $module_readable_name module.";
  }

  /**
   * Return the main body of the file code.
   */
  function code_body() {
    $module_root_name = $this->root_component->component_data['root_name'];
    $module_camel_case = $this->root_component->component_data['camel_case_name'];
    $module_readable_name = $this->root_component->component_data['readable_name'];

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
