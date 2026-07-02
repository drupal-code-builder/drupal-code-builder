<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\Attributes\Group;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests for CLI command component.
 */
class ComponentCliCommandTest11 extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected $drupalMajorVersion = 11;

  public function testFoo(): void {
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'cli_commands' => array(
        0 => [
          'command_name' => 'alpha',
          'command_name_aliases' => [
            'a',
          ],
          'command_description' => 'Do alpha.',
          'injected_services' => [
            'entity_type.manager',
          ],
          'command_parameters' => [
            0 => [
              'name' => 'alpha',
              'type' => 'string',
            ],
            1 => [
              'name' => 'beta',
              'type' => 'bool',
              'default_value' => 'FALSE',
            ],
          ],
          'command_options' => [
            0 => [
              'name' => 'cake',
              'type' => 'string',
            ],
            1 => [
              'name' => 'pie',
              'type' => 'bool',
              'default_value' => 'TRUE',
            ],
          ],
        ],
      ),
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'src/Command/AlphaCommand.php',
    ], $files);

    $command_class_file = $files['src/Command/AlphaCommand.php'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $command_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Command\AlphaCommand');
    $php_tester->getClassDocBlockTester()->assertHasLine('The alpha command.');
    $php_tester->assertInjectedServices([
      [
        'typehint' => 'Drupal\Core\Entity\EntityTypeManagerInterface',
        'service_name' => 'entity_type.manager',
        'property_name' => 'entityTypeManager',
        'parameter_name' => 'entity_type_manager',
      ],
    ]);

    $this->assertStringContainsString("#[Argument('The alpha parameter.')]", $command_class_file);
    $this->assertStringContainsString("#[Option('The cake option.', shortcut: 'c')]", $command_class_file);

    $invoke_method_tester = $php_tester->getMethodTester('__invoke');
    $invoke_method_tester->assertHasParameters([
        'input' => 'Symfony\Component\Console\Input\InputInterface',
        'output' => 'Symfony\Component\Console\Output\OutputInterface',
        'alpha' => 'string',
        'beta' => 'bool',
        'cake' => 'string',
        'pie' => 'bool',
    ]);

    $invoke_method_tester->assertHasLine('return Command::SUCCESS;');
  }

}
