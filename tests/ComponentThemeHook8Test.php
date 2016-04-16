<?php

/**
 * @file
 * Contains ComponentThemeHook8Test.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/DrupalCodeBuilderTestBase.php';

/**
 * Tests the AdminSettingsForm generator class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/ComponentThemeHook8Test.php
 * @endcode
 */
class ComponentThemeHook8Test extends DrupalCodeBuilderTestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test theme hook component.
   */
  function testThemeHook() {
    $theme_hook_name = 'my_themeable';

    // Create a module.
    $module_name = 'testmodule';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'theme_hooks' => array(
        $theme_hook_name,
      ),
      'requested_components' => array(
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(3, $files, "Expected number of files is returned.");
    $this->assertContains("$module_name.info.yml", $file_names, "The files list has a .info.yml file.");
    $this->assertContains("$module_name.module", $file_names, "The files list has a .module file.");
    $this->assertContains("templates/$theme_hook_name.html.twig", $file_names, "The files list has a twig file.");

    // TODO: check the file contents.
  }

}
