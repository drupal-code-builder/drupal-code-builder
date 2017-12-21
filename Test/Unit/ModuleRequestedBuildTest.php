<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Test the functionality for requesting only certain files to be generated.
 */
class ModuleRequestedBuildTest extends TestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(7);
  }

  /**
   * Test build request functionality.
   */
  function testModuleGenerationBuildRequest() {
    // Create a module, specifying limited build.
    // It is crucial to create a new module name, as we eval() the generated
    // code!
    $module_name = 'testmodule2';
    $module_data_base = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
        // These two hooks will go in the .module file.
        'hook_menu',
        'hook_block_info',
        // This goes in a tokens.inc file, and also has complex parameters.
        'hook_tokens',
        // This goes in the .install file.
        'hook_install',
      ),
      'readme' => TRUE,
      'api' => TRUE,
      'tests' => TRUE,
    );

    // Test the 'all' build list setting.
    $module_data = $module_data_base;
    $module_data['requested_build'] = array(
      'all' => TRUE,
    );

    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(7, $files, "Expected number of files are returned.");
    $this->assertArrayHasKey("$module_name.info", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.install", $files, "The files list has a .install file.");
    $this->assertArrayHasKey("$module_name.tokens.inc", $files, "The files list has a .tokens.inc file.");
    $this->assertArrayHasKey("$module_name.api.php", $files, "The files list has a .api.php file.");
    $this->assertArrayHasKey("tests/$module_name.test", $files, "The files list has a tests file.");
    $this->assertArrayHasKey("README.txt", $files, "The files list has a README file.");

    // Test the 'code' build list setting.
    $module_data = $module_data_base;
    $module_data['requested_build'] = array(
      'code' => TRUE,
    );

    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(5, $files, "Expected number of files are returned.");
    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("$module_name.install", $files, "The files list has a .install file.");
    $this->assertArrayHasKey("$module_name.tokens.inc", $files, "The files list has a .tokens.inc file.");
    $this->assertArrayHasKey("tests/$module_name.test", $files, "The files list has a tests file.");
    $this->assertArrayHasKey("$module_name.api.php", $files, "The files list has a .api.php file.");

    // Test specific file requests.
    $module_data = $module_data_base;
    $module_data['requested_build'] = array(
      'install' => TRUE,
    );

    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(1, $files, "Only one file is returned.");
    $this->assertArrayHasKey("$module_name.install", $files, "The files list has a .install file.");

    $module_data = $module_data_base;
    $module_data['requested_build'] = array(
      'module' => TRUE,
    );

    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(1, $files, "Only one file is returned.");
    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");

    $module_data = $module_data_base;
    $module_data['requested_build'] = array(
      'info' => TRUE,
    );

    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(1, $files, "Only one file is returned.");
    $this->assertArrayHasKey("$module_name.info", $files, "The files list has a .info file.");

    $module_data = $module_data_base;
    $module_data['requested_build'] = array(
      'tokens' => TRUE,
    );

    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(1, $files, "Only one file is returned.");
    $this->assertArrayHasKey("$module_name.tokens.inc", $files, "The files list has a .tokens.inc file.");

    $module_data = $module_data_base;
    $module_data['requested_build'] = array(
      'tests' => TRUE,
    );

    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(1, $files, "Only one file is returned.");
    $this->assertArrayHasKey("tests/$module_name.test", $files, "The files list has a tests file.");
  }

}
