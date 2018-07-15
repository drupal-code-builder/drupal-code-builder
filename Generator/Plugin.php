<?php

namespace DrupalCodeBuilder\Generator;

use \DrupalCodeBuilder\Exception\InvalidInputException;
use DrupalCodeBuilder\Generator\Render\ClassAnnotation;
use CaseConverter\CaseString;

/**
 * Generator for a plugin.
 */
class Plugin extends PHPClassFileWithInjection {

  use PluginTrait;

  /**
   * {@inheritdoc}
   */
  protected $hasStaticFactoryMethod = TRUE;

  /**
   * The plugin discovery type used by the plugins this generates.
   *
   * @var string
   */
  protected static $discoveryType = 'AnnotatedClassDiscovery';

  /**
   * The standard fixed create() parameters.
   *
   * These are the parameters to create() that come after the $container
   * parameter.
   *
   * @var array
   */
  const STANDARD_FIXED_PARAMS = [
    [
      'name' => 'configuration',
      'description' => 'A configuration array containing information about the plugin instance.',
      'typehint' => 'array',
    ],
    [
      'name' => 'plugin_id',
      'description' => 'The plugin_id for the plugin instance.',
      'typehint' => 'string',
    ],
    [
      'name' => 'plugin_definition',
      'description' => 'The plugin implementation definition.',
      'typehint' => 'mixed',
    ]
  ];

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the component. Any missing properties
   *   (or all if this is entirely omitted) are given default values.
   *
   * @throws \DrupalCodeBuilder\Exception\InvalidInputException
   */
  function __construct($component_data) {
    // Set some default properties.
    $component_data += array(
      'injected_services' => [],
    );

    $plugin_type = $component_data['plugin_type'];

    $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $plugin_types_data = $mb_task_handler_report_plugins->listPluginData();

    // The plugin type has already been validated by the plugin_type property's
    // processing.
    $component_data['plugin_type_data'] = $plugin_types_data[$plugin_type];

    parent::__construct($component_data);
  }

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    $data_definition = array(
      'plugin_type' => static::getPluginTypePropertyDefinition(),
      'plugin_name' => array(
        'label' => 'Plugin ID',
        'description' => 'The module name will be prepended, unless the ID is a derivative.',
        'required' => TRUE,
        'default' => function($component_data) {
          // Skip this for non-interactive UIs.
          if (empty($component_data['plugin_type'])) {
            return;
          }

          // Keep a running count of the plugins of each type, so we can offer
          // a default in the form 'block_one', 'block_two'.
          $plugin_type = $component_data['plugin_type'];
          static $counters;
          if (!isset($counters[$plugin_type])) {
            $counters[$plugin_type] = 0;
          }
          $counters[$plugin_type]++;

          $formatter = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);

          return $component_data['plugin_type'] . '_' . $formatter->format($counters[$plugin_type]);
        },
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          // Prepend the module name.
          if (strpos($value, ':') !== FALSE) {
            // Don't if the plugin ID is a derivative.
            return;
          }

          $module_name = $component_data['root_component_name'];

          if (strpos($value, $module_name . '_') === 0) {
            // Don't if the plugin ID already has the module name as a prefix.
            return;
          }

          $component_data['plugin_name'] = $module_name . '_' . $component_data['plugin_name'];
        },
      ),
      'injected_services' => array(
        'label' => 'Injected services',
        'format' => 'array',
        'options' => function(&$property_info) {
          $mb_task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');

          $options = $mb_task_handler_report_services->listServiceNamesOptions();

          return $options;
        },
        // TODO: allow this to be a callback.
        'options_extra' => \DrupalCodeBuilder\Factory::getTask('ReportServiceData')->listServiceNamesOptionsAll(),
      ),
      'parent_plugin_id' => [
        'label' => 'Parent class plugin ID',
        'description' => "Use another plugin's class as the parent class for this plugin.",
        'validation' => function($property_name, $property_info, $component_data) {
          if (!empty($component_data['parent_plugin_id'])) {
            $plugin_type = $component_data['plugin_type'];

            $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
            $plugin_types_data = $mb_task_handler_report_plugins->listPluginData();

            // The plugin type has already been validated by the plugin_type property's
            // processing.
            $plugin_service_id = $plugin_types_data[$plugin_type]['service_id'];

            // TODO: go via the environment for testing!
            try {
              $plugin_definition = \Drupal::service($plugin_service_id)->getDefinition($component_data['parent_plugin_id']);
            }
            catch (\Drupal\Component\Plugin\Exception\PluginNotFoundException $plugin_exception) {
              return ["There is no plugin '@plugin-id' of type '@plugin-type'.", [
                '@plugin-id' => $component_data['parent_plugin_id'],
                '@plugin-type' => $component_data['plugin_type']
              ]];
            }
          }
        },
      ],
      'replace_parent_plugin' => [
        'label' => 'Replace parent plugin',
        'description' => "Replace the parent plugin's class with the generated class, rather than create a new plugin. The plugin ID value will be use to form the class name.",
        'format' => 'boolean',
      ],
      'parent_plugin_class' => [
        'computed' => TRUE,
        'default' => function($component_data) {
          if (!empty($component_data['parent_plugin_id'])) {
            $plugin_type = $component_data['plugin_type'];

            $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
            $plugin_types_data = $mb_task_handler_report_plugins->listPluginData();

            // The plugin type has already been validated by the plugin_type property's
            // processing.
            $plugin_service_id = $plugin_types_data[$plugin_type]['service_id'];

            // TODO: go via the environment for testing!
            try {
              $plugin_definition = \Drupal::service($plugin_service_id)->getDefinition($component_data['parent_plugin_id']);
            }
            catch (\Drupal\Component\Plugin\Exception\PluginNotFoundException $plugin_exception) {
              // Rethrow as something that UIs will catch.
              throw new InvalidInputException($plugin_exception->getMessage());
            }

            return $plugin_definition['class'];
          }
        },
      ],
    );

    // Put the parent definitions after ours.
    $data_definition += parent::componentDataDefinition();

    $data_definition['class_docblock_lines']['default'] = function($component_data) {
      if (!empty($component_data['replace_parent_plugin'])) {
        return [
          "Overrides the '{$component_data['parent_plugin_id']}' plugin class.",
        ];
      }
    };


    return $data_definition;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    foreach ($this->component_data['injected_services'] as $service_id) {
      $components['service_' . $service_id] = array(
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
      );
    }

    if (!empty($this->component_data['plugin_type_data']['config_schema_prefix'])) {
      $schema_id = $this->component_data['plugin_type_data']['config_schema_prefix']
        . $this->component_data['plugin_name'];

      $components["config/schema/%module.schema.yml"] = [
        'component_type' => 'ConfigSchema',
        'yaml_data' => [
           $schema_id => [
            'type' => 'mapping',
            'label' => $this->component_data['plugin_name'],
            'mapping' => [

            ],
          ],
        ],
      ];
    }

    if (!empty($this->component_data['replace_parent_plugin'])) {
      if (!empty($this->component_data['plugin_type_data']['alter_hook_name'])) {
        $alter_hook_name = 'hook_' . $this->component_data['plugin_type_data']['alter_hook_name'];

        $components['hooks'] = [
          'component_type' => 'Hooks',
          'hooks' => [
            $alter_hook_name => TRUE,
          ],
          'hook_bodies' => [
            $alter_hook_name => [
              "// Override the class for the '{$this->component_data['parent_plugin_id']}' plugin.",
              "if (isset(£info['{$this->component_data['parent_plugin_id']}'])) {",
              "  £info['{$this->component_data['parent_plugin_id']}']['class'] = \\{$this->component_data['qualified_class_name']}::class;",
              "}",
            ],
          ],
        ];
      }
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // TEMPORARY, until Generate task handles returned contents.
    $this->injectedServices = $this->filterComponentContentsForRole($children_contents, 'service');

    $this->childContentsGrouped = $this->groupComponentContentsByRole($children_contents);

    return array();
  }

  /**
   * Procudes the docblock for the class.
   */
  protected function getClassDocBlockLines() {
    $docblock_lines = parent::getClassDocBlockLines();

    // Do not include the annotation if this plugin is a class override.
    if (!empty($this->component_data['replace_parent_plugin'])) {
      return $docblock_lines;
    }

    $docblock_lines[] = '';

    $docblock_lines = array_merge($docblock_lines, $this->classAnnotation());

    return $docblock_lines;
  }

  /**
   * Produces the plugin class annotation lines.
   *
   * @return
   *   An array of lines suitable for docBlock().
   */
  function classAnnotation() {
    $annotation_class_path = explode('\\', $this->component_data['plugin_type_data']['plugin_definition_annotation_name']);
    $annotation_class = array_pop($annotation_class_path);

    // Special case: annotation that's just the plugin ID.
    if (!empty($this->component_data['plugin_type_data']['annotation_id_only'])) {
      $annotation = ClassAnnotation::{$annotation_class}($this->component_data['plugin_name']);

      $annotation_lines = $annotation->render();

      return $annotation_lines;
    }

    $annotation_variables = $this->component_data['plugin_type_data']['plugin_properties'];

    $annotation_data = [];
    foreach ($annotation_variables as $annotation_variable => $annotation_variable_info) {
      if ($annotation_variable == 'id') {
        $annotation_data['id'] = $this->component_data['plugin_name'];
        continue;
      }

      // Hacky workaround for https://github.com/drupal-code-builder/drupal-code-builder/issues/97.
      if (isset($annotation_variable_info['type']) && $annotation_variable_info['type'] == '\Drupal\Core\Annotation\Translation') {
        // The annotation property value is translated.
        $annotation_data[$annotation_variable] = ClassAnnotation::Translation("TODO: replace this with a value");
        continue;
      }

      // It's a plain string.
      $annotation_data[$annotation_variable] = "TODO: replace this with a value";
    }

    $annotation = ClassAnnotation::{$annotation_class}($annotation_data);
    $annotation_lines = $annotation->render();

    return $annotation_lines;
  }

  /**
   * Produces the class declaration.
   */
  function class_declaration() {
    if (isset($this->component_data['parent_plugin_class'])) {
      $this->component_data['parent_class_name'] = '\\' . $this->component_data['parent_plugin_class'];
    }
    elseif (isset($this->component_data['plugin_type_data']['base_class'])) {
      $this->component_data['parent_class_name'] = '\\' . $this->component_data['plugin_type_data']['base_class'];
    }

    // Set the DI interface if needed.
    $use_di_interface = FALSE;
    // We need the DI interface if this class injects services, unless a parent
    // class also does so.
    if (!empty($this->injectedServices)) {
      $use_di_interface = TRUE;

      if (isset($this->component_data['parent_plugin_class'])) {
        // TODO: violates DRY; we call this twice.
        $parent_construction_parameters = \DrupalCodeBuilder\Utility\CodeAnalysis\DependencyInjection::getInjectedParameters($this->component_data['parent_plugin_class'], 3);
        if (!empty($parent_construction_parameters)) {
          $use_di_interface = FALSE;
        }
      }
      elseif (!empty($this->component_data['plugin_type_data']['construction'])) {
        $use_di_interface = FALSE;
      }
    }

    if ($use_di_interface) {
      $this->component_data['interfaces'][] = '\Drupal\Core\Plugin\ContainerFactoryPluginInterface';
    }

    return parent::class_declaration();
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    $this->collectSectionBlocksForDependencyInjection();

    // TODO: move this to a component.
    $this->createBlocksFromMethodData($this->component_data['plugin_type_data']['plugin_interface_methods']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getConstructBaseParameters() {
    if (isset($this->component_data['plugin_type_data']['constructor_fixed_parameters'])) {
      // Plugin type has non-standard constructor fixed parameters.
      // Argh, the data for this is in a different format: type / typehint.
      // TODO: clean up this WTF.
      $parameters = [];
      foreach ($this->component_data['plugin_type_data']['constructor_fixed_parameters'] as $i => $param) {
        $typehint = $param['type'];
        if (!empty($typehint) && !in_array($typehint, ['array', 'string', 'bool', 'mixed', 'int'])) {
          // Class typehints need an initial '\'.
          // TODO: clean up and standardize.
          $typehint = '\\' . $typehint;
        }

        $parameters[$i] = [
          'name' => $param['name'],
          // buildMethodHeader() will fill in a description.
          // TODO: get this from the docblock in analysis.
          'description' => '',
          'typehint' => $typehint,
          'extraction' => $param['extraction'],
        ];
      }
    }
    else {
      // Plugin type has standard fixed parameters.
      $parameters = self::STANDARD_FIXED_PARAMS;
    }

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateParameters() {
    return self::STANDARD_FIXED_PARAMS;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConstructParentInjectedServices() {
    $parameters = [];

    if (isset($this->component_data['parent_plugin_class'])) {
      $parent_construction_parameters = \DrupalCodeBuilder\Utility\CodeAnalysis\DependencyInjection::getInjectedParameters($this->component_data['parent_plugin_class'], 3);
    }
    elseif (isset($this->component_data['plugin_type_data']['construction'])) {
      $parent_construction_parameters = $this->component_data['plugin_type_data']['construction'];
    }

    // The parameters for the base class's constructor.
    if (!empty($parent_construction_parameters)) {
      foreach ($parent_construction_parameters as $construction_item) {
        $parameters[] = [
          'name' => $construction_item['name'],
          'description' => 'The ' . strtr($construction_item['name'], '_', ' ')  . '.',
          'typehint' => '\\' . $construction_item['type'],
          'extraction' => $construction_item['extraction'],
        ];
      }
    }
    return $parameters;
  }

}
