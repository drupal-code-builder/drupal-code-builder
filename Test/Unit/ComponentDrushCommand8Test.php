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
   * Test generating a Drush command file.
   */
  public function testBasicCommandGeneration() {
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
          'command_description' => 'Do alpha.',
        ],
        1 => [
          'command_name' => 'my_group:beta',
          'command_description' => 'Do beta.',
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
    $yaml_tester->assertHasProperty(['services', "test_module.commands"]);
    $yaml_tester->assertPropertyHasValue(['services', "test_module.commands", 'class'], "Drupal\\test_module\\Commands\\TestModuleCommands");
    $yaml_tester->assertPropertyHasValue(['services', "test_module.commands", 'tags', 0, 'name'], 'drush.command');

    $command_class_file = $files["src/Commands/TestModuleCommands.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $command_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Commands\TestModuleCommands');
    $php_tester->assertClassHasParent('Drush\Commands\DrushCommands');
    $php_tester->assertClassDocBlockHasLine('Test module Drush commands.');
    $php_tester->assertHasMethod('alpha');
    $php_tester->assertHasMethod('beta');

    $alpha_method_tester = $php_tester->getMethodTester('alpha');
    $alpha_method_tester->assertMethodHasDocblockLine('@command test_module:alpha');
    $alpha_method_tester->assertMethodHasDocblockLine('@usage drush test_module:alpha');

    $beta_method_tester = $php_tester->getMethodTester('beta');
    $beta_method_tester->assertMethodHasDocblockLine('@command my_group:beta');
    $beta_method_tester->assertMethodHasDocblockLine('@usage drush my_group:beta');
  }

  /**
   * Test generation with parameters and options.
   */
  function testCommandGenerationWithParameters() {
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'drush_commands' => array(
        0 => [
          'command_name' => 'alpha',
          'command_description' => 'Do alpha.',
          'command_parameters' => [
            'alpha_one',
            'alpha_two',
          ],
          'command_options' => [
            'option_string: cake',
            'option_numeric: 42',
            'option_bool: FALSE',
          ],
        ],
        1 => [
          'command_name' => 'my_group:beta',
          'command_description' => 'Do beta.',
          'command_parameters' => [
            'beta_one',
          ],
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
    $yaml_tester->assertHasProperty(['services', "test_module.commands"]);
    $yaml_tester->assertPropertyHasValue(['services', "test_module.commands", 'class'], "Drupal\\test_module\\Commands\\TestModuleCommands");
    $yaml_tester->assertPropertyHasValue(['services', "test_module.commands", 'tags', 0, 'name'], 'drush.command');

    $command_class_file = $files["src/Commands/TestModuleCommands.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $command_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Commands\TestModuleCommands');
    $php_tester->assertClassHasParent('Drush\Commands\DrushCommands');
    $php_tester->assertClassDocBlockHasLine('Test module Drush commands.');
    $php_tester->assertHasMethod('alpha');
    $php_tester->assertHasMethod('beta');

    $alpha_method_tester = $php_tester->getMethodTester('alpha');
    $alpha_method_tester->assertMethodHasDocblockLine('@command test_module:alpha');
    $alpha_method_tester->assertMethodHasDocblockLine('@usage drush test_module:alpha alpha_one alpha_two --option_string --option_numeric --option_bool');
    $alpha_method_tester->assertHasParameters([
      'alpha_one' => 'string',
      'alpha_two' => 'string',
      'option_string' => 'string',
      'option_numeric' => 'int',
      'option_bool' => 'bool',
    ]);
    // TODO: test default values of options.
    $alpha_method_tester->assertMethodHasDocblockLine('@option option_string Option description.');
    $alpha_method_tester->assertMethodHasDocblockLine('@option option_numeric Option description.');
    $alpha_method_tester->assertMethodHasDocblockLine('@option option_bool Option description.');

    $beta_method_tester = $php_tester->getMethodTester('beta');
    $beta_method_tester->assertMethodHasDocblockLine('@command my_group:beta');
    $beta_method_tester->assertMethodHasDocblockLine('@usage drush my_group:beta beta_one');
    $beta_method_tester->assertHasParameters([
      'beta_one' => 'string',
    ]);
  }

  /**
   * Test a command with with injected services.
   *
   * @group di
   */
  function testCommandGenerationWithServices() {
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
          'command_description' => 'Do alpha.',
          'injected_services' => [
            'current_user',
          ],
        ],
        1 => [
          'command_name' => 'my_group:beta',
          'command_description' => 'Do beta.',
          'injected_services' => [
            'entity_type.manager',
          ],
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
    // dump($drush_services_file);

    $yaml_tester = new YamlTester($drush_services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', "test_module.commands"]);
    $yaml_tester->assertPropertyHasValue(['services', "test_module.commands", 'class'], "Drupal\\test_module\\Commands\\TestModuleCommands");
    $yaml_tester->assertPropertyHasValue(['services', "test_module.commands", 'tags', 0, 'name'], 'drush.command');
    $yaml_tester->assertPropertyHasValue(['services', "test_module.commands", 'arguments', 0], '@current_user');
    $yaml_tester->assertPropertyHasValue(['services', "test_module.commands", 'arguments', 1], '@entity_type.manager');

    $command_class_file = $files["src/Commands/TestModuleCommands.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $command_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Commands\TestModuleCommands');
    $php_tester->assertClassHasParent('Drush\Commands\DrushCommands');
    $php_tester->assertClassDocBlockHasLine('Test module Drush commands.');
    $php_tester->assertHasMethod('alpha');
    $php_tester->assertHasMethod('beta');
    $php_tester->assertInjectedServices([
      [
        'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
        'service_name' => 'current_user',
        'property_name' => 'currentUser',
        'parameter_name' => 'current_user',
      ],
      [
        'typehint' => 'Drupal\Core\Entity\EntityTypeManagerInterface',
        'service_name' => 'entity_type.manager',
        'property_name' => 'entityTypeManager',
        'parameter_name' => 'entity_type_manager',
      ],
    ]);
  }

  /**
   * Test a command with with inflection interfaces.
   */
  public function testCommandGenerationWithInflection() {
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
          'command_description' => 'Do alpha.',
          'inflected_injection' => [
            'autoloader',
            'site_alias',
          ],
        ],
        1 => [
          'command_name' => 'my_group:beta',
          'command_description' => 'Do beta.',
          'inflected_injection' => [
            'site_alias',
          ],
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
    $yaml_tester->assertHasProperty(['services', "test_module.commands"]);
    $yaml_tester->assertPropertyHasValue(['services', "test_module.commands", 'class'], "Drupal\\test_module\\Commands\\TestModuleCommands");
    $yaml_tester->assertPropertyHasValue(['services', "test_module.commands", 'tags', 0, 'name'], 'drush.command');

    $command_class_file = $files["src/Commands/TestModuleCommands.php"];

    $php_tester = new PHPTester($this->drupalMajorVersion, $command_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Commands\TestModuleCommands');
    $php_tester->assertClassHasParent('Drush\Commands\DrushCommands');
    $php_tester->assertClassDocBlockHasLine('Test module Drush commands.');
    $php_tester->assertHasMethod('alpha');
    $php_tester->assertHasMethod('beta');
    $php_tester->assertClassHasInterfaces([
      "Drush\SiteAlias\SiteAliasManagerAwareInterface",
      "Drush\Boot\AutoloaderAwareInterface",
    ]);
    $php_tester->assertClassHasTraits([
      "Drush\Boot\AutoloaderAwareTrait",
      "Consolidation\SiteAlias\SiteAliasManagerAwareTrait",
    ]);
  }

}
