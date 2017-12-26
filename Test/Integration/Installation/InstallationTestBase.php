<?php

namespace DrupalCodeBuilder\Test\Integration\Installation;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for installation tests.
 *
 * These generate module code and then install it in Drupal.
 *
 * These need to be run from a working Drupal site, and run using that site's
 * PHPUnit (so Drupal's version, rather than the more recent version DCB uses
 * for unit tests).
 */
class InstallationTestBase extends KernelTestBase {

  public static $modules = [
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Drupal doesn't know about DCB, so won't have it in its autoloader, so
    // rely on the Factory file's autoloader.
    $dcb_root = dirname(dirname(dirname(__DIR__)));
    require_once("$dcb_root/Factory.php");

    \DrupalCodeBuilder\Factory::setEnvironmentLocalClass('TestsSampleLocation')
      ->setCoreVersionNumber(\Drupal::VERSION);
  }

  /**
   * Generate module files from a data array.
   *
   * @param $module_data
   *  An array of module data for the module generator.
   *
   * @param
   *  An array of files.
   */
  protected function generateModuleFiles($module_data) {
    $mb_task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
    $component_data_info = $mb_task_handler_generate->getRootComponentDataInfo();

    $files = $mb_task_handler_generate->generateComponent($module_data);

    return $files;
  }

  /**
   * Write the module files to the site path.
   *
   * This uses the site path, which during tests is in a VFS.
   *
   * @param string $module_name
   *   The module name.
   * @param string[] $files
   *   An array of code files, keyed by relative filepath.
   */
  protected function writeModuleFiles($module_name, $files) {
    // Write to the virtual file system that exists for the test site.
    // This also means we don't have to delete the code files in test tear-down
    // as this filesystem simply ceases to exist.
    $test_site_path = \Drupal::service('site.path');

    // Uncomment when developing to see the written code.
    //$test_site_path = \Drupal::root();

    $module_folder = $test_site_path . '/modules/tmp/' . $module_name;

    mkdir($module_folder, 0777, TRUE);

    foreach ($files as $filepath => $code) {
      $relative_file_dir = dirname($filepath);
      $absolute_file_dir = $module_folder . '/' . $relative_file_dir;
      if (!file_exists($absolute_file_dir)) {
        mkdir($absolute_file_dir, 0777, TRUE);
      }

      file_put_contents($module_folder . '/' . $filepath, $code);
    }
  }

  /**
   * Install the given module.
   *
   * @param string $module_name
   *   The module name.
   */
  protected function installModule($module_name) {
    // Force the extension system to re-scan for modules.
    drupal_static_reset('system_rebuild_module_data');
    // ExtensionDiscovery keeps a cache of found files in a static property that
    // can only be cleared by hacking it with reflection.
    $reflection_property = new \ReflectionProperty(\Drupal\Core\Extension\ExtensionDiscovery::class, 'files');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue(NULL, []);

    $listing = new \Drupal\Core\Extension\ExtensionDiscovery(\Drupal::root());
    $modules = $listing->scan('module');

    $this->container->get('module_installer')->install([$module_name]);
  }

}
