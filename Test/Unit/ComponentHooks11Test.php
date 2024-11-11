<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests for Hooks component on Drupal 11.
 *
 * @group hooks
 */
class ComponentHooks11Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 11;

  /**
   * Tests procedural hooks can also be generated on 11.
   */
  public function testHookImplementationTypeProcedural() {
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hook_implementation_type' => 'procedural',
      'hooks' => [
        'hook_block_access',
        'hook_form_alter',
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.module',
    ], $files);
  }

  /**
   * Tests generating OO hooks.
   */
  public function testOOHooks() {
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hook_implementation_type' => 'oo',
      'hooks' => [
        'hook_block_access',
        'hook_form_alter',
        'hook_install',
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.install',
      'src/Hooks/TestModuleHooks.php',
    ], $files);

    $hooks_file = $files['src/Hooks/TestModuleHooks.php'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $hooks_file);
    $php_tester->assertDrupalCodingStandards([
      // Temporarily exclude the sniff for comment lines being too long, as a
      // comment in hook_form_alter() violates this.
      // TODO: remove this when https://www.drupal.org/project/drupal/issues/2924184
      // is fixed.
      'Drupal.Files.LineLength.TooLong',
      // Probably hard to fix because of tokens, arrrgh.
      'SlevomatCodingStandard.Namespaces.AlphabeticallySortedUses.IncorrectlyOrderedUses',
    ]);

    $php_tester->assertHasClass('Drupal\test_module\Hooks\TestModuleHooks');
    $php_tester->assertHasMethod('formAlter');
    $php_tester->assertHasMethod('blockAccess');
    $php_tester->assertNotHasMethod('install');

    // TODO: Attribute testing.
    $this->assertStringContainsString("#[Hook('form_alter')]", $hooks_file);
    $this->assertStringContainsString("#[Hook('block_access')]", $hooks_file);

    // Check the .install file has a procedural implementation for
    // hook_install().
    $install_file = $files['test_module.install'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $install_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertFileDocblockHasLine("Contains install and update hooks for the Test Module module.");
    $php_tester->assertHasHookImplementation('hook_install', $module_name);
  }

  /**
   * Tests generating OO hooks requested indirectly.
   */
  public function testIndirectOOHooks() {
    // Same technique as testPluginsGenerationReplacePluginClass().
    $drupal_container = $this->prophesize(\Psr\Container\ContainerInterface::class);
    $drupal_container
      ->get('plugin.manager.element_info')
      ->willReturn(new class {
        public function getDefinition($plugin_id) {
          return [
            // The definition doesn't have the initial '\'.
            'class' => 'Drupal\somemodule\Element\ParentElement',
          ];
        }
      });
    $this->container->get('environment')->setContainer($drupal_container->reveal());

    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      // Request a plugin type so we get the plugin info alter hook requested
      // indirectly by the plugin type generator.
      'plugins' => [
        0 => [
          'plugin_type' => 'element_info',
          'plugin_name' => 'alpha',
          'parent_plugin_id' => 'parent',
          'replace_parent_plugin' => TRUE,
        ]
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertArrayHasKey('src/Hooks/TestModuleHooks.php', $files);

    $hooks_file = $files['src/Hooks/TestModuleHooks.php'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $hooks_file);
    $php_tester->assertDrupalCodingStandards([
      // Probably hard to fix because of tokens, arrrgh.
      'SlevomatCodingStandard.Namespaces.AlphabeticallySortedUses.IncorrectlyOrderedUses',
    ]);
    $php_tester->assertHasClass('Drupal\test_module\Hooks\TestModuleHooks');
    $php_tester->assertHasMethod('elementPluginAlter');
  }

  /**
   * Tests generation of legacy hooks.
   */
  public function testHookImplementationLegacy() {
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hook_implementation_type' => 'oo_legacy',
      'hooks' => [
        'hook_block_access',
        'hook_block_view_alter',
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.module',
      'test_module.services.yml',
      'src/Hooks/TestModuleHooks.php',
    ], $files);

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', 'Drupal\test_module\Hooks\TestModuleHooks']);
    $yaml_tester->assertPropertyHasValue(['services', 'Drupal\test_module\Hooks\TestModuleHooks', 'class'], 'Drupal\test_module\Hooks\TestModuleHooks');
    $yaml_tester->assertPropertyHasValue(['services', 'Drupal\test_module\Hooks\TestModuleHooks', 'autowire'], 'true');

    $module_file = $files['test_module.module'];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $module_file);
    $php_tester->assertDrupalCodingStandards([
      'SlevomatCodingStandard.Namespaces.AlphabeticallySortedUses.IncorrectlyOrderedUses',
    ]);
    $php_tester->assertFileDocblockHasLine("Contains hook implementations for the Test Module module.");

    $function_tester = $php_tester->getFunctionTester('test_module_block_access');
    $function_tester->getDocBlockTester()->assertHasLine('Legacy hook implementation.');
    $function_tester->assertHasLine('return \Drupal::service(TestModuleHooks::class)->blockAccess($block, $operation, $account);');

    $function_tester = $php_tester->getFunctionTester('test_module_block_view_alter');
    $function_tester->getDocBlockTester()->assertHasLine('Legacy hook implementation.');
    $function_tester->assertHasLine('\Drupal::service(TestModuleHooks::class)->blockViewAlter($build, $block);');
  }

  /**
   * Tests generation of legacy hooks merges with other generated services.
   */
  public function testOtherServicesHookImplementationLegacy() {
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'hook_implementation_type' => 'oo_legacy',
      'hooks' => [
        'hook_block_access',
      ],
      'services' => [
        0 => [
          'service_name' => 'my_service',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.module',
      'test_module.services.yml',
      'src/MyService.php',
      'src/Hooks/TestModuleHooks.php',
    ], $files);

    $services_file = $files["$module_name.services.yml"];

    $yaml_tester = new YamlTester($services_file);
    $yaml_tester->assertHasProperty('services');
    $yaml_tester->assertHasProperty(['services', 'Drupal\test_module\Hooks\TestModuleHooks']);
    $yaml_tester->assertPropertyHasValue(['services', 'Drupal\test_module\Hooks\TestModuleHooks', 'class'], 'Drupal\test_module\Hooks\TestModuleHooks');
    $yaml_tester->assertPropertyHasValue(['services', 'Drupal\test_module\Hooks\TestModuleHooks', 'autowire'], 'true');

    $yaml_tester->assertHasProperty(['services', "$module_name.my_service"]);
    $yaml_tester->assertPropertyHasValue(['services', "$module_name.my_service", 'class'], "Drupal\\$module_name\\MyService");
  }

}
