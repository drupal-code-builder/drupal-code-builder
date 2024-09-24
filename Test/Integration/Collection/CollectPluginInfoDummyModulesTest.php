<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;


/**
 * Tests plugin collection with a dummy module.
 */
class CollectPluginInfoDummyModulesTest extends CollectionTestBase {

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

    $this->pluginTypesCollector = new \DrupalCodeBuilder\Task\Collect\PluginTypesCollector(
      \DrupalCodeBuilder\Factory::getEnvironment(),
      new \DrupalCodeBuilder\Task\Collect\ContainerBuilderGetter,
      new \DrupalCodeBuilder\Task\Collect\MethodCollector,
      new \DrupalCodeBuilder\Task\Collect\CodeAnalyser($this->environment)
    );

    // Hack the task handler so we can call the processing method with a subset
    // of plugin manager service IDs.
    $class = new \ReflectionObject($this->pluginTypesCollector);
    $this->gatherPluginTypeInfoMethod = $class->getMethod('gatherPluginTypeInfo');
    $this->gatherPluginTypeInfoMethod->setAccessible(TRUE);
  }

  protected function getPluginTypeInfoFromCollector($job) {
    return $this->gatherPluginTypeInfoMethod->invoke($this->pluginTypesCollector, [$job]);
  }

  /**
   * Tests plugin base class in the obvious location when there are no plugins.
   *
   * This uses a fixture Drupal module which was generated by DCB.
   *
   * TODO: add this to the stuff that DCB can generate automatically so it can
   * be updated at the same time as test sample data.
   */
  public function testObviousBaseClassDetection() {
    $this->installFixtureModule('test_generated_plugin_type');

    $plugin_types_info = $this->getPluginTypeInfoFromCollector(
      [
        'service_id' => 'plugin.manager.test_generated_plugin_type_test_annotation_plugin',
        'type_id' => 'test_generated_plugin_type_test_annotation_plugin',
      ],
    );

    $this->assertEquals('Drupal\test_generated_plugin_type\Plugin\TestAnnotationPlugin\TestAnnotationPluginBase', $plugin_types_info['test_generated_plugin_type_test_annotation_plugin']['base_class']);
  }

}
