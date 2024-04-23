<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Fixtures\File\MockableExtension;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;
use MutableTypedData\Exception\InvalidInputException;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests for event component.
 */
class ComponentEventTest extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 10;

  /**
   * Test generating an event.
   */
  public function testEventGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'events' => [
        0 => [
          'event_constant' => 'MOO',
          'event_value' => 'moo',
        ],
        // FUCK. Merging issues.
        // 1 => [
        //   'event_constant' => 'MIAOW',
        //   'event_value' => 'miaow',
        // ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);
    dump($files);

    $this->assertFiles([
      'test_module.info.yml',
      "src/Event/TestModuleEvents.php",
    ], $files);

    $event_constants_file = $files['src/Event/TestModuleEvents.php'];
    dump($event_constants_file);
  }

}
