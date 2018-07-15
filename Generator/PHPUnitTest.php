<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

/**
 * Component generator: PHPUnit test class.
 */
class PHPUnitTest extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    // Presets for the different types of test.
    $test_type_presets = [
      'unit' => [
        // Option label.
        'label' => 'Unit test',
        'data' => [
          // Values that are forced on other properties.
          // These are set in the process stage.
          'force' => [
            // Name of another property => Value for that property.
            'test_namespace' => [
              'value' => 'Unit',
            ],
            'parent_class_name' => [
              'value' => '\Drupal\Tests\UnitTestCase',
            ],
          ],
        ],
      ],
      'kernel' => [
        'label' => 'Kernel test',
        'data' => [
          'force' => [
            'test_namespace' => [
              'value' => 'Kernel',
            ],
            'parent_class_name' => [
              'value' => '\Drupal\KernelTests\KernelTestBase',
            ],
          ],
        ],
      ],
      'kernel_entity' => [
        'label' => 'Kernel test with entity support',
        'data' => [
          'force' => [
            'test_namespace' => [
              'value' => 'Kernel',
            ],
            'parent_class_name' => [
              'value' => '\Drupal\KernelTests\Core\Entity\EntityKernelTestBase',
            ],
          ],
        ],
      ],
      'browser' => [
        'label' => 'Browser test',
        'data' => [
          'force' => [
            'test_namespace' => [
              'value' => 'Functional',
            ],
            'parent_class_name' => [
              'value' => '\Drupal\Tests\BrowserTestBase',
            ],
          ],
        ],
      ],
      'javascript' => [
        'label' => 'Javascript test',
        'data' => [
          'force' => [
            'test_namespace' => [
              'value' => 'FunctionalJavascript',
            ],
            'parent_class_name' => [
              'value' => '\Drupal\FunctionalJavascriptTests\JavascriptTestBase',
            ],
          ],
        ],
      ],
    ];

    $data_definition = [
      'test_type' => [
        'label' => 'Test type',
        'presets' => $test_type_presets,
      ],
      'test_namespace' => [
        'label' => "The namespace piece above the class name, e.g. 'Kernel'",
        'internal' => TRUE,
      ],
      'test_class_name' => [
        'label' => 'Test class name',
        'description' => "The short class name of the test.",
        'process_default' => TRUE,
        'validation' => function($property_name, $property_info, $component_data) {
          // TODO: check camel case!
          if (!preg_match('@^\w+$@', $component_data[$property_name])) {
            return 'Invalid class name.';
          }
        },
      ],
      'module_dependencies' => [
        'acquired' => TRUE,
      ],
      'test_modules' => [
        'label' => 'Test modules',
        'format' => 'compound',
        'component_type' => 'TestModule',
        'default' => function($component_data) {
          return [
            0 => [
              // Create a default module name from the requesting test class name.
              'root_name' => CaseString::pascal($component_data['test_class_name'])->snake(),
            ],
          ];
        },
      ],
    ];

    // Put the parent definitions after ours.
    $data_definition += parent::componentDataDefinition();

    // Override some parent definitions to provide computed defaults.
    // Qualified class names and paths work differently for test classes because
    // the namespace above the module is different and the path is different.
    // Treat this as relative to the \Drupal\Tests\mymodule namespace.
    $data_definition['relative_class_name']['default'] = function ($component_data) {
      if (isset($component_data['test_namespace'])) {
        return [
          $component_data['test_namespace'],
          $component_data['test_class_name'],
        ];
      }
      else {
        return [
          $component_data['test_class_name'],
        ];
      }
    };

    $data_definition['qualified_class_name_pieces']['default'] = function ($component_data) {
      $class_name_pieces = array_merge([
        'Drupal',
        'Tests',
        '%module',
      ], $component_data['relative_class_name']);

      return $class_name_pieces;
    };

    $data_definition['path']['default'] = function($component_data) {
      // Lop off the initial Drupal\Tests\module and the final class name to
      // build the path.
      $path_pieces = array_slice($component_data['qualified_class_name_pieces'], 3, -1);
      // Add the initial tests/src to the front.
      array_unshift($path_pieces, 'tests/src');

      return implode('/', $path_pieces);
    };

    $data_definition['docblock_first_line']['default'] = function ($component_data) {
      return "Test case class TODO.";
    };

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    // We have no subcomponents.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getClassDocBlockLines() {
    $docblock_lines = parent::getClassDocBlockLines();
    $docblock_lines[] = '';
    $docblock_lines[] = '@group %module';

    return $docblock_lines;
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    // Set up properties and methods.

    // Create the array of modules to install in the test.
    $test_install_modules = array_merge(
      // Some general defaults.
      [
        'system',
        'user',
      ],
      // The generated module's dependencies.
      $this->component_data['module_dependencies'],
      // The generated module itself.
      [
        '%module',
      ]
    );
    // Any test modules.
    if (!empty($this->component_data['test_modules'])) {
      foreach ($this->component_data['test_modules'] as $data) {
        $test_install_modules[] = $data['root_name'];
      }
    }

    $this->properties[] = $this->createPropertyBlock(
      'modules',
      'array',
      [
        'docblock_first_line' => 'The modules to enable.',
        'prefixes' => ['public', 'static'],
        'default' => $test_install_modules,
        'break_array_value' => TRUE,
      ]
    );

    $setup_lines = $this->buildMethodHeader('setUp', [], ['inheritdoc' => TRUE, 'prefixes' => ['protected']]);
    $setup_lines[] = '  parent::setUp();';
    $setup_lines[] = '  // TODO: setup tasks here.';
    $setup_lines[] = '}';

    $this->functions[] = $setup_lines;

    $test_method_lines = $this->buildMethodHeader('testMyTest', [], ['docblock_first_line' => 'Tests the TODO.', 'prefixes' => ['public']]);
    $test_method_lines[] = '  // TODO: test code here.';
    $test_method_lines[] = '}';

    $this->functions[] = $test_method_lines;
  }

}
