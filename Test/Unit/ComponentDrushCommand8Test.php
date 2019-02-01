<?php

namespace DrupalCodeBuilder\Test\Unit;

use \DrupalCodeBuilder\Exception\InvalidInputException;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests for Drush command component.
 *
 * @group yaml
 */
class ComponentDrushCommand8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test generating a module with a service.
   */
  public function testBasicServiceGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'drush_commands' => array(
        0 => [
          'command_name' => 'alpha',
        ],
        1 => [
          'command_name' => 'my_group:beta',
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'drush.services.yml',
      'src/Commands/TestModuleCommands.php',
    ], $files);

    $drush_services_file = $files["drush.services.yml"];

    $yaml_tester = new YamlTester($drush_services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "$module_name.commands"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.commands", 'class'], "Drupal\\$module_name\\Commands\\TestModuleCommands");
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.commands", 'tags', 0, 'name'], 'drush.command');

    $command_class_file = $files["src/Commands/TestModuleCommands.php"];
    dump($command_class_file);

    $php_tester = new PHPTester($command_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Commands\TestModuleCommands');
    $php_tester->assertClassHasParent('Drush\Commands\DrushCommands');
    $php_tester->assertHasMethod('alpha');
    $php_tester->assertHasMethod('beta');
  }

}
