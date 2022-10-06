<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Data\DataItem;

/**
 * Component generator: PHPUnit test class.
 */
class PHPUnitTest extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
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
            'relative_namespace' => [
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
            'relative_namespace' => [
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
            'relative_namespace' => [
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
            'relative_namespace' => [
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
            'relative_namespace' => [
              'value' => 'FunctionalJavascript',
            ],
            'parent_class_name' => [
              'value' => '\Drupal\FunctionalJavascriptTests\WebDriverTestBase',
            ],
          ],
        ],
      ],
    ];

    $properties = [
      'test_type' => PropertyDefinition::create('string')
        ->setLabel('Test type')
        ->setPresets($test_type_presets)
        ->setRequired(TRUE),
      'plain_class_name' => PropertyDefinition::create('string')
        ->setLabel('Test class name')
        ->setDescription("The short class name of the test.")
        ->setRequired(TRUE),
        // TODO: class name validation
        // if (!preg_match('@^\w+$@', $component_data[$property_name])) {
        //   TODO: also check camel case.
        //   return 'Invalid class name.';
        // }
        // TODO: must also end in 'Test'.
      'container_services' => PropertyDefinition::create('string')
        ->setLabel('Services from the container')
        ->setDescription("The services that this test class gets from the container, to use normally.")
        ->setMultiple(TRUE)
        ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportServiceData')),
      'mocked_services' => PropertyDefinition::create('string')
        ->setLabel('Services to mock')
        ->setDescription("The services that this test class creates mocks for.")
        ->setMultiple(TRUE)
        ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportServiceData')),
      'module_dependencies' => PropertyDefinition::create('string')
        ->setMultiple(TRUE)
        ->setAutoAcquiredFromRequester(),
      'test_modules' => static::getLazyDataDefinitionForGeneratorType('TestModule')
        ->setLabel('Test modules')
        ->setMultiple(TRUE),
    ];

    // Put the parent definitions after ours.
    $definition = parent::getPropertyDefinition();
    $properties += $definition->getProperties();
    $definition->setProperties($properties);

    // Override some parent definitions to provide computed defaults.
    // Qualified class names and paths work differently for test classes because
    // the namespace above the module is different and the path is different.
    // Treat this as relative to the \Drupal\Tests\mymodule namespace.
    // $data_definition['relative_namespace']->getDefault()
    //   ->setCallable(function (DataItem $component_data) {
    //     $test_data = $component_data->getParent();

    //     return $test_data->test_namespace->value;
    //   });

    // $data_definition['relative_class_name']['default'] = function ($component_data) {
    //   if (isset($component_data['test_namespace'])) {
    //     return [
    //       $component_data['test_namespace'],
    //       $component_data['test_class_name'],
    //     ];
    //   }
    //   else {
    //     return [
    //       $component_data['test_class_name'],
    //     ];
    //   }
    // };

    $definition->getProperty('relative_class_name')->setInternal(TRUE);

    $definition->getProperty('qualified_class_name_pieces')->getDefault()
      ->setCallable(function (DataItem $component_data) {
        $class_name_pieces = array_merge(
          [
            'Drupal',
            'Tests',
            '%module',
          ],
          $component_data->getParent()->relative_class_name_pieces->get()
        );

      return $class_name_pieces;
    });

    $definition->getProperty('path')->getDefault()
      ->setCallable(function (DataItem $component_data) {
        // Lop off the initial Drupal\Tests\module and the final class name to
        // build the path.
        $path_pieces = array_slice($component_data->getParent()->qualified_class_name_pieces->value, 3, -1);
        // Add the initial tests/src to the front.
        array_unshift($path_pieces, 'tests/src');

        return implode('/', $path_pieces);
      });

    $definition->getProperty('docblock_first_line')->setLiteralDefault("Test case class TODO.");

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = [];

    $components['method_setup'] = [
      'component_type' => 'PHPFunction',
      'containing_component' => '%requester',
      'function_name' => 'setUp',
      'docblock_inherit' => TRUE,
      'prefixes' => ['protected'],
      'return_type' => 'void',
      // 'body' is set later, in classCodeBody().
    ];

    $components['method_test'] = [
      'component_type' => 'PHPFunction',
      'containing_component' => '%requester',
      'function_name' => 'testMyTest',
      'function_docblock_lines' => [
        'Tests the TODO.',
      ],
      'prefixes' => ['public'],
      'body' => [
        '// TODO: test code here.',
      ],
    ];

    foreach ($this->component_data['container_services'] as $service_id) {
      $components['service_' . $service_id] = [
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
        'role_suffix' => '_container',
      ];
    }
    foreach ($this->component_data['mocked_services'] as $service_id) {
      $components['service_' . $service_id] = [
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
        'role_suffix' => '_mocked',
      ];
    }

    return $components;
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
  protected function classCodeBody() {
    // Quick temporary hack to set the method lines for working with services
    // into the setUp() method.
    // TODO: change the services to be components that are contained in the
    // method_setup function component.
    foreach ($this->containedComponents['function'] as $key => $child_item) {
      $parts = explode('/', $key);
      $local_name = end($parts);

      if ($local_name == 'method_setup') {
        $setup_method_component = $child_item;
        break;
      }
    }

    $setup_method_component->component_data->body = $this->getSetupMethodLines();

    return parent::classCodeBody();
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    // Set up properties and methods.

    if ($this->component_data['test_type'] != 'unit') {
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

      // Class properties.
      // The modules property should come first, as it's the most interesting to
      // a developer; others are boilerplate.
      $this->properties[] = $this->createPropertyBlock(
        'modules',
        'array',
        [
          'docblock_first_line' => 'The modules to enable.',
          'prefixes' => ['protected', 'static'],
          'default' => $test_install_modules,
          'break_array_value' => TRUE,
        ]
      );
    }

    // Add properties for services obtained from the container.
    if (!empty($this->getContentsElement('service_property_container'))) {
      // Service class property.
      foreach ($this->getContentsElement('service_property_container') as $service_property) {
        $property_code = $this->docBlock([
          $service_property['description'] . '.',
          '',
          '@var ' . $service_property['typehint']
        ]);
        $property_code[] = 'protected $' . $service_property['property_name'] . ';';

        $this->properties[] = $property_code;
      }
    }
  }

  /**
   * Gets the code lines for the setUp() method.
   *
   * @return array
   *   Array of code lines.
   */
  protected function getSetupMethodLines(): array {
    $setup_lines = [];

    $setup_lines[] = 'parent::setUp();';
    $setup_lines[] = '';

    // $this->containedComponents->dump();

    // Container services setup.
    if (!empty($this->getContentsElement('service_container'))) {
      // Use the main service infor rather than 'container_extraction', as
      // that is intended for use in an array and so has a terminal comma.
      // TODO: remove the terminal comma so we can use it here!
      foreach ($this->getContentsElement('service_container') as $service_info) {
        $setup_lines[] = "£this->{$service_info['property_name']} = £this->container->get('{$service_info['id']}');";
      }

      $setup_lines[] = '';
    }

    // Mocked services.
    if (!empty($this->getContentsElement('service_mocked'))) {
      foreach ($this->getContentsElement('service_mocked') as $service_info) {
        $setup_lines[] = "// Mock the {$service_info['label']} service.";
        $setup_lines[] = "£{$service_info['variable_name']} = £this->prophesize({$service_info['typehint']}::class);";
        $setup_lines[] = "£this->container->set('{$service_info['id']}', £{$service_info['variable_name']}->reveal());";
      }
    }

    return $setup_lines ;
  }

}
