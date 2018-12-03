<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;

/**
 * Generator for a plugin type.
 */
class PluginType extends BaseGenerator {

  use NameFormattingTrait;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'discovery_type' => [
        'label' => 'Plugin discovery type',
        'description' => "The way in which plugins of this type are formed.",
        'options' => [
          'annotation' => 'Annotation: Plugins are classes with an annotation',
          'yaml' => 'YAML: Plugins are declared in a single YAML file, usually sharing the same class',
        ],
        'default' => 'annotation',
        'required' => TRUE,
      ],
      'plugin_type' => array(
        'label' => 'Plugin type ID',
        'description' => "The identifier of the plugin type. This is used to form the name of the manager service by prepending 'plugin.manager.'.",
        'required' => TRUE,
      ),
      'plugin_label' => [
        'label' => 'Plugin label',
        'description' => "The human-readable label for plugins of this type. This is used in documentation text.",
        'process_default' => TRUE,
        'default' => function($component_data) {
          $plugin_type = $component_data['plugin_type'];

          // Convert the plugin type to camel case. E.g., 'my_plugin' becomes
          // 'My Plugin'.
          return CaseString::snake($plugin_type)->title();
        },
      ],
      'plugin_subdirectory' => array(
        'label' => 'Plugin subdirectory within the Plugins directory',
        'required' => TRUE,
        'default' => function($component_data) {
          $plugin_type = $component_data['plugin_type'];

          // Convert the plugin type to camel case. E.g., 'my_plugin' becomes
          // 'MyPlugin'.
          return CaseString::snake($plugin_type)->pascal();
        },
        'process_default' => TRUE,
      ),
      'plugin_relative_namespace' => [
        'label' => 'Plugin relative namespace',
        'computed' => TRUE,
        'default' => function($component_data) {
          // The plugin subdirectory may be nested.
          return str_replace('/', '\\', $component_data['plugin_subdirectory']);
        },
      ],
      'annotation_class' => array(
        'label' => 'Annotation class name. (Only used for annotation discovery plugins.)',
        'required' => TRUE,
        'default' => function($component_data) {
          $plugin_type = $component_data['plugin_type'];

          // Convert the plugin type to camel case. E.g., 'my_plugin' becomes
          // 'MyPlugin'.
          return CaseString::snake($plugin_type)->pascal();
        },
        'process_default' => TRUE,
      ),
      'interface' => array(
        'label' => 'Interface',
        'computed' => TRUE,
        'default' => function($component_data) {
          return '\\' . self::makeQualifiedClassName([
            'Drupal',
            '%module',
            'Plugin',
            $component_data['plugin_relative_namespace'],
            $component_data['annotation_class'] . 'Interface',
          ]);
        },
      ),
      'base_class' => [
        'label' => 'Base class',
        'computed' => TRUE,
        'default' => function($component_data) {
          return '\\' . self::makeQualifiedClassName([
            'Drupal',
            '%module',
            'Plugin',
            $component_data['plugin_relative_namespace'],
            $component_data['annotation_class'] . 'Base',
          ]);
        },
      ],
      'plugin_manager_service_id' => [
        'computed' => TRUE,
        'default' => function($component_data) {
          // Namespace the service name after the prefix.
          return 'plugin.manager.'
            . $component_data['root_component_name']
            . '_'
            . $component_data['plugin_type'];
        },
      ],
      'info_alter_hook' => [
        'label' => 'Alter hook name',
        'description' => "The name of the hook used to alter plugin info, without the 'hook_' prefix.",
        'required' => TRUE,
        'default' => function($component_data) {
          // Skip this for non-interactive UIs.
          if (empty($component_data['plugin_type'])) {
            return;
          }

          $plugin_type = $component_data['plugin_type'];
          return "{$component_data['plugin_type']}_info";
        },
        'process_default' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    $plugin_type = $this->component_data['plugin_type'];

    $components['manager'] = array(
      'component_type' => 'PluginTypeManager',
      'prefixed_service_name' => $this->component_data['plugin_manager_service_id'],
      // Use the annotation class name as the basis for the manager class name.
      'service_class_name' => $this->component_data['annotation_class'] . 'Manager',
      'injected_services' => [],
      'docblock_first_line' => "Manages discovery and instantiation of {$this->component_data['plugin_label']} plugins.",
    );

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
        'relative_class_name' => ['Annotation', $this->component_data['annotation_class']],
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
      'relative_class_name' => array_merge(
        ['Plugin'],
        $plugin_relative_namespace_pieces,
        [$this->component_data['annotation_class'] . 'Interface']
      ),
      'docblock_first_line' => "Interface for {$this->component_data['plugin_label']} plugins.",
      // TODO: parent interfaces.
    ];

    $components['base_class'] = [
      'component_type' => 'PHPClassFile',
      'relative_class_name' => array_merge(
        ['Plugin'],
        $plugin_relative_namespace_pieces,
        [$this->component_data['annotation_class'] . 'Base']
      ),
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
