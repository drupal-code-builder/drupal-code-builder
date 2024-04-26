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
          'event_name' => 'cow_moo',
        ],
        1 => [
          'event_name' => 'cat_miaow',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      "src/Event/TestModuleEvents.php",
      "src/Event/CowMooEvent.php",
      "src/Event/CatMiaowEvent.php",
    ], $files);

    $event_constants_file = $files['src/Event/TestModuleEvents.php'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $event_constants_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Event\TestModuleEvents');
    $php_tester->assertClassHasNoParent();
    $php_tester->assertClassDocBlockHasLine('Defines events for the Test Module module.');
    $php_tester->assertClassHasConstant('COW_MOO');
    $php_tester->assertClassHasConstant('CAT_MIAOW');

    $event_file = $files['src/Event/CowMooEvent.php'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $event_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Event\CowMooEvent');
    $php_tester->assertClassHasParent('Drupal\Component\EventDispatcher\Event');
  }

}
