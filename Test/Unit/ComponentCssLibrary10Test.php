<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests for library generation.
 *
 * @group yaml
 */
class ComponentCssLibrary8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test generating a module with a library with CSS and JS assets
   */
  public function testLibraryGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'library' => [
        0 => [
          'library_name' => 'test_module_library',
          'version' => '1.x',
          'css_assets' => [
            0 => [
              'filename' => 'css_one',
              'style_type' => 'component',
            ],
            1 => [
              'filename' => 'css_two',
              'style_type' => 'theme',
            ],
          ],
          'js_assets' => [
            0 => [
              'filename' => 'js_one',
            ],
            1 => [
              'filename' => 'js_two',
            ],
            2 => [
              'filename' => 'https://example.com/external.min.js',
            ],
          ],
          'dependencies' => [
            'core/jquery',
            'foo/bar',
          ],
        ],
        1 => [
          'library_name' => 'second_library',
          'version' => '1.x',
          'header' => TRUE,
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.libraries.yml',
      'css/css_one.css',
      'css/css_two.css',
      'js/js_one.js',
      'js/js_two.js',
    ], $files);

    $libraries_file = $files['test_module.libraries.yml'];

    $yaml_tester = new YamlTester($libraries_file);
    $yaml_tester->assertHasProperty('test_module_library');
    $yaml_tester->assertPropertyHasValue(['test_module_library', 'version'], '1.x', "The libraries file declares the library version.");
    $yaml_tester->assertHasProperty(['test_module_library', 'css', 'component', 'css/css_one.css'], "The libraries file declares the CSS file.");
    $yaml_tester->assertHasProperty(['test_module_library', 'css', 'theme', 'css/css_two.css'], "The libraries file declares the CSS file.");
    $yaml_tester->assertHasProperty(['test_module_library', 'js', 'js/js_one.js'], "The libraries file declares the JS file.");
    $yaml_tester->assertHasProperty(['test_module_library', 'js', 'js/js_two.js'], "The libraries file declares the JS file.");
    $yaml_tester->assertHasProperty(['test_module_library', 'js', 'https://example.com/external.min.js'], "The libraries file declares the external JS URL.");
    $yaml_tester->assertPropertyHasValue(['test_module_library', 'js', 'https://example.com/external.min.js', 'type'], 'external');
    $yaml_tester->assertPropertyHasValue(['test_module_library', 'js', 'https://example.com/external.min.js', 'minified'], TRUE);
    $yaml_tester->assertPropertyHasValue(['test_module_library', 'dependencies', 0], 'core/jquery', "The libraries file declares the dependencies.");
    $yaml_tester->assertPropertyHasValue(['test_module_library', 'dependencies', 1], 'foo/bar', "The libraries file declares the dependencies.");
    $yaml_tester->assertNotHasProperty(['test_module_library', 'header'], 'The library does not specify the header property.');

    $yaml_tester->assertHasProperty('second_library');
    $yaml_tester->assertPropertyHasValue(['second_library', 'version'], '1.x', "The libraries file declares the library version.");
    $yaml_tester->assertPropertyHasValue(['second_library', 'header'], TRUE, "The libraries file declares the library version.");
    $yaml_tester->assertPropertyHasValue(['second_library', 'dependencies'], ['core/jquery', 'core/drupal'], "The second library has the default dependencies.");

    $js_file = $files['js/js_one.js'];
    // Some crude testing of the JS file.
    $this->assertStringContainsString("Defines Javascript behaviors for the Test Module module.", $js_file);
    $this->assertStringContainsString("Drupal.behaviors.testModule =", $js_file);
    $this->assertStringContainsString("attach: function (context, settings)", $js_file);
  }

}
