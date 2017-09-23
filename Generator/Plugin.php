<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\Plugin.
 */

namespace DrupalCodeBuilder\Generator;

use \DrupalCodeBuilder\Exception\InvalidInputException;

/**
 * Generator for a plugin.
 */
class Plugin extends PHPClassFile {

  /**
   * The unique name of this generator.
   */
  public $name;

  /**
   * An array of data about injected services.
   */
  protected $injectedServices = [];

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

    // Prefix the plugin name with the module name.
    $component_data['original_plugin_name'] = $component_data['plugin_name'];
    $component_data['plugin_name'] = $component_data['root_component_name'] . '_' . $component_data['plugin_name'];

    $plugin_type = $component_data['plugin_type'];

    $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $plugin_types_data = $mb_task_handler_report_plugins->listPluginData();

    // Try to find the intended plugin type.
    if (isset($plugin_types_data[$plugin_type])) {
      $plugin_data = $plugin_types_data[$plugin_type];
    }
    else {
      $plugin_types_data_by_subdirectory = $mb_task_handler_report_plugins->listPluginDataBySubdirectory();
      if (isset($plugin_types_data_by_subdirectory[$plugin_type])) {
        $plugin_data = $plugin_types_data_by_subdirectory[$plugin_type];
      }
      else {
        // Nothing found. Throw exception.
        throw new InvalidInputException("Plugin type $plugin_type not found.");
      }
    }

    $component_data['plugin_type_data'] = $plugin_data;

    // Create the relative qualified class name.
    // The full class name will be of the form:
    //  \Drupal\{MODULE}\Plugin\{PLUGINTYPE}\{MODULE}{PLUGINNAME}
    $component_data['relative_class_name'] = array_merge(
      // Plugin subdirectory.
      $this->pathToNamespacePieces($component_data['plugin_type_data']['subdir']),
      // Plugin ID.
      [
        self::toCamel($component_data['original_plugin_name']),
      ]
    );

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + array(
      'plugin_type' => array(
        'label' => 'Plugin type',
        'description' => "The identifier of the plugin type. This can be either the manager service name with the 'plugin.manager.' prefix removed, " .
          ' or the subdirectory name.',
        'required' => TRUE,
        'options' => function(&$property_info) {
          $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');

          $options = $mb_task_handler_report_plugins->listPluginNamesOptions();

          return $options;
        },
      ),
      'plugin_name' => array(
        'label' => 'Plugin name',
        // TODO: say in help text that the module name will be prepended for you!
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
      ),
      'injected_services' => array(
        'label' => 'Injected services',
        'format' => 'array',
        'options' => function(&$property_info) {
          $mb_task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');

          $options = $mb_task_handler_report_services->listServiceNamesOptions();

          return $options;
        },
        'options_allow_other' => TRUE,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function requestedComponentHandling() {
    return 'repeat';
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    foreach ($this->component_data['injected_services'] as $service_id) {
      $components[$this->name . '_' . $service_id] = array(
        'component_type' => 'InjectedService',
        'container' => $this->getUniqueID(),
        'service_id' => $service_id,
      );
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
  protected function class_doc_block() {
    // TODO: add docblock_first_line.
    return $this->classAnnotation();
  }

  /**
   * Produces the plugin class annotation.
   */
  function classAnnotation() {
    $annotation_variables = $this->component_data['plugin_type_data']['plugin_properties'];
    //ddpr($class_variables);

    // Drupal\Core\Block\Annotation\Block

    $annotation_class_path = explode('\\', $this->component_data['plugin_type_data']['plugin_definition_annotation_name']);
    $annotation_class = array_pop($annotation_class_path);

    $docblock_code = array();
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

    return $this->docBlock($docblock_code);
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
   * Return the body of the class's code.
   */
  function classCodeBody() {
    // TODO: this code sets up class properties for the parent classCodeBody()
    // to work with, as if they had been set by buildComponentContents().
    // This should be refactored in due course.
    // Injected services.
    // TODO: refactor this along with Plugin to a parent class.
    if (!empty($this->injectedServices)) {
      foreach ($this->injectedServices as $service_info) {
        $property_code = $this->docBlock([
          $service_info['description'] . '.',
          '',
          '@var ' . $service_info['interface']
        ]);
        $property_code[] = 'protected $' . $service_info['property_name'] . ';';

        $this->properties[] = $property_code;
      }

      // __construct() method
      $this->constructor = $this->codeBodyClassMethodConstruct();

      // create() method.
      $this->functions = array_merge([$this->codeBodyClassMethodCreate()], $this->functions);
    }

    // TODO: move this to a component.
    foreach ($this->component_data['plugin_type_data']['plugin_interface_methods'] as $interface_method_name => $interface_method_data) {
      $function_code = [];
      $function_doc = $this->docBlock('{@inheritdoc}');
      $function_code = array_merge($function_code, $function_doc);

      // Trim the semicolon from the end of the interface method.
      $method_declaration = substr($interface_method_data['declaration'], 0, -1);

      $function_code[] = "$method_declaration {";
      // Add a comment with the method's first line of docblock, so the user
      // has something more informative than '{@inheritdoc}' to go on!
      $function_code[] = '  // ' . $interface_method_data['description'];
      $function_code[] = '}';

      // Add to the functions section array for the parent to merge.
      $this->functions[] = $function_code;
    }

    return parent::classCodeBody();
  }

  /**
   * Creates the code lines for the __construct() method.
   */
  protected function codeBodyClassMethodConstruct() {
    $parameters = [
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
    foreach ($this->childContentsGrouped['constructor_param'] as $service_parameter) {
      $parameters[] = $service_parameter;
    }

    $code = $this->buildMethodHeader(
      '__construct',
      $parameters,
      [
        'docblock_first_line' => "Creates a {$this->plain_class_name} instance.",
        'prefixes' => ['public'],
      ]
    );

    $code[] = '  ' . 'parent::__construct($configuration, $plugin_id, $plugin_definition);';

    foreach ($this->injectedServices as $service_info) {
      $code[] = "  \$this->{$service_info['property_name']} = \${$service_info['variable_name']};";
    }
    $code[] = '}';

    return $code;
  }

  /**
   * Creates the code lines for the create() method.
   */
  protected function codeBodyClassMethodCreate() {
    $parameters = [
      [
        'name' => 'container',
        'typehint' => '\\Symfony\\Component\\DependencyInjection\\ContainerInterface',
      ],
      [
        'name' => 'configuration',
        'typehint' => 'array',
      ],
      [
        'name' => 'plugin_id',
      ],
      [
        'name' => 'plugin_definition',
      ],
    ];

    $code = $this->buildMethodHeader(
      'create',
      $parameters,
      [
        'inheritdoc' => TRUE,
        'prefixes' => ['public', 'static'],
      ]
    );

    $code[] = '  return new static(';
    $code[] = '    $configuration,';
    $code[] = '    $plugin_id,';
    $code[] = '    $plugin_definition,';

    $container_extraction_lines = [];
    foreach ($this->childContentsGrouped['container_extraction'] as $container_extraction) {
      $container_extraction_lines[] = '    ' . $container_extraction;
    }

    // Remove the last comma.
    end($container_extraction_lines);
    $last_line_key = key($container_extraction_lines);
    $container_extraction_lines[$last_line_key] = rtrim($container_extraction_lines[$last_line_key], ',');
    $code = array_merge($code, $container_extraction_lines);

    $code[] = '  );';
    $code[] = '}';

    return $code;
  }

  /**
   * TODO: is there a core function for this?
   */
  function pathToNamespacePieces($path) {
    return explode('/', $path);
  }

}
