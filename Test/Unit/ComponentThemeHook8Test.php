<?php

/**
 * @file
 * Contains ComponentThemeHook8Test.
 */

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Tests the AdminSettingsForm generator class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit Test/ComponentThemeHook8Test.php
 * @endcode
 */
class ComponentThemeHook8Test extends TestBase {

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
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);
    $file_names = array_keys($files);

    $this->assertCount(3, $files, "Expected number of files is returned.");
    $this->assertContains("$module_name.info.yml", $file_names, "The files list has a .info.yml file.");
    $this->assertContains("$module_name.module", $file_names, "The files list has a .module file.");
    $this->assertContains("templates/my-themeable.html.twig", $file_names, "The files list has a twig file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];

    $this->assertNoTrailingWhitespace($module_file, "The module file contains no trailing whitespace.");

    $this->assertWellFormedPHP($module_file, "Module file parses as well-formed PHP.");

    $this->assertFileHeader($module_file, "The module file contains the correct PHP open tag and file doc header");

    $this->assertHookDocblock('hook_theme', $module_file, "The module file contains the docblock for hook_block_info().");
    $this->assertHookImplementation($module_file, 'hook_theme', $module_name, "The module file contains a function declaration that implements hook_block_info().");

    // Check that the hook_theme() implementation has the generated code.
    // This covers the specialized HookTheme hook generator class getting used.
    $this->assertFunctionCode($module_file, "{$module_name}_theme", "'$theme_hook_name' =>");
    $this->assertFunctionCode($module_file, "{$module_name}_theme", "'render element' => 'elements',");

    // TODO: check the other file contents.
  }

}
