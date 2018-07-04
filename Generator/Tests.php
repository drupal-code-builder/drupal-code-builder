<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Component generator: tests.
 */
class Tests extends PHPFile {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    // Properties acquired from the requesting component.
    $root_component_properties = [
      'readable_name',
      'camel_case_name',
    ];
    foreach ($root_component_properties as $property_name) {
      $data_definition[$property_name] = [
        'acquired' => TRUE,
      ];
    }

    return $data_definition;
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
    $module_root_name = $this->component_data['camel_case_name'];
    $test_file_name = $module_root_name . "Test.php";

    return array(
      'path' => 'src/Tests',
      'filename' => $test_file_name,
      'body' => $this->fileContents(),
      'build_list_tags' => ['code', 'tests'],
    );
  }

  /**
   * Return the summary line for the file docblock.
   */
  function fileDocblockSummary() {
    $module_readable_name = $this->component_data['readable_name'];
    return "Contains tests for the $module_readable_name module.";
  }

  /**
   * Return the main body of the file code.
   */
  function code_body() {
    $module_root_name = $this->component_data['root_component_name'];
    $module_camel_case = $this->component_data['camel_case_name'];
    $module_readable_name = $this->component_data['readable_name'];

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
