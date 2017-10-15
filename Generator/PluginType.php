<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a plugin type.
 */
class PluginType extends BaseGenerator {

  use NameFormattingTrait;

  /**
   * The unique name of this generator.
   */
  public $name;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return array(
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
          return self::snakeToTitle($plugin_type);
        },
      ],
      'plugin_subdirectory' => array(
        'label' => 'Plugin subdirectory within the Plugins directory',
        'required' => TRUE,
        'default' => function($component_data) {
          $plugin_type = $component_data['plugin_type'];

          // Convert the plugin type to camel case. E.g., 'my_plugin' becomes
          // 'MyPlugin'.
          return self::snakeToCamel($plugin_type);
        },
        'process_default' => TRUE,
      ),
      'annotation_class' => array(
        'label' => 'Annotation class name',
        'required' => TRUE,
        'default' => function($component_data) {
          $plugin_type = $component_data['plugin_type'];

          // Convert the plugin type to camel case. E.g., 'my_plugin' becomes
          // 'MyPlugin'.
          return self::snakeToCamel($plugin_type);
        },
        'process_default' => TRUE,
      ),
      'interface' => array(
        'label' => 'Interface',
        'computed' => TRUE,
        'default' => function($component_data) {
          return '\\' . self::makeQualifiedClassName([
            'Drupal',
            $component_data['root_component_name'],
            'Plugin',
            // TODO: won't work if plugin subdir has nesting.
            $component_data['plugin_subdirectory'],
            $component_data['annotation_class'] . 'Interface',
          ]);
        },
      ),
      'info_alter_hook' => [
        'label' => 'Alter hook name',
        'description' => "The name of the hook used to alter plugin info, without the 'hook_' prefix.",
        'required' => TRUE,
        'default' => function($component_data) {
          $plugin_type = $component_data['plugin_type'];
          return "{$component_data['plugin_type']}_info";
        },
        'process_default' => TRUE,
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    $plugin_type = $this->component_data['plugin_type'];

    $components["plugin_type_{$plugin_type}_service"] = array(
      'component_type' => 'PluginTypeManager',
      // Namespace the service name after the prefix.
      'prefixed_service_name' => 'plugin.manager.' . $this->component_data['root_component_name'] . '_' . $this->component_data['plugin_type'],
      // Use the annotation class name as the basis for the manager class name.
      'relative_class_name' => [$this->component_data['annotation_class'] . 'Manager'],
      'injected_services' => [],
      'docblock_first_line' => "Manages discovery and instantiation of {$this->component_data['plugin_label']} plugins.",
      'parent' => 'default_plugin_manager',
      // TODO: a service should be able to detect the parent class name from
      // service definitions.... if we had all of them.
      // TODO: passing all these in is tedious.
      // We want the collector to magically pass these in?
      'parent_class_name' => '\Drupal\Core\Plugin\DefaultPluginManager',
      'plugin_type' => $this->component_data['plugin_type'],
      'plugin_subdirectory' => $this->component_data['plugin_subdirectory'],
      'annotation_class' => $this->component_data['annotation_class'],
      'info_alter_hook' => $this->component_data['info_alter_hook'],
      'interface' => $this->component_data['interface'],
    );

    // TODO: remove the specialized PluginTypeManager generator, and instead
    // set a constructor method generator to be contained by a Service.
    /*
    $components['__construct'] = array(
      'component_type' => 'PHPMethod',
      'code_file' => $this->component_data['annotation_class'] . 'Manager',
      // TODO: brittle. Find a better way!
      // Use references to the array?
      'code_file_id' => "PluginTypeManager:" . "plugin_type_{$plugin_type}_service",
      'doxygen_first' => "Constructs a new {$this->component_data['annotation_class']}Manager object.",
      'declaration' => 'public function __construct()',
      'body' => array(
        "return [];",
      ),
    );
    */

    $components["plugin_type_{$plugin_type}_annotation"] = [
      'component_type' => 'AnnotationClass',
      'relative_class_name' => ['Annotation', $this->component_data['annotation_class']],
      'parent_class_name' => '\Drupal\Component\Annotation\Plugin',
      'docblock_first_line' => "Defines the {$this->component_data['plugin_label']} plugin annotation object.",
      // TODO: Some annotation properties such as ID and label.
    ];

    $components["plugin_type_{$plugin_type}_interface"] = [
      'component_type' => 'PHPInterfaceFile',
      'relative_class_name' => [
        'Plugin',
        $this->component_data['plugin_subdirectory'],
        $this->component_data['annotation_class'] . 'Interface',
      ],
      'docblock_first_line' => "Interface for {$this->component_data['plugin_label']} plugins.",
      // TODO: parent interfaces.
    ];

    $components["plugin_type_{$plugin_type}_base_class"] = [
      'component_type' => 'PHPClassFile',
      'relative_class_name' => [
        'Plugin',
        $this->component_data['plugin_subdirectory'],
        $this->component_data['annotation_class'] . 'Base',
      ],
      'parent_class_name' => '\Drupal\Component\Plugin\PluginBase',
      'interfaces' => [
        $this->component_data['interface'],
      ],
      'abstract'=> TRUE,
      'docblock_first_line' => "Base class for {$this->component_data['plugin_label']} plugins.",
    ];

    $components["%module.plugin_type.yml"] = [
      'component_type' => 'YMLFile',
      'yaml_data' => [
        "%module.{$plugin_type}"=> [
          'label' => $this->component_data['plugin_label'],
          'provider' => '%module',
          'plugin_manager_service_id' => "plugin.manager.{$plugin_type}",
          'plugin_definition_decorator_class' => 'Drupal\plugin\PluginDefinition\ArrayPluginDefinitionDecorator',
        ],
      ],
    ];

    // TODO: api.php file for our info alter hook.

    return $components;
  }

}
