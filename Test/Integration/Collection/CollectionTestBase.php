<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;

use Drupal\KernelTests\KernelTestBase;
use DrupalCodeBuilder\Test\Fixtures\Drupal\TestModuleExtensionList;

/**
 * Base class for collection integration tests.
 *
 * This type of test tests aspects that need a working Drupal site.
 *
 * These need to be run from Drupal's PHPUnit, rather than ours:
 * @code
 *  [drupal]/core $ ../vendor/bin/phpunit ../vendor/drupal-code-builder/drupal-code-builder/Test/Integration/Collection/CollectHooksTest.php
 * @endcode
 */
class CollectionTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    \DrupalCodeBuilder\Factory::setEnvironmentLocalClass('DrupalLibrary')
      ->setCoreVersionNumber(\Drupal::VERSION);

    $this->environment = \DrupalCodeBuilder\Factory::getEnvironment();

    parent::setUp();
  }

  /**
   * Install a local fixture module.
   *
   * @param string $module
   *   The name of a module in the Test/Fixtures/modules folder.
   */
  protected function installFixtureModule(string $module) {
    // Create a module list service, using our subclass that lets us hack in
    // the discovery.
    $module_list = new TestModuleExtensionList(
      $this->container->getParameter('app.root'),
      'module',
      $this->container->get('cache.default'),
      $this->container->get('info_parser'),
      $this->container->get('module_handler'),
      $this->container->get('state'),
      $this->container->get('config.factory'),
      $this->container->get('extension.list.profile'),
      $this->container->getParameter('install_profile'),
      $this->container->getParameter('container.modules')
    );

    // Mock the discovery to return our fixture module.
    $extension_discovery = $this->prophesize(\Drupal\Core\Extension\ExtensionDiscovery::class);

    // Keep the real scan result, as in at least once case, core components
    // have a hidden dependency on system module.
    // See https://www.drupal.org/project/drupal/issues/3179090.
    $real_extension_discovery = new \Drupal\Core\Extension\ExtensionDiscovery(\Drupal::root());
    $extension_scan_result = $real_extension_discovery->scan('module');

    // We expect DCB to be in the vendor folder.
    $extension_scan_result[$module] = new \Drupal\Core\Extension\Extension(
      // Our module is outside of the Drupal root, but we have to specify it
      // as ModuleInstaller::install() assumes it when it constructs the
      // Extension object again later.
      \Drupal::root(),
      $module,
      // This has to be a path relative to the given root in the first
      // parameter.
      "../vendor/drupal-code-builder/drupal-code-builder/Test/Fixtures/modules/$module/$module.info.yml"
    );

    $extension_discovery->scan('module')->willReturn($extension_scan_result);

    // Set the discovery on the module list and set it into the container.
    $module_list->setExtensionDiscovery($extension_discovery->reveal());
    $module_list->reset();
    $this->container->set('extension.list.module', $module_list);

    // Install our module.
    $module_installer = $this->container->get('module_installer');
    $result = $module_installer->install([$module]);
    $this->assertTrue($result);
  }

}
