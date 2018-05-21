<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the theme hook generator class.
 */
class ComponentThemeHook8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

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
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a .module file.");
    $this->assertArrayHasKey("templates/my-themeable.html.twig", $files, "The files list has a twig file.");

    // Check the .module file.
    $module_file = $files["$module_name.module"];
    $php_tester = new PHPTester($module_file);

    $php_tester->assertDrupalCodingStandards();

    $php_tester->assertHasHookImplementation('hook_theme', $module_name);

    // Check that the hook_theme() implementation has the generated code.
    // This covers the specialized HookTheme hook generator class getting used.
    $this->assertFunctionCode($module_file, "{$module_name}_theme", "'$theme_hook_name' =>");
    $this->assertFunctionCode($module_file, "{$module_name}_theme", "'render element' => 'elements',");

    // TODO: check the other file contents.
  }

}
