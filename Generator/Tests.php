<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Component generator: Simpletest test class.
 */
class Tests extends PHPFile {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('filename')
      ->setExpressionDefault("'src/Tests/' ~ get('..:root_name_pascal') ~ 'TestCase.php'");

    $definition->addProperties([
      'readable_name' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
      'root_name_pascal' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    // We have no subcomponents.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFileInfo() {
    $module_root_name = $this->component_data['root_name_pascal'];
    $test_file_name = $module_root_name . "TestCase.php";

    return [
      'path' => 'src/Tests',
      'filename' => $test_file_name,
      'body' => $this->fileContents(),
      'build_list_tags' => ['code', 'tests'],
    ];
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
  function phpCodeBody() {
    $module_root_name = $this->component_data['root_component_name'];
    $module_camel_case = $this->component_data['root_name_pascal'];
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

    return [$code];
  }

}
