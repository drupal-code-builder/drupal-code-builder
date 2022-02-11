<?php

namespace DrupalCodeBuilder\Generator;

use \DrupalCodeBuilder\Exception\InvalidInputException;
use DrupalCodeBuilder\Generator\Render\ClassAnnotation;
use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\VariantGeneratorDefinition;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for an annotation plugin.
 *
 * This is a variant generator for the Plugin generator, and should not be
 * used directly.
 */
class PluginAnnotationDiscovery extends PHPClassFileWithInjection {

  /**
   * {@inheritdoc}
   */
  protected $hasStaticFactoryMethod = TRUE;

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

  function __construct($component_data) {
    // Set some default properties.
    // $component_data += array(
    //   'injected_services' => [],
    // );

    $plugin_type = $component_data->plugin_type->value;

    $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $plugin_types_data = $mb_task_handler_report_plugins->listPluginData();

    // The plugin type has already been validated by the plugin_type property's
    // processing.
    $this->plugin_type_data = $plugin_types_data[$plugin_type];

    parent::__construct($component_data);
  }

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $plugin_data_task = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $services_data_task = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');

    $definition->getProperty('relative_class_name')->setInternal(TRUE);

    $definition->addProperties([
      'plugin_type_data' => PropertyDefinition::create('mapping')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setCallable([static::class, 'defaultPluginTypeData'])
        ),
      'plugin_name' => PropertyDefinition::create('string')
        ->setLabel('Plugin ID')
        ->setRequired(TRUE)
        ->setValidators('plugin_name'),
      'prefixed_plugin_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setCallable([static::class, 'processingPluginName'])
            ->setDependencies('..:plugin_name')
        ),
      'plugin_label' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setExpression("machineToLabel(stripBefore(get('..:plugin_name'), ':'))")
          ->setDependencies('..:plugin_name')
        ),
      'plain_class_name' => PropertyDefinition::create('string')
        ->setLabel('Plugin class name')
        ->setRequired(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setExpression("machineToClass(stripBefore(get('..:plugin_name'), ':'))")
          ->setDependencies('..:plugin_name')
        )
        ->setValidators('class_name'),
      'relative_namespace' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setCallable([static::class, 'defaultRelativeNamespace'])
        ),
      'injected_services' => PropertyDefinition::create('string')
        ->setLabel('Injected services')
        ->setDescription("Services to inject. Additionally, use 'storage:TYPE' to inject entity storage handlers.")
        ->setMultiple(TRUE)
        ->setOptionsProvider($services_data_task),
      'parent_plugin_id' => PropertyDefinition::create('string')
        ->setLabel('Parent class plugin ID')
        ->setDescription("Use another plugin's class as the parent class for this plugin.")
        ->setValidators('plugin_exists'),
      'parent_plugin_class' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setCallable([static::class, 'defaultParentPluginClass'])
            ->setDependencies('..:parent_plugin_id')
        ),
      'replace_parent_plugin' => PropertyDefinition::create('boolean')
        ->setLabel('Replace parent plugin')
        ->setDescription("Replace the parent plugin's class with the generated class, rather than define a new plugin."),
      'class_docblock_lines' => PropertyDefinition::create('mapping')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setLiteral(['TODO: class docs.'])
        ),
    ]);

    return $definition;
  }

  public static function defaultPluginTypeData($data_item) {
    $plugin_type = $data_item->getParent()->plugin_type->value;

    $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $plugin_types_data = $mb_task_handler_report_plugins->listPluginData();

    return $plugin_types_data[$plugin_type];
  }

  public static function defaultRelativeNamespace($data_item) {
    $subdir = $data_item->getParent()->plugin_type_data->value['subdir'];
    return implode('\\', self::pathToNamespacePieces($subdir));
  }

  /**
   * Default value callback.
   */
  public static function defaultParentPluginClass($data_item) {
    $plugin_type_manager_service_id = $data_item->getParent()->plugin_type_data->value['service_id'];
    $plugin_type_manager_service = \DrupalCodeBuilder\Factory::getEnvironment()->getContainer()->get($plugin_type_manager_service_id);

    // Validation should already have checked this, no need to catch an
    // exception.
    $plugin_definition = $plugin_type_manager_service->getDefinition($data_item->getParent()->parent_plugin_id->value);

    return $plugin_definition['class'];
  }

  /**
   * TODO: is there a core function for this?
   */
  static function pathToNamespacePieces($path) {
    return explode('/', $path);
  }

  /**
   * Default callback.
   */
  public static function processingPluginName($data_item) {
    $plugin_name = $data_item->getParent()->plugin_name->value;

    if (strpos($plugin_name, ':') !== FALSE) {
      // Don't if the plugin ID is a derivative.
      return $plugin_name;
    }

    $module_name = $data_item->getParent()->root_component_name->value;

    if (strpos($plugin_name, $module_name) === 0) {
      // Don't if the plugin ID already has the module name as a prefix, or
      // is entirely the module name.
      return $plugin_name;
    }

    // Prepend the module name.
    return $module_name . '_' . $plugin_name;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // TODO: really need a way to iterate over the scalar values!
    foreach ($this->component_data->injected_services->export() as $service_id) {
      $components['service_' . $service_id] = [
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
      ];
    }

    if (empty($this->component_data->replace_parent_plugin->value)) {
      if (!empty($this->plugin_type_data['config_schema_prefix'])) {
        $schema_id = $this->plugin_type_data['config_schema_prefix']
          . $this->component_data['prefixed_plugin_name'];

        $definition = $this->classHandler->getStandaloneComponentPropertyDefinition('ConfigSchema');
        $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
        $data->yaml_data->set([
          $schema_id => [
            'type' => 'mapping',
            'label' => $this->component_data['prefixed_plugin_name'],
            'mapping' => [],
          ],
        ]);

        $components["config/schema/%module.schema.yml"] = $data;

        // Old style:
        // TODO: decide whether to convert to the above syntax.
        // $components["config/schema/%module.schema.yml"] = [
        //   'component_type' => 'ConfigSchema',
        //   'yaml_data' => [
        //      $schema_id => [
        //       'type' => 'mapping',
        //       'label' => $this->component_data['plugin_name'],
        //       'mapping' => [

        //       ],
        //     ],
        //   ],
        // ];
      }
    }

    if (!empty($this->component_data->replace_parent_plugin->value)) {
      if (!empty($this->plugin_type_data['alter_hook_name'])) {
        $alter_hook_name = 'hook_' . $this->plugin_type_data['alter_hook_name'];

        $components['hooks'] = [
          'component_type' => 'Hooks',
          'hooks' => [
            $alter_hook_name,
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

    return [];
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
    $annotation_class_path = explode('\\', $this->plugin_type_data['plugin_definition_annotation_name']);
    $annotation_class = array_pop($annotation_class_path);

    // Special case: annotation that's just the plugin ID.
    if (!empty($this->plugin_type_data['annotation_id_only'])) {
      $annotation = ClassAnnotation::{$annotation_class}($this->component_data['prefixed_plugin_name']);

      $annotation_lines = $annotation->render();

      return $annotation_lines;
    }

    $annotation_variables = $this->plugin_type_data['plugin_properties'];
    // dump($annotation_variables);

    $annotation_data = [];
    foreach ($annotation_variables as $annotation_variable => $annotation_variable_info) {
      if ($annotation_variable == 'id') {
        // ARGH l
        // CRASH
        // lazy defaults not working with array acess thought I'd fuckkign fixed it!
        $annotation_data['id'] = $this->component_data['prefixed_plugin_name'];
        continue;
      }

      if (in_array($annotation_variable, ['label', 'admin_label'])) {
        $annotation_data[$annotation_variable] = ClassAnnotation::Translation($this->component_data->plugin_label->value);
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
    if ($this->component_data->parent_plugin_class->value) {
      $this->component_data->parent_class_name->value = '\\' . $this->component_data['parent_plugin_class'];
    }
    elseif (isset($this->plugin_type_data['base_class'])) {
      $this->component_data->parent_class_name->value = '\\' . $this->plugin_type_data['base_class'];
    }

    // Set the DI interface if needed.
    $use_di_interface = FALSE;
    // We need the DI interface if this class injects services, unless a parent
    // class also does so.
    if (!empty($this->injectedServices)) {
      $use_di_interface = TRUE;

      if (!empty($this->plugin_type_data['base_class_has_di'])) {
        // No need to implement the interface if the base class already
        // implements it.
        $use_di_interface = FALSE;
      }
      elseif (isset($this->component_data['parent_plugin_class'])) {
        // TODO: violates DRY; we call this twice.
        $parent_construction_parameters = \DrupalCodeBuilder\Utility\CodeAnalysis\DependencyInjection::getInjectedParameters($this->component_data['parent_plugin_class'], 3);
        if (!empty($parent_construction_parameters)) {
          $use_di_interface = FALSE;
        }
      }
      elseif (!empty($this->plugin_type_data['construction'])) {
        $use_di_interface = FALSE;
      }
    }

    if ($use_di_interface) {
      // Numeric key will clobber, so make something up!
      // TODO: fix!
      $this->component_data->interfaces->add(['ContainerFactoryPluginInterface' => '\Drupal\Core\Plugin\ContainerFactoryPluginInterface']);
    }

    return parent::class_declaration();
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    $this->collectSectionBlocksForDependencyInjection();

    // TODO: move this to a component.
    $this->createBlocksFromMethodData($this->plugin_type_data['plugin_interface_methods']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getConstructBaseParameters() {
    if (isset($this->plugin_type_data['constructor_fixed_parameters'])) {
      // Plugin type has non-standard constructor fixed parameters.
      // Argh, the data for this is in a different format: type / typehint.
      // TODO: clean up this WTF.
      $parameters = [];
      foreach ($this->plugin_type_data['constructor_fixed_parameters'] as $i => $param) {
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

    if ($this->component_data->parent_plugin_class->value) {
      $parent_construction_parameters = \DrupalCodeBuilder\Utility\CodeAnalysis\DependencyInjection::getInjectedParameters($this->component_data['parent_plugin_class'], 3);
    }
    elseif (isset($this->plugin_type_data['construction'])) {
      $parent_construction_parameters = $this->plugin_type_data['construction'];
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
