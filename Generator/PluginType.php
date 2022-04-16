<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\OptionDefinition;
use DrupalCodeBuilder\Definition\LazyGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\GeneratorDefinition;
use MutableTypedData\Definition\VariantDefinition;

/**
 * Generator for a plugin type.
 */
class PluginType extends BaseGenerator {

  use NameFormattingTrait;

  /**
   * {@inheritdoc}
   */
  protected static $dataType = 'mutable';

  public static function setProperties(PropertyDefinition $definition): void {
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
              "Each plugins is a class with an annotation to declare the plugin data."
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

          // Append 'Base' to the base class name for annotation plugins, where
          // the base class is actually a base class, but not for YAML plugins,
          // where the base class really is the class that's mostly used for
          // all plugins.
          if ($component_data->getParent()->discovery_type->value == 'annotation') {
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
      'manager' => static::getLazyDataDefinitionForGeneratorType('PluginTypeManager')
        ->setInternal(TRUE),
    ];

    foreach ($definition->getVariants() as $variant) {
      $variant->addProperties($common_properties);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $plugin_type = $this->component_data['plugin_type'];

    // Experimental. This corresponse to a property defined in the main data.
    // This is needed so that the PluginTypeManager generator has access to
    // the whole data structure, in particular, to access generation
    // configuration.
    $components['manager'] = [
      'component_type' => 'PluginTypeManager',
      'use_data_definition' => TRUE,
      'prefixed_service_name' => $this->component_data->plugin_manager_service_id->value,
      // Use the annotation class name as the basis for the manager class name.
      'plain_class_name' => CaseString::snake($this->component_data->plugin_type->value)->pascal() . 'Manager',
      'injected_services' => [],
      'docblock_first_line' => "Manages discovery and instantiation of {$this->component_data['plugin_label']} plugins.",
    ];

    if ($this->component_data['discovery_type'] == 'annotation') {
      // Annotation plugin managers inherit from DefaultPluginManager.
      $components['manager']['parent'] = 'default_plugin_manager';
      // TODO: a service should be able to detect the parent class name from
      // service definitions.... if we had all of them.
      $components['manager']['parent_class_name'] = '\Drupal\Core\Plugin\DefaultPluginManager';
    }
    else {
      // YAML plugin managers need some services injecting.
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

    $plugin_relative_namespace_pieces = explode('\\', $this->component_data['plugin_relative_namespace']);

    $components['interface'] = [
      'component_type' => 'PHPInterfaceFile',
      'plain_class_name' => $this->component_data->plugin_plain_class_name->value . 'Interface',
      'relative_namespace' => 'Plugin\\' . $this->component_data['plugin_relative_namespace'],
      'docblock_first_line' => "Interface for {$this->component_data['plugin_label']} plugins.",
      // TODO: parent interfaces.
    ];

    $components['base_class'] = [
      'component_type' => 'PHPClassFile',
      'plain_class_name' => $this->component_data['base_class_short_name'],
      'relative_namespace' => 'Plugin\\' . $this->component_data['plugin_relative_namespace'],
      'parent_class_name' => '\Drupal\Component\Plugin\PluginBase',
      'interfaces' => [
        $this->component_data['interface'],
      ],
      // Abstract for annotation plugins, where each plugin provides a class;
      // for YAML plugins, each plugin will typically just use this class.
      'abstract'=> ($this->component_data['discovery_type'] == 'annotation'),
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
      'declaration' => "function hook_{$this->component_data['info_alter_hook']}_alter(array &£info)",
      'function_docblock_lines' => [
        "Perform alterations on {$this->component_data['plugin_label']} definitions.",
        '@param array $info',
        "  Array of information on {$this->component_data['plugin_label']} plugins.",
      ],
      'body' => [
        "// Change the class of the 'foo' plugin.",
        "£info['foo']['class'] = SomeOtherClass::class;",
      ],
    ];

    return $components;
  }

}
