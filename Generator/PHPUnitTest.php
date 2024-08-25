<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\Docblock;
use MutableTypedData\Data\DataItem;

/**
 * Component generator: PHPUnit test class.
 */
class PHPUnitTest extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  protected $functionOrdering = [
    // We only generate protected and public methods. The protected setUp()
    // should go before the public test method.
    'protected',
    'public',
  ];

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
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
            'use_module_dependencies' => [
              'value' => FALSE,
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
            'use_module_dependencies' => [
              'value' => TRUE,
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
            'use_module_dependencies' => [
              'value' => TRUE,
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
            'use_module_dependencies' => [
              'value' => TRUE,
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
            'use_module_dependencies' => [
              'value' => TRUE,
            ],
          ],
        ],
      ],
      'existing_site' => [
        'label' => 'Existing site test',
        'description' => 'Requires the weitzman/drupal-test-traits package.',
        'data' => [
          'force' => [
            'relative_namespace' => [
              'value' => 'ExistingSite',
            ],
            'parent_class_name' => [
              'value' => '\weitzman\DrupalTestTraits\ExistingSiteBase',
            ],
            'use_module_dependencies' => [
              'value' => FALSE,
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
      'creation_traits' => PropertyDefinition::create('string')
        ->setLabel('Creation traits')
        ->setDescription("Traits which provide useful methods for creating various kinds of test data.")
        ->setMultiple(TRUE)
        ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('Analyse\TestTraits')),
      'module_dependencies' => PropertyDefinition::create('string')
        ->setMultiple(TRUE)
        ->setAutoAcquiredFromRequester(),
      'use_module_dependencies' => PropertyDefinition::create('boolean')
        ->setInternal(TRUE),
      'test_modules' => MergingGeneratorDefinition::createFromGeneratorType('TestModule')
        ->setLabel('Test modules')
        ->setMultiple(TRUE),
    ];

    // Put the parent definitions after ours.
    parent::addToGeneratorDefinition($definition);
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
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

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
        'component_type' => 'TestContainerService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
        'class_has_constructor' => FALSE,
        'class_has_static_factory' => FALSE,
      ];
    }
    foreach ($this->component_data['mocked_services'] as $service_id) {
      $components['service_' . $service_id] = [
        'component_type' => 'TestMockedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
        'class_has_constructor' => FALSE,
        'class_has_static_factory' => FALSE,
      ];
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function getClassDocBlock(): DocBlock {
    $docblock = parent::getClassDocBlock();

    $docblock->group('%module');

    return $docblock;
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

    if ($this->component_data->use_module_dependencies->value) {
      // Get the dependencies of the generated module. These need to be cleaned
      // up to remove any 'project:' prefixes.
      $module_dependencies = $this->component_data->module_dependencies->values();
      array_walk($module_dependencies, function(&$dependency) {
        if ($position = strpos($dependency, ':')) {
          $dependency = substr($dependency, $position + 1);
        }
      });

      // Create the array of modules to install in the test.
      $test_install_modules = array_merge(
        // Some general defaults.
        [
          'system',
          'user',
        ],
        $module_dependencies,
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

    if (in_array($this->component_data->test_type->value, ['browser', 'javascript'])) {
      $this->properties[] = $this->createPropertyBlock(
        'defaultTheme',
        NULL,
        [
          'docblock_inherit' => TRUE,
          'prefixes' => ['protected'],
          'default' => 'stark',
        ]
      );
    }

    // Add properties for services obtained from the container.
    if (!empty($this->getContentsElement('service_property_container'))) {
      // Service class property.
      foreach ($this->getContentsElement('service_property_container') as $service_property) {
        $docblock = DocBlock::property();
        $docblock[] = $service_property['description'] . '.';
        $docblock->var($service_property['typehint']);
        $property_code = $docblock->render();

        $property_code[] = 'protected $' . $service_property['property_name'] . ';';

        $this->properties[] = $property_code;
      }
    }

    foreach ($this->component_data['creation_traits'] as $data) {
      $this->traits[] = [
        "use {$data};",
      ];
    }

    parent::collectSectionBlocks();
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

    // $this->containedComponents->dump();

    // Container services setup.
    if (!empty($this->getContentsElement('service_container'))) {
      $setup_lines[] = '';

      // Use the main service infor rather than 'container_extraction', as
      // that is intended for use in an array and so has a terminal comma.
      // TODO: remove the terminal comma so we can use it here!
      foreach ($this->getContentsElement('service_container') as $service_info) {
        $setup_lines[] = "£this->{$service_info['property_name']} = £this->container->get('{$service_info['id']}');";
      }
    }

    // Mocked services.
    if (!empty($this->getContentsElement('service_mocked'))) {
      $setup_lines[] = '';

      foreach ($this->getContentsElement('service_mocked') as $service_info) {
        $setup_lines[] = "// Mock the {$service_info['label']}.";
        $setup_lines[] = "£{$service_info['variable_name']} = £this->prophesize({$service_info['typehint']}::class);";
        $setup_lines[] = "£this->container->set('{$service_info['id']}', £{$service_info['variable_name']}->reveal());";
      }
    }

    return $setup_lines ;
  }

}
