<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests for Router item component.
 */
class ComponentRouterItem7Test extends TestBaseComponentGeneration {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(7);
  }

  /**
   * Test generating a module with menu items.
   */
  public function testRouteGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'router_items' => array(
        0 => 'my/path',
        1 => 'my/other-path',
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(2, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("$module_name.info", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("$module_name.module", $files, "The files list has a module file.");

    $module_file = $files["$module_name.module"];

    $php_tester = new PHPTester($module_file);

    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasHookImplementation('hook_menu', $module_name);

    $this->assertFunctionCode($module_file, 'test_module_menu', "return \$items;");
    $this->assertFunctionCode($module_file, 'test_module_menu', "\$items['my/path']");
    $this->assertFunctionCode($module_file, 'test_module_menu', "\$items['my/other-path']");
  }

}
