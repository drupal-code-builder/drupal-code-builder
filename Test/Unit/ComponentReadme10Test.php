<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the README file.
 */
class ComponentReadme10Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 10;

  /**
   * Tests the README file.
   */
  function test8ReadmeFile() {
    // Create a module.
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'readme' => TRUE,
    ];
    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'README.md',
    ], $files);

    $readme_file = $files['README.md'];

    $this->assertStringContainsString("# Test module", $readme_file);
    $this->assertStringContainsString("TODO: write some documentation.", $readme_file);
  }

  /**
   * Tests the README file with a settings form.
   */
  function testReadmeWithSettings() {
    // Create a module.
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      // Test that even with README not set, one is generated when the
      // settings form is set.
      'readme' => FALSE,
      'settings_form' => [
        'parent_route' => 'system.admin_config_system',
      ],
    ];
    $files = $this->generateModuleFiles($module_data);

    $readme_file = $files['README.md'];

    $this->assertStringContainsString("# Test module", $readme_file);
    $this->assertStringContainsString("TODO: write some documentation.", $readme_file);
    $this->assertStringContainsString("## Configuration", $readme_file);
    $this->assertStringContainsString("To configure Test module:", $readme_file);
    $this->assertStringContainsString("Go to Administration » System » Test module", $readme_file);
  }

  /**
   * Data provider for testReadmeWithDependencies().
   */
  function dataReadmeWithDependencies() {
    return [
      'readme_with_contrib_deps' => [
        // Readme.
        TRUE,
        // Dependencies.
        [
          'drupal:node',
          'taxonomy',
          'flag:flag',
          'token:token',
        ],
        // Expect README.
        TRUE,
        // Expect Requirements section.
        TRUE,
        // Expect dependencies in Requirements section.
        TRUE,
      ],
      // Even with README not set, one is generated when there are dependencies.
      'no_readme_with_contrib_deps' => [
        FALSE,
        [
          'drupal:node',
          'taxonomy',
          'flag:flag',
          'token:token',
        ],
        TRUE,
        TRUE,
        TRUE,
      ],
      'readme_with_core_deps' => [
        TRUE,
        [
          'drupal:node',
          // 'taxonomy',
        ],
        TRUE,
        TRUE,
        FALSE,
      ],
      'no_readme_with_core_deps' => [
        FALSE,
        [
          'drupal:node',
          // 'taxonomy',
        ],
        FALSE,
        FALSE,
        FALSE,
      ],
      'readme_no_deps' => [
        TRUE,
        [],
        TRUE,
        TRUE,
        FALSE,
      ],
      'no_readme_no_deps' => [
        FALSE,
        [],
        FALSE,
        FALSE,
        FALSE,
      ],
    ];
  }

  /**
   * Tests the README file with dependencies.
   *
   * @dataProvider dataReadmeWithDependencies
   *
   * @param boolean $readme
   *   The value for the readme property in the module data.
   * @param array $dependencies
   *   The value for the module_dependencies property in the module data.
   * @param boolean $expect_readme
   *   Whether to expect a README file.
   * @param boolean $expect_requirements_section
   *   Whether to expect a Requirements section in the README.
   * @param boolean $expect_requirements_dependencies
   *   Whether to expect a list of dependencies in the Requirements section.
   */
  function testReadmeWithDependencies(bool $readme, array $dependencies, bool $expect_readme, bool $expect_requirements_section, bool $expect_requirements_dependencies) {
    // Create a module.
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'readme' => FALSE,
      'module_dependencies' => [
        'drupal:node',
        'taxonomy',
        'flag:flag',
        'token:token',
      ],
    ];

    $module_data['readme'] = $readme;
    $module_data['module_dependencies'] = $dependencies;

    $files = $this->generateModuleFiles($module_data);

    if ($expect_readme) {
      $this->assertArrayHasKey('README.md', $files);
    }
    else {
      $this->assertArrayNotHasKey('README.md', $files);
      // Nothing more to do if there is no README.
      return;
    }

    $readme_file = $files['README.md'];

    $this->assertStringContainsString("# Test module", $readme_file);
    $this->assertStringContainsString("TODO: write some documentation.", $readme_file);

    if ($expect_requirements_section) {
      $this->assertStringContainsString("## Requirements\n", $readme_file);
    }
    else {
      $this->assertStringNotContainsString("## Requirements\n", $readme_file);
      $this->assertStringNotContainsString("This module requires the following modules:", $readme_file);
      $this->assertStringNotContainsString("- [flag](https://www.drupal.org/project/flag)", $readme_file);
      $this->assertStringNotContainsString("- [token](https://www.drupal.org/project/token)", $readme_file);
    }

    if ($expect_requirements_dependencies) {
      $this->assertStringContainsString("This module requires the following modules:", $readme_file);
      $this->assertStringContainsString("- [flag](https://www.drupal.org/project/flag)", $readme_file);
      $this->assertStringContainsString("- [token](https://www.drupal.org/project/token)", $readme_file);
    }
    else {
      $this->assertStringContainsString("This module requires no modules outside of Drupal core.", $readme_file);
    }
  }

  /**
   * Tests the README file with a settings form and dependencies.
   */
  function testReadmeWithSettingsAndDependencies() {
    // Create a module.
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'readme' => FALSE,
      'settings_form' => [
        'parent_route' => 'system.admin_config_system',
      ],
      'module_dependencies' => [
        'flag:flag',
      ],
    ];
    $files = $this->generateModuleFiles($module_data);

    $readme_file = $files['README.md'];

    $this->assertStringContainsString("# Test module", $readme_file);
    $this->assertStringContainsString("TODO: write some documentation.", $readme_file);
    $this->assertStringContainsString("## Configuration", $readme_file);
    $this->assertStringContainsString("To configure Test module:", $readme_file);
    $this->assertStringContainsString("Go to Administration » System » Test module", $readme_file);
    $this->assertStringContainsString("## Requirements\n", $readme_file);
    $this->assertStringContainsString("This module requires the following modules:", $readme_file);
    $this->assertStringContainsString("- [flag](https://www.drupal.org/project/flag)", $readme_file);

    // Assert the order of sections.
    $headings = [];
    foreach (explode("\n", $readme_file) as $line) {
      if (str_starts_with($line, '## ')) {
        $heading = trim($line, ' #');
        $headings[] = $heading;
      }
    }
    $this->assertSame([
      "Requirements",
      "Configuration",
    ],
    $headings);
  }

}
