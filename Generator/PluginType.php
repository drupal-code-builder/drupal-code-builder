<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\OptionDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\File\DrupalExtension;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\VariantDefinition;

/**
 * Generator for a plugin type.
 */
class PluginType extends BaseGenerator implements AdoptableInterface {

  use NameFormattingTrait;

  /**
   * {@inheritdoc}
   */
  protected static $dataType = 'mutable';

  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    // ARGH common properties! -- root name!
    $definition
      ->setProperties([
        'discovery_type' => PropertyDefinition::create('string')
          ->setLabel('Plugin discovery type')
          ->setDescription("The way in which plugins of this type are formed.")
          ->setOptions(
            OptionDefinition::create(
              'annotation',
              'Annotation plugin',
              "Each plugin is a class with an annotation to declare the plugin data. WARNING: This plugin discovery type will soon be deprecated in Drupal core."
            ),
            OptionDefinition::create(
              'attribute',
              'Attribute plugin',
              "Each plugin is a class with an attribute to declare the plugin data."
            ),
            OptionDefinition::create(
              'yaml',
              'YAML plugin',
              "Plugins are declared in a single YAML file, and usually share the same class."
            )
          )
        ])
      ->setVariants([
        'annotation' => VariantDefinition::create()
          ->setLabel('Annotation plugin')
          ->setProperties([
            'plugin_type' => PropertyDefinition::create('string')
              ->setLabel('Plugin type ID')
              ->setDescription("The identifier of the plugin type. This is used to form the name of the manager service by prepending 'plugin.manager.'.")
              ->setRequired(TRUE)
              ->setValidators('machine_name'),
            'plugin_label' => PropertyDefinition::create('string')
              ->setLabel('Plugin type label')
              ->setDescription("The human-readable label for plugins of this type. This is used in documentation text.")
              ->setRequired(TRUE)
              ->setDefault(DefaultDefinition::create()
                ->setExpression("machineToLabel(get('..:plugin_type'))")
                ->setDependencies('..:plugin_type')
              ),
            'plugin_subdirectory' => PropertyDefinition::create('string')
              ->setLabel('Plugin subdirectory')
              ->setDescription("The subdirectory within the Plugins directory for plugins of this type.")
              ->setRequired(TRUE)
              ->setDefault(DefaultDefinition::create()
                ->setExpression("machineToClass(get('..:plugin_type'))")
                ->setDependencies('..:plugin_type')
              ),
            // TODO: 'plugin_relative_namespace' => PropertyDefinition::create('string')
            // TODO: other computed.
            'annotation_class' => PropertyDefinition::create('string')
              ->setLabel('Annotation class name')
              ->setRequired(TRUE)
              ->setDefault(DefaultDefinition::create()
                ->setExpression("machineToClass(get('..:plugin_type'))")
                ->setDependencies('..:plugin_type')
              )
              ->setValidators('class_name'),
            'info_alter_hook' => PropertyDefinition::create('string')
              ->setLabel('Alter hook name')
              ->setDescription("The name of the hook used to alter the info for plugins of this type, without the 'hook_' prefix.")
              ->setRequired(TRUE)
              ->setDefault(DefaultDefinition::create()
                ->setExpression("get('..:plugin_type') ~ '_info'")
                ->setDependencies('..:plugin_type')
              )
              ->setValidators('machine_name'),
            // Stupidly named so they switch between both class-based discovery
            // variants.
            'attribute_properties' => PropertyDefinition::create('complex')
              ->setLabel("Annotation properties")
              ->setDescription("Properties for the plugin type's annotation class. These do not produce generated code, but are here to facilitate the conversion to attribute plugin types.")
              ->setMultiple(TRUE)
              ->setProperties([
                'name' => PropertyDefinition::create('string')
                  ->setLabel('Parameter name')
                  ->setRequired(TRUE),
                'type' => PropertyDefinition::create('string')
                  ->setLabel('Parameter type')
                  ->setRequired(TRUE)
                  ->setLiteralDefault('string'),
                'description' => PropertyDefinition::create('string')
                  ->setLabel('Parameter description')
                  ->setLiteralDefault('TODO: parameter description.'),
              ]),
        ]),
        'attribute' => VariantDefinition::create()
          ->setLabel('Attribute plugin')
          ->setProperties([
            'plugin_type' => PropertyDefinition::create('string')
              ->setLabel('Plugin type ID')
              ->setDescription("The identifier of the plugin type. This is used to form the name of the manager service by prepending 'plugin.manager.'.")
              ->setRequired(TRUE)
              ->setValidators('machine_name'),
            'plugin_label' => PropertyDefinition::create('string')
              ->setLabel('Plugin type label')
              ->setDescription("The human-readable label for plugins of this type. This is used in documentation text.")
              ->setRequired(TRUE)
              ->setDefault(DefaultDefinition::create()
                ->setExpression("machineToLabel(get('..:plugin_type'))")
                ->setDependencies('..:plugin_type')
              ),
            'plugin_subdirectory' => PropertyDefinition::create('string')
              ->setLabel('Plugin subdirectory')
              ->setDescription("The subdirectory within the Plugins directory for plugins of this type.")
              ->setRequired(TRUE)
              ->setDefault(DefaultDefinition::create()
                ->setExpression("machineToClass(get('..:plugin_type'))")
                ->setDependencies('..:plugin_type')
              ),
            // TODO: 'plugin_relative_namespace' => PropertyDefinition::create('string')
            // TODO: other computed.
            'attribute_class' => PropertyDefinition::create('string')
              ->setLabel('Attribute class name')
              ->setRequired(TRUE)
              ->setDefault(DefaultDefinition::create()
                ->setExpression("machineToClass(get('..:plugin_type'))")
                ->setDependencies('..:plugin_type')
              )
              ->setValidators('class_name'),
            'info_alter_hook' => PropertyDefinition::create('string')
              ->setLabel('Alter hook name')
              ->setDescription("The name of the hook used to alter the info for plugins of this type, without the 'hook_' prefix.")
              ->setRequired(TRUE)
              ->setDefault(DefaultDefinition::create()
                ->setExpression("get('..:plugin_type') ~ '_info'")
                ->setDependencies('..:plugin_type')
              )
              ->setValidators('machine_name'),
            'attribute_properties' => PropertyDefinition::create('complex')
              ->setLabel("Attribute properties")
              ->setDescription("Properties for the plugin type's attribute class, which define the properties of individual plugins to be set in their own attribute. The ID, label, and description are automatically added.")
              ->setMultiple(TRUE)
              ->setProperties([
                'name' => PropertyDefinition::create('string')
                  ->setLabel('Parameter name')
                  ->setRequired(TRUE),
                'type' => PropertyDefinition::create('string')
                  ->setLabel('Parameter type')
                  ->setRequired(TRUE)
                  ->setLiteralDefault('string'),
                'description' => PropertyDefinition::create('string')
                  ->setLabel('Parameter description')
                  ->setLiteralDefault('TODO: parameter description.'),
              ]),
        ]),
        'yaml' => VariantDefinition::create()
          ->setLabel('Annotation plugin')
          ->setProperties([
            'plugin_type' => PropertyDefinition::create('string')
              ->setLabel('Plugin type ID')
              ->setDescription("The identifier of the plugin type. This is used to form the name of the manager service by prepending 'plugin.manager.'.")
              ->setRequired(TRUE)
              ->setValidators('machine_name'),
            'plugin_label' => PropertyDefinition::create('string')
              ->setLabel('Plugin type label')
              ->setDescription("The human-readable label for plugins of this type. This is used in documentation text.")
              ->setDefault(DefaultDefinition::create()
                ->setExpression("machineToLabel(get('..:plugin_type'))")
                ->setDependencies('..:plugin_type')
              ),
            'plugin_subdirectory' => PropertyDefinition::create('string')
              ->setLabel('Plugin subdirectory')
              ->setDescription("The subdirectory within the Plugins directory for the interface and base class.")
              ->setRequired(TRUE)
              ->setDefault(DefaultDefinition::create()
                ->setExpression("machineToClass(get('..:plugin_type'))")
                ->setDependencies('..:plugin_type')),
            'info_alter_hook' => PropertyDefinition::create('string')
              ->setLabel('Alter hook name')
              ->setDescription("The name of the hook used to alter the info for plugins of this type, without the 'hook_' prefix.")
              ->setRequired(TRUE)
              ->setDefault(DefaultDefinition::create()
                ->setExpression("get('..:plugin_type') ~ '_info'")
                ->setDependencies('..:plugin_type')
              )
              ->setValidators('machine_name'),
          ]),
      ]);

    $common_properties = [
      'base_class_injected_services' => PropertyDefinition::create('string')
        ->setLabel('Plugin base class injected services')
        ->setDescription("Services to inject into the plugin base class.")
        ->setMultiple(TRUE)
        ->setOptionSetDefinition(\DrupalCodeBuilder\Factory::getTask('ReportServiceData')),
      // TODO: move these 3 universal properties to a helper method.
      'root_component_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setAcquiringExpression("getRootComponentName(requester)"),
      'containing_component' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      'component_base_path' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setAcquiringExpression("requester.component_base_path.value"),
      'plugin_plain_class_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setExpression("machineToClass(get('..:plugin_type'))")
          ->setDependencies('..:plugin_type')
        ),
      'plugin_manager_service_id' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(function ($component_data) {
          // Namespace the service name after the prefix.
          return
            'plugin.manager.'
            . $component_data->getParent()->root_component_name->value
            . '_'
            . $component_data->getParent()->plugin_type->value;
        }),
      'plugin_relative_namespace' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(function ($component_data) {
          // The plugin subdirectory may be nested.
          return str_replace('/', '\\', $component_data->getParent()->plugin_subdirectory->value);
        }),
      'interface' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(function ($component_data) {
          $short_class_name = CaseString::snake($component_data->getParent()->plugin_type->value)->pascal();

          return '\\' . self::makeQualifiedClassName([
            'Drupal',
            $component_data->getParent()->root_component_name->value,
            'Plugin',
            $component_data->getParent()->plugin_relative_namespace->value,
            $short_class_name . 'Interface',
          ]);
        }),
      'base_class_short_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(function ($component_data) {
          $short_class_name = CaseString::snake($component_data->getParent()->plugin_type->value)->pascal();

          // Append 'Base' to the base class name for annotation and attribute
          // plugins, where the base class is actually a base class, but not for
          // YAML plugins, where the base class really is the class that's
          // mostly used for all plugins.
          if (in_array($component_data->getParent()->discovery_type->value, ['annotation', 'attribute'])) {
            $short_class_name .= 'Base';
          }

          return $short_class_name;
        }),
      'base_class' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(function ($component_data) {
          return '\\' . self::makeQualifiedClassName([
            'Drupal',
            $component_data->getParent()->root_component_name->value,
            'Plugin',
            $component_data->getParent()->plugin_relative_namespace->value,
            $component_data->getParent()->base_class_short_name->value,
          ]);
        }),
      // Experimental. Define the data here that will then be set by
      // self::requiredComponents().
      'manager' => MergingGeneratorDefinition::createFromGeneratorType('PluginTypeManager')
        ->setInternal(TRUE),
    ];

    foreach ($definition->getVariants() as $variant) {
      $variant->addProperties($common_properties);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function findAdoptableComponents(DrupalExtension $extension): array {
    $services_filename = $extension->name . '.services.yml';
    if (!$extension->hasFile($services_filename)) {
      return [];
    }

    $yaml = $extension->getFileYaml($services_filename);
    $service_names = array_keys($yaml['services']);
    $plugin_manager_names = array_filter($service_names, fn ($name) => str_starts_with($name, 'plugin.manager.'));
    $plugin_manager_names = array_map(fn ($name) => str_replace('plugin.manager.', '', $name), $plugin_manager_names);
    return array_combine($plugin_manager_names, $plugin_manager_names);
  }

  /**
   * {@inheritdoc}
   */
  public static function adoptComponent(DataItem $component_data, DrupalExtension $extension, string $property_name, string $name): void {
    $services_filename = $extension->name . '.services.yml';
    $yaml = $extension->getFileYaml($services_filename);
    $service_yaml = $yaml['services']['plugin.manager.' . $name];
    $manager_class = $service_yaml['class'];

    // The module might not be enabled, so we can't rely on Drupal's autoloader
    // to find the class.
    $extension->loadClass($manager_class);

    try {
      $constructor_reflection = new \DrupalCodeBuilder\Utility\CodeAnalysis\Method($manager_class, '__construct');
    }
    catch (\ReflectionException $e) {
      // TODO: complain.
      return;
    }

    // Create the data for the plugin type.
    $plugin_type_data_array = [
      'plugin_type' => $name,
    ];

    $constructor_body = $constructor_reflection->getBody();

    // Check for Attribute first, because of BC-compatible hybrids.
    if (str_contains($constructor_body, "Drupal\\{$extension->name}\\Attribute")) {
      // TODO
      $plugin_type_data_array['discovery_type'] = 'attribute';

      $matches = [];
      preg_match("@Drupal\\\\{$extension->name}\\\\Attribute\\\\(\w+)@", $constructor_body, $matches);
    }
    elseif (str_contains($constructor_body, "Drupal\\{$extension->name}\\Annotation")) {
      $plugin_type_data_array['discovery_type'] = 'annotation';

      $matches = [];
      preg_match("@Plugin/([\w/]+)@", $constructor_body, $matches);
      $plugin_type_data_array['plugin_subdirectory'] = $matches['1'] ?? '';

      $matches = [];
      preg_match("@Drupal\\\\{$extension->name}\\\\Annotation\\\\([[\w\\]]+)@", $constructor_body, $matches);
      $annotation_class = $matches[0];
      $plugin_type_data_array['annotation_class'] = $matches['1'] ?? '';

      $extension->loadClass($annotation_class);
      $annotation_class_reflection = new \ReflectionClass($annotation_class);

      // Get the properties from the annotation.
      $annotation_property_relections = $annotation_class_reflection->getProperties();
      foreach ($annotation_property_relections as $property_reflection) {
        $name = $property_reflection->getName();

        // Skip the properties which are added automatically.
        if (in_array($name, ['id', 'label', 'description'])) {
          continue;
        }

        // Skip the 'definition' property from the parent class.
        if ($name == 'definition') {
          continue;
        }

        $property_data = [
          'name' => $name,
        ];

        // Get the type of the property, defaulting to 'string' if we can't.
        if ($property_reflection->hasType()) {
          $property_data['type'] = (string) $property_reflection->getType();
        }
        elseif ($docblock = $property_reflection->getDocComment()) {
          $matches = [];
          preg_match('/@var (\S+)$/m', $docblock, $matches);
          if (!isset($matches[1])) {
            $property_data['type'] = 'string';
            continue;
          }

          $type = $matches[1];

          if (str_ends_with($type, '[]')) {
            $type = 'array';
          }

          $property_data['type'] = $type;
        }
        else {
          // No sodding docs!
          $property_data['type'] = 'string';
        }
        $plugin_type_data_array['attribute_properties'][] = $property_data;
      }
    }
    else {
      // TODO!
      $plugin_type_data_array['discovery_type'] = 'yaml';
    }

    // TODO: Find an existing component and merge into it -- see Service
    // generator for example.

    // Bit of a WTF: this requires this generator class to know it's being used
    // as a multi-valued item in the Module generator.
    $item_data = $component_data->getItem($property_name)->createItem();
    $item_data->set($plugin_type_data_array);
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $plugin_type = $this->component_data['plugin_type'];

    // Experimental. This corresponds to a property defined in the main data.
    // This is needed so that the PluginTypeManager generator has access to
    // the whole data structure, in particular, to access generation
    // configuration.
    $components['manager'] = [
      'component_type' => 'PluginTypeManager',
      'use_data_definition' => TRUE,
      'service_name' => $this->component_data->plugin_manager_service_id->value,
      'service_name_prefix' => '',
      'plain_class_name' => CaseString::snake($this->component_data->plugin_type->value)->pascal() . 'Manager',
      'injected_services' => [],
      'docblock_first_line' => "Manages discovery and instantiation of {$this->component_data['plugin_label']} plugins.",
    ];

    if (in_array($this->component_data['discovery_type'], ['annotation', 'attribute'])) {
      // Annotation and attribute plugin managers inherit from
      // DefaultPluginManager.
      $components['manager']['parent'] = 'default_plugin_manager';
      // TODO: a service should be able to detect the parent class name from
      // service definitions.... if we had all of them.
      $components['manager']['parent_class_name'] = '\Drupal\Core\Plugin\DefaultPluginManager';
    }
    else {
      // YAML plugin managers need some services injecting.
      // TODO: move this to the manager?? But then we lose the handling in
      // the Service class.
      $components['manager']['injected_services'] = [
        'cache.discovery',
        'module_handler',
      ];
      // Don't inherit from the default plugin manager as a service, but do
      // inherit from it as a class. (See menu YAML plugins for example.)
      $components['manager']['parent_class_name'] = '\Drupal\Core\Plugin\DefaultPluginManager';
    }


    // TODO: remove the specialized PluginTypeManager generator, and instead
    // set a constructor method generator to be contained by a Service.
    /*
    $components['__construct'] = array(
      'component_type' => 'PHPFunction',
      'containing_component' => "%requester:manager",
      'doxygen_first' => "Constructs a new {$this->component_data['annotation_class']}Manager object.",
      'declaration' => 'public function __construct()',
      'body' => array(
        "return [];",
      ),
    );
    */

    if ($this->component_data['discovery_type'] == 'annotation') {
      $components['annotation'] = [
        'component_type' => 'AnnotationClass',
        'relative_class_name' => 'Annotation\\' . $this->component_data['annotation_class'],
        'parent_class_name' => '\Drupal\Component\Annotation\Plugin',
        'class_docblock_lines' => [
          "Defines the {$this->component_data['plugin_label']} plugin annotation object.",
          "Plugin namespace: {$this->component_data['plugin_relative_namespace']}.",
        ],
        // TODO: Some annotation properties such as ID and label.
      ];
    }
    if ($this->component_data['discovery_type'] == 'attribute') {
      $components['attribute'] = [
        'component_type' => 'AttributeClass',
        'relative_class_name' => 'Attribute\\' . $this->component_data->attribute_class->value,
        'parent_class_name' => '\Drupal\Component\Plugin\Attribute\Plugin',
        'class_docblock_lines' => [
          "Defines a {$this->component_data['plugin_label']} attribute object.",
          "Plugin namespace: {$this->component_data['plugin_relative_namespace']}.",
        ],
      ];

      $attribute_constructor_parameters = [
        [
          'name' => 'id',
          'description' => 'The plugin ID.',
          'typehint' => 'string',
          'visibility' => 'public',
          'readonly' => TRUE,
        ],
        [
          'visibility' => 'public',
          'description' => 'The plugin label.',
          'typehint' => '\Drupal\Core\StringTranslation\TranslatableMarkup',
          'readonly' => TRUE,
          'name' => 'label',
        ],
        [
          'name' => 'description',
          'visibility' => 'public',
          'description' => 'The plugin description.',
          'typehint' => '\Drupal\Core\StringTranslation\TranslatableMarkup',
          'readonly' => TRUE,
        ],
      ];
      foreach ($this->component_data->attribute_properties as $attribute_property) {
        $attribute_constructor_parameters[] = [
          'name' => $attribute_property->name->value,
          'description' => $attribute_property->description->value,
          'typehint' => $attribute_property->type->value,
          'visibility' => 'public',
          'readonly' => TRUE,
        ];
      }

      foreach ($attribute_constructor_parameters as &$parameter) {
        $parameter['property_assignment']['name'] = $parameter['name'];
      }

      $components['attribute_constructor'] = [
        'component_type' => 'PHPConstructor',
        'containing_component' => '%requester:attribute',
        'function_docblock_lines' => ["Constructs a {$this->component_data->attribute_class->value} attribute."],
        // We want the __construct() method declaration's parameters to be
        // broken over multiple lines for legibility.
        // This is a Drupal coding standard still under discussion: see
        // https://www.drupal.org/node/1539712.
        'break_declaration' => TRUE,
        'parameters' => $attribute_constructor_parameters,
        'promote_properties' => TRUE,
      ];
    }

    $plugin_relative_namespace_pieces = explode('\\', $this->component_data['plugin_relative_namespace']);

    $components['interface'] = [
      'component_type' => 'PHPInterfaceFile',
      'plain_class_name' => $this->component_data->plugin_plain_class_name->value . 'Interface',
      'relative_namespace' => 'Plugin\\' . $this->component_data['plugin_relative_namespace'],
      'docblock_first_line' => "Interface for {$this->component_data['plugin_label']} plugins.",
      'parent_interface_names' => [
        // The interfaces that PluginBase implements, since we use that as the
        // parent of the generated plugin base class.
        '\Drupal\Component\Plugin\PluginInspectionInterface',
        '\Drupal\Component\Plugin\DerivativeInspectionInterface',
      ]
    ];

    $components['base_class'] = [
      'component_type' => 'PluginClassBase',
      'plain_class_name' => $this->component_data['base_class_short_name'],
      'relative_namespace' => 'Plugin\\' . $this->component_data['plugin_relative_namespace'],
      'parent_class_name' => '\Drupal\Component\Plugin\PluginBase',
      'interfaces' => [$this->component_data->interface->value],
      'injected_services' => $this->component_data->base_class_injected_services->values(),
      'use_static_factory_method' => TRUE,
      // Abstract for annotation or attribute plugins, where each plugin
      // provides a class; for YAML plugins, each plugin will typically just use
      // this class.
      'abstract'=> (in_array($this->component_data['discovery_type'], ['annotation', 'attribute'])),
      'docblock_first_line' => "Base class for {$this->component_data['plugin_label']} plugins.",
    ];

    $module = $this->component_data['root_component_name'];
    $components['plugin_type_yml'] = [
      'component_type' => 'YMLFile',
      'filename' => '%module.plugin_type.yml',
      'yaml_data' => [
        "{$module}.{$plugin_type}"=> [
          'label' => $this->component_data['plugin_label'],
          'plugin_manager_service_id' => $this->component_data['plugin_manager_service_id'],
          'plugin_definition_decorator_class' => 'Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator',
        ],
      ],
    ];

    // Request this even though the Module generator may have done, so we ensure
    // it is there.
    $components['api'] = [
      'component_type' => 'API',
    ];

     $components['alter_hook'] = [
      'component_type' => 'PHPFunction',
      'function_name' => "hook_{$this->component_data['info_alter_hook']}_alter",
      'containing_component' => '%requester:api',
      'function_docblock_lines' => [
        "Perform alterations on {$this->component_data['plugin_label']} definitions.",
      ],
      'parameters' => [
        [
          'name' => 'info',
          'by_reference' => TRUE,
          'typehint' => 'array',
          'description' => "Array of information on {$this->component_data['plugin_label']} plugins.",
        ],
      ],
      'return' => [
        'omit_return_tag' => TRUE,
      ],
      'body' => [
        "// Change the class of the 'foo' plugin.",
        "£info['foo']['class'] = SomeOtherClass::class;",
      ],
    ];

    return $components;
  }

}
