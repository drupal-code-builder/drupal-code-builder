<?php

namespace DrupalCodeBuilder\Test\Integration\Installation;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests a basic module.
 *
 * These need to be run from Drupal's PHPUnit, rather than ours:
 * @code
 *  [drupal]/core $ ../vendor/bin/phpunit ../vendor/drupal-code-builder/drupal-code-builder/Test/Integration/Installation/ModuleTest.php
 * @endcode
 */
class ModuleTest extends InstallationTestBase {

  /**
   * Tests a basic module.
   */
  public function testModule() {
    // Create a module.
    $module_name = 'dcb_test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'readme' => FALSE,
    );
    $files = $this->generateModuleFiles($module_data);

    $this->writeModuleFiles($module_name, $files);

    $this->installModule($module_name);
  }

}
