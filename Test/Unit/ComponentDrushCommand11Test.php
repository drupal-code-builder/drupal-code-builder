<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests for Drush command component.
 *
 * @group yaml
 */
class ComponentDrushCommand11Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 11;

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
      'src/Commands/TestModuleCommands.php',
    ], $files);

    $command_class_file = $files["src/Commands/TestModuleCommands.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $command_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Commands\TestModuleCommands');
    $php_tester->assertClassHasParent('Drush\Commands\DrushCommands');
    $php_tester->assertClassDocBlockHasLine('Test module Drush commands.');
    $php_tester->assertHasMethod('alpha');
    $php_tester->assertHasMethod('beta');

    // $alpha_method_tester = $php_tester->getMethodTester('alpha');
    // $alpha_method_tester->assertMethodHasDocblockLine('@command test_module:alpha');
    // $alpha_method_tester->assertMethodHasDocblockLine('@usage drush test_module:alpha');

    // $beta_method_tester = $php_tester->getMethodTester('beta');
    // $beta_method_tester->assertMethodHasDocblockLine('@command my_group:beta');
    // $beta_method_tester->assertMethodHasDocblockLine('@usage drush my_group:beta');
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
          'command_name_aliases' => [
            'al',
            'betty',
          ],
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
      'src/Commands/TestModuleCommands.php',
    ], $files);

    $command_class_file = $files["src/Commands/TestModuleCommands.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $command_class_file);
    $php_tester->assertDrupalCodingStandards([
      // The options array gets picked up by this, sort of incorrectly!
      // See https://www.drupal.org/project/coder/issues/3475912.
      'Drupal.Arrays.Array.LongLineDeclaration',
    ]);
    $php_tester->assertHasClass('Drupal\test_module\Commands\TestModuleCommands');
    $php_tester->assertClassHasParent('Drush\Commands\DrushCommands');
    $php_tester->assertClassDocBlockHasLine('Test module Drush commands.');
    $php_tester->assertHasMethod('alpha');
    $php_tester->assertHasMethod('beta');

    $alpha_method_tester = $php_tester->getMethodTester('alpha');
    // TODO: test attributes.
    // $alpha_method_tester->assertMethodHasDocblockLine('@command test_module:alpha');
    // $alpha_method_tester->assertMethodHasDocblockLine('@usage drush test_module:alpha alpha_one alpha_two --option_string --option_numeric --option_bool');
    $alpha_method_tester->assertHasParameters([
      'alpha_one' => NULL,
      'alpha_two' => NULL,
      'options' => NULL,
    ]);
    // TODO: test default values of options.
    // $alpha_method_tester->assertMethodHasDocblockLine('@option option_string Option description.');
    // $alpha_method_tester->assertMethodHasDocblockLine('@option option_numeric Option description.');
    // $alpha_method_tester->assertMethodHasDocblockLine('@option option_bool Option description.');

    // $beta_method_tester = $php_tester->getMethodTester('beta');
    // $beta_method_tester->assertMethodHasDocblockLine('@command my_group:beta');
    // $beta_method_tester->assertMethodHasDocblockLine('@usage drush my_group:beta beta_one');
    // $beta_method_tester->assertHasParameters([
    //   'beta_one' => 'string',
    // ]);
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
      'src/Commands/TestModuleCommands.php',
    ], $files);

    $command_class_file = $files["src/Commands/TestModuleCommands.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $command_class_file);
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

    $create_tester = $php_tester->getMethodTester('create');
    $create_tester->assertHasParameters([
      'container' => 'Psr\Container\ContainerInterface',
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
            'logger',
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
      'src/Commands/TestModuleCommands.php',
    ], $files);

    $command_class_file = $files["src/Commands/TestModuleCommands.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $command_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Commands\TestModuleCommands');
    $php_tester->assertClassHasParent('Drush\Commands\DrushCommands');
    $php_tester->assertClassDocBlockHasLine('Test module Drush commands.');
    $php_tester->assertHasMethod('alpha');
    $php_tester->assertHasMethod('beta');
    $php_tester->assertClassHasInterfaces([
      "Drush\SiteAlias\SiteAliasManagerAwareInterface",
      'Psr\Log\LoggerAwareInterface',
    ]);
    $php_tester->assertClassHasTraits([
      "Consolidation\SiteAlias\SiteAliasManagerAwareTrait",
      'Psr\Log\LoggerAwareTrait',
    ]);
  }

}
