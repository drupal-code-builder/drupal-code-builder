<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the theme hook generator class.
 * @group hooks
 */
class ComponentThemeHook11Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 11;

  /**
   * Test theme hook component.
   */
  function testThemeHook() {
    $theme_hook_name = 'my_themeable';

    // Create a module.
    $module_name = 'testmodule';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
      ],
      // Set this to OO only, so we don't have the extra legacy code.
      'hook_implementation_type' => 'oo',
      'theme_hooks' => [
        $theme_hook_name,
      ],
      'readme' => FALSE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'testmodule.info.yml',
      'templates/my-themeable.html.twig',
      'src/Hook/TestmoduleHooks.php',
    ], $files);

    // Check the hooks file.
    $hooks_file = $files['src/Hook/TestmoduleHooks.php'];
    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $hooks_file);

    $php_tester->assertDrupalCodingStandards();

    $php_tester->assertHasMethod('theme');
    // TODO: Attribute testing.
    $this->assertStringContainsString("#[Hook('theme')]", $hooks_file);

    $method_tester = $php_tester->getMethodTester('theme');

    // Check that the hook_theme() implementation has the generated code.
    // This covers the specialized HookTheme hook generator class getting used.
    $method_tester->assertHasLine("'$theme_hook_name' =>");
    $method_tester->assertHasLine("'render element' => 'elements',");

    // TODO: check the other file contents.
  }

}
