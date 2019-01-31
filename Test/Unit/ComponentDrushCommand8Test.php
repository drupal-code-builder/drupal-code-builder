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
          'command_class_name' => 'MyCommand',
        ],
        1 => [
          'command_class_name' => 'MyCommandTwo',
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'drush.services.yml',
      'src/Commands/MyCommand.php',
      'src/Commands/MyCommandTwo.php',
    ], $files);

    $drush_services_file = $files["drush.services.yml"];

    $yaml_tester = new YamlTester($drush_services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "$module_name.my_command"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_command", 'class'], "Drupal\\$module_name\\Commands\\MyCommand");
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_command_two", 'class'], "Drupal\\$module_name\\Commands\\MyCommandTwo");

    $command_class_file = $files["src/Commands/MyCommand.php"];
    dump($command_class_file);

    $php_tester = new PHPTester($command_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Commands\MyCommand');
    $php_tester->assertClassHasParent('Drush\Commands\DrushCommands');
  }

}
