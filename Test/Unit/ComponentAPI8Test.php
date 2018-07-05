<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the API documentation file component.
 */
class ComponentAPI8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test generating a module with an api.php file.
   */
  public function testModuleGenerationApiFile() {
    $mb_task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
    $this->assertTrue(is_object($mb_task_handler_generate), "A task handler object was returned.");

    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'readme' => FALSE,
      'api' => TRUE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.api.php',
    ], $files);

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.api.php", $files, "The files list has an api.php file.");

    $api_file = $files["$module_name.api.php"];

    $php_tester = new PHPTester($api_file);
    $php_tester->assertDrupalCodingStandards();

    // TODO: expand the docblock assertion for these.
    $this->assertContains("Hooks provided by the Test Module module.", $api_file, 'The API file contains the correct docblock header.');
    $this->assertContains("@addtogroup hooks", $api_file, 'The API file contains the addtogroup docblock tag.');
    $this->assertContains('@} End of "addtogroup hooks".', $api_file, 'The API file contains the closing addtogroup docblock tag.');
  }

}
