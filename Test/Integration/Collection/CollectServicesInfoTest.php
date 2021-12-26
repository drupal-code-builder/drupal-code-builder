<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;

/**
 * Tests services collection.
 */
class CollectServicesInfoTest extends CollectionTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    // Don't enable any modules, as we replace the module extension list during
    // the test and remove all modules except for our fixture module.
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->servicesCollector = new \DrupalCodeBuilder\Task\Collect\ServicesCollector(
      \DrupalCodeBuilder\Factory::getEnvironment(),
      new \DrupalCodeBuilder\Task\Collect\ContainerBuilderGetter,
      new \DrupalCodeBuilder\Task\Collect\CodeAnalyser($this->environment)
    );
  }

  /**
   * Tests collection of core services.
   */
  public function testCoreServicesCollection() {
    $module_installer = $this->container->get('module_installer');
    $result = $module_installer->install(['node']);
    $this->assertTrue($result);

    $complete_service_info = $this->servicesCollector->collect();

    // Service detected from the \Drupal class's static methods.
    $this->assertNotEmpty($complete_service_info['all']['entity_type.manager']);
    $entity_type_manager_info = $complete_service_info['all']['entity_type.manager'];
    $this->assertEquals('\Drupal\Core\Entity\EntityTypeManager', $entity_type_manager_info['class']);
    $this->assertEquals('\Drupal\Core\Entity\EntityTypeManagerInterface', $entity_type_manager_info['interface']);
    $this->assertEquals('Entity type manager', $entity_type_manager_info['label']);
    $this->assertEquals('The entity type manager', $entity_type_manager_info['description']);
    $this->assertEquals('entity_type_manager', $entity_type_manager_info['variable_name']);

    // Service obtained from the container.
    $this->assertNotEmpty($complete_service_info['all']['entity_type.bundle.info']);
    $entity_type_bundle_info = $complete_service_info['all']['entity_type.bundle.info'];
    $this->assertEquals('\Drupal\Core\Entity\EntityTypeBundleInfo', $entity_type_bundle_info['class']);
    $this->assertEquals('\Drupal\Core\Entity\EntityTypeBundleInfoInterface', $entity_type_bundle_info['interface']);
    $this->assertEquals('Entity type bundle info', $entity_type_bundle_info['label']);
    // TODO: why does this get uppercase 'Entity'??
    $this->assertEquals('The Entity type bundle info service', $entity_type_bundle_info['description']);
    $this->assertEquals('entity_type_bundle_info', $entity_type_bundle_info['variable_name']);

    // A 'manager' doesn't get called 'service'.
    $this->assertNotEmpty($complete_service_info['all']['config.manager']);
    $config_manager_info = $complete_service_info['all']['config.manager'];
    $this->assertEquals('\Drupal\Core\Config\ConfigManager', $config_manager_info['class']);
    $this->assertEquals('\Drupal\Core\Config\ConfigManagerInterface', $config_manager_info['interface']);
    $this->assertEquals('Config manager', $config_manager_info['label']);
    $this->assertEquals('The Config manager', $config_manager_info['description']);
    $this->assertEquals('config_manager', $config_manager_info['variable_name']);

    // Proxy services get the original class.
    $this->assertNotEmpty($complete_service_info['all']['lock.persistent']);
    $service_info = $complete_service_info['all']['lock.persistent'];
    $this->assertEquals('\Drupal\Core\Lock\PersistentDatabaseLockBackend', $service_info['class']);
    $this->assertEquals('', $service_info['interface']);
    $this->assertEquals('Persistent database lock backend', $service_info['label']);
    $this->assertEquals('The Persistent database lock backend service', $service_info['description']);
    // A service ID with a '.' in it has the variable name derived from the
    // class name instead of the service ID.
    $this->assertEquals('persistent_database_lock_backend', $service_info['variable_name']);

    // Services whose declared class is actually an interface get a variable
    // name that doesn't include 'interface'.
    $this->assertNotEmpty($complete_service_info['all']['cache.discovery']);
    $service_info = $complete_service_info['all']['cache.discovery'];
    $this->assertEquals('\Drupal\Core\Cache\CacheBackendInterface', $service_info['class']);
    $this->assertEquals('cache_backend', $service_info['variable_name']);

    // Storage pseudoservices.
    $this->assertNotEmpty($complete_service_info['all']['storage:node']);
    $service_info = $complete_service_info['all']['storage:node'];
    $this->assertArrayNotHasKey('class', $service_info);
    $this->assertEquals('\Drupal\Core\Entity\EntityStorageInterface', $service_info['interface']);
    // TODO: Fix this to use the machine name, not the label.
    // See https://github.com/drupal-code-builder/drupal-code-builder/issues/211.
    $this->assertEquals('Content storage', $service_info['label']);
    $this->assertEquals('The node storage handler', $service_info['description']);
    $this->assertEquals('node_storage', $service_info['variable_name']);
    $this->assertEquals('entity_type.manager', $service_info['real_service']);
    $this->assertEquals('getStorage', $service_info['service_method']);
  }

  /**
   * Tests collecting services with special requirements.
   *
   * This uses a fixture Drupal module which was generated by DCB.
   *
   * TODO: add this to the stuff that DCB can generate automatically so it can
   * be updated at the same time as test sample data.
   */
  public function testSpecialCasesServicesCollection() {
    $this->installFixtureModule('test_services');

    $complete_service_info = $this->servicesCollector->collect();

    // A service whose name has a 'service' prefix doesn't get it repeated in
    // the description.
    $this->assertNotEmpty($complete_service_info['all']['test_services.combobulating_service']);
    $test_service_info = $complete_service_info['all']['test_services.combobulating_service'];
    $this->assertEquals('\Drupal\test_services\CombobulatingService', $test_service_info['class']);
    $this->assertEquals('Combobulating service', $test_service_info['label']);
    $this->assertEquals('The Combobulating service', $test_service_info['description']);

    $this->assertNotEmpty($complete_service_info['all']['test_services.slash_prefix']);
    $test_service_info = $complete_service_info['all']['test_services.slash_prefix'];
    $this->assertEquals('\Drupal\test_services\SlashPrefix', $test_service_info['class']);
    $this->assertEquals('Slash prefix', $test_service_info['label']);
    $this->assertEquals('The Slash prefix service', $test_service_info['description']);

    $this->assertNotEmpty($complete_service_info['all']['test_services.fq_interface']);
    $test_service_info = $complete_service_info['all']['test_services.fq_interface'];
    $this->assertEquals('\Drupal\test_services\UsesFullyQualifedInterface', $test_service_info['class']);
    $this->assertEquals('\Drupal\test_services\ServiceInterface\FullyQualifedInterface', $test_service_info['interface']);
    // TODO: bug? Should not trim 'interface'?
    $this->assertEquals('Uses fully qualifed', $test_service_info['label']);
    $this->assertEquals('The Uses fully qualifed service', $test_service_info['description']);
  }

}
