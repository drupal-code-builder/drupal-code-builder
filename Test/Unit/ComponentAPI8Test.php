<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Task\AnalyzeModule;
use DrupalCodeBuilder\Test\Fixtures\File\MockableExtension;
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
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'readme' => FALSE,
      'api' => TRUE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.api.php',
    ], $files);

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.api.php", $files, "The files list has an api.php file.");

    $api_file = $files["$module_name.api.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $api_file);
    $php_tester->assertDrupalCodingStandards();

    // TODO: expand the docblock assertion for these.
    $this->assertStringContainsString("Hooks provided by the Test Module module.", $api_file, 'The API file contains the correct docblock header.');
    $this->assertStringContainsString("@addtogroup hooks", $api_file, 'The API file contains the addtogroup docblock tag.');
    $this->assertStringContainsString('@} End of "addtogroup hooks".', $api_file, 'The API file contains the closing addtogroup docblock tag.');
  }

  /**
   * Tests with an existing api file.
   *
   * @group existing
   */
  public function testExistingAPIFile() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      'readme' => FALSE,
      'plugin_types' => [
        0 => [
          'discovery_type' => 'annotation',
          'plugin_type' => 'cat_feeder',
        ]
      ],
      'api' => TRUE,
    ];

    // Mock the AnalyzeModule task, since it expects to read files directly
    // and doesn't use DrupalExtension.
    // Use procedural hook invocation until https://github.com/drupal-code-builder/drupal-code-builder/issues/247
    // is fixed.
    $analyze_module = $this->prophesize(AnalyzeModule::class);
    $analyze_module->getSanityLevel()->willReturn('component_data_processed');
    $analyze_module->getInventedHooks('test_module')
      ->willReturn([
        'analysed_hook' => 'array $foo, array $bar',
      ]);

    $container = \DrupalCodeBuilder\Factory::getContainer();
    $container->set('AnalyzeModule', $analyze_module->reveal());

    $extension = new MockableExtension('module', __DIR__ . '/../Fixtures/modules/existing/');

    $api_file = <<<'EOPHP'
      <?php

      /**
       * @file
       * Describes hooks provided by the Test Module module.
       */

      /**
       * @addtogroup hooks
       * @{
       */

      /**
       * Does a thing.
       */
      function hook_existing_hook() {
        // Sample code does a thing.
        $foo = 'foo';
      }

      EOPHP;

    $extension->setFile('%module.api.php', $api_file);

    $files = $this->generateModuleFiles($module_data, $extension);
    $api_file = $files['test_module.api.php'];

    $php_tester = new PHPTester($this->drupalMajorVersion, $api_file);
    // Skip the return type sniff, as the generated hook function won't have
    // a return type.
    $php_tester->assertDrupalCodingStandards(['Drupal.Commenting.FunctionComment.MissingReturnType']);

    $php_tester->assertHasFunction('hook_analysed_hook');
    $php_tester->assertHasFunction('hook_cat_feeder_info_alter');
    $php_tester->assertHasFunction('hook_existing_hook');

    // TODO: expand the docblock assertion for these.
    $this->assertStringContainsString("Hooks provided by the Test Module module.", $api_file, 'The API file contains the correct docblock header.');
    $this->assertStringContainsString("@addtogroup hooks", $api_file, 'The API file contains the addtogroup docblock tag.');
    $this->assertStringContainsString('@} End of "addtogroup hooks".', $api_file, 'The API file contains the closing addtogroup docblock tag.');
  }

}
