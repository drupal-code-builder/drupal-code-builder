<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Fixtures\File\MockableExtension;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;
use MutableTypedData\Exception\InvalidInputException;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for event subscriber component.
 *
 * @group yaml
 */
class ComponentServiceEventSubscriber10Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 10;

  /**
   * Test generating an event subscriber service.
   */
  public function testServiceGenerationEventSubscriber() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'event_subscribers' => [
        0 => [
          'service_name' => 'event_subscriber',
          'event_names' => [
            '\\Drupal\\Core\\Entity\\EntityTypeEvents::CREATE',
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.services.yml',
      "src/EventSubscriber/EventSubscriber.php",
    ], $files);

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', 'test_module.event_subscriber']);
    $yaml_tester->assertPropertyHasValue(['services', 'test_module.event_subscriber', 'class'], 'Drupal\test_module\EventSubscriber\EventSubscriber');
    $yaml_tester->assertHasProperty(['services', 'test_module.event_subscriber', 'tags']);
    $yaml_tester->assertHasProperty(['services', 'test_module.event_subscriber', 'tags', 0]);
    $yaml_tester->assertPropertyHasValue(['services', 'test_module.event_subscriber', 'tags', 0, 'name'], 'event_subscriber');
    $yaml_tester->assertPropertyHasValue(['services', 'test_module.event_subscriber', 'tags', 0, 'priority'], 0);

    $service_class_file = $files["src/EventSubscriber/EventSubscriber.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $service_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\EventSubscriber\EventSubscriber');
    $php_tester->assertClassHasInterfaces(['Symfony\Component\EventDispatcher\EventSubscriberInterface']);

    // Interface methods.
    $method_tester = $php_tester->getMethodTester('getSubscribedEvents');
    $method_tester->assertMethodDocblockHasInheritdoc();
    $method_tester->assertHasNoParameters();
    $method_tester->assertHasLine('$events[EntityTypeEvents::CREATE] = [\'onCreate\'];');
    $method_tester->assertHasLine('return $events;');

    $method_tester = $php_tester->getMethodTester('onCreate');
    $method_tester->assertMethodHasDocblockLine('Reacts to the CREATE event.');
    $method_tester->assertHasParameters([
      'event' => 'Drupal\Component\EventDispatcher\Event',
    ]);
  }

}
