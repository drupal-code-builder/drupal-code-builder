<?php

namespace DrupalCodeBuilder\Test\Integration\Installation;

use Drupal\KernelTests\KernelTestBase;
use MutableTypedData\Data\DataItem;

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

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
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
    $component_data = $this->getRootComponentBlankData('module');

    $component_data->set($module_data);

    $files = $this->generateComponentFilesFromData($component_data);

    return $files;
  }

  /**
   * Gets the empty data item for the root component.
   *
   * @param string $type
   *   The component type.
   *   TODO: make this optional in getTask()?
   *
   * @return \MutableTypedData\Data\DataItem
   *   The data item.
   */
  protected function getRootComponentBlankData(string $type): DataItem {
    $task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', $type);
    $component_data = $task_handler_generate->getRootComponentData();
    return $component_data;
  }

  /**
   * Generate module files from component data.
   *
   * @param \MutableTypedData\Data\DataItem $component_data
   *  The data for the generator.
   *
   * @param
   *  An array of files.
   */
  protected function generateComponentFilesFromData(DataItem $component_data) {
    $violations = $component_data->validate();

    if ($violations) {
      $message = [];
      foreach ($violations as $address => $address_violations) {
        $message[] = $address . ': ' . implode(',', $address_violations);
      }
      throw new \DrupalCodeBuilder\Test\Exception\ValidationException(implode('; ', $message));
    }

    $task_handler_generate = \DrupalCodeBuilder\Factory::getTask('Generate', $component_data->base->value);
    $files = $task_handler_generate->generateComponent($component_data);
    return $files;
  }

  /**
   * Gets the path of the folder to write modules to.
   *
   * This is a temporary folder which is deleted on teardown.
   *
   * @return string
   *   The path to the folder, without a trailing slash.
   */
  protected function getModuleParentFolderPath(): string {
    $app_root = $this->container->getParameter('app.root');
    $modules_folder = $app_root . '/modules/tmp';
    return $modules_folder;
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
    $module_folder = $this->getModuleParentFolderPath() . '/' . $module_name;

    mkdir($module_folder, 0777, TRUE);

    foreach ($files as $filepath => $code) {
      $relative_file_dir = dirname($filepath);
      $absolute_file_dir = $module_folder . '/' . $relative_file_dir;
      if (!file_exists($absolute_file_dir)) {
        mkdir($absolute_file_dir, 0777, TRUE);
      }

      $result = file_put_contents($module_folder . '/' . $filepath, $code);
      $this->assertNotEmpty($result);
    }
  }

  /**
   * Install the given module.
   *
   * @param string $module_name
   *   The module name.
   */
  protected function installModule(string $module_name) {
    // Force the extension system to re-scan for modules.
    \Drupal::service('extension.list.module')->reset();
    // ExtensionDiscovery keeps a cache of found files in a static property that
    // can only be cleared by hacking it with reflection.
    $reflection_property = new \ReflectionProperty(\Drupal\Core\Extension\ExtensionDiscovery::class, 'files');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue(NULL, []);

    $result = $this->container->get('module_installer')->install([$module_name]);
    $this->assertTrue($result);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->container->get('file_system')->deleteRecursive($this->getModuleParentFolderPath());
  }

}
