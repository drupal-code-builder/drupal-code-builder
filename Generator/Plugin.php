<?php

namespace DrupalCodeBuilder\Generator;

use \DrupalCodeBuilder\Exception\InvalidInputException;
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
   * The unique name of this generator.
   */
  public $name;

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
  function __construct($component_name, $component_data, $root_generator) {
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

    parent::__construct($component_name, $component_data, $root_generator);
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

          $component_data['plugin_name'] = $component_data['root_component_name'] . '_' . $component_data['plugin_name'];
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
    );

    // Put the parent definitions after ours.
    $data_definition += parent::componentDataDefinition();

    return $data_definition;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    foreach ($this->component_data['injected_services'] as $service_id) {
      $components[$this->name . '_' . $service_id] = array(
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

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function buildComponentContents($children_contents) {
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
    $docblock_code = [];

    $annotation_class_path = explode('\\', $this->component_data['plugin_type_data']['plugin_definition_annotation_name']);
    $annotation_class = array_pop($annotation_class_path);

    // Special case: annotation that's just the plugin ID.
    if (!empty($this->component_data['plugin_type_data']['annotation_id_only'])) {
      $docblock_code[] = '@'
        . $annotation_class
        . '("'
        . $this->component_data['plugin_name']
        . '")';

      return $docblock_code;
    }

    $annotation_variables = $this->component_data['plugin_type_data']['plugin_properties'];

    $docblock_code[] = '@' . $annotation_class . '(';

    foreach ($annotation_variables as $annotation_variable => $annotation_variable_info) {
      if ($annotation_variable == 'id') {
        $docblock_code[] = '  ' . $annotation_variable . ' = "' . $this->component_data['plugin_name'] . '",';
        continue;
      }

      if ($annotation_variable_info['type'] == '\Drupal\Core\Annotation\Translation') {
        // The annotation property value is translated.
        $docblock_code[] = '  ' . $annotation_variable . ' = @Translation("TODO: replace this with a value"),';
        continue;
      }

      // It's a plain string.
      $docblock_code[] = '  ' . $annotation_variable . ' = "TODO: replace this with a value",';
    }
    $docblock_code[] = ')';

    return $docblock_code;
  }

  /**
   * Produces the class declaration.
   */
  function class_declaration() {
    if (isset($this->component_data['plugin_type_data']['base_class'])) {
      $this->component_data['parent_class_name'] = '\\' . $this->component_data['plugin_type_data']['base_class'];
    }

    if (!empty($this->injectedServices)) {
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
    // The parameters for the base class's constructor.
    if (isset($this->component_data['plugin_type_data']['construction'])) {
      foreach ($this->component_data['plugin_type_data']['construction'] as $construction_item) {
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
