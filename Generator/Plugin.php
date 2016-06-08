<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\Plugin.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a plugin.
 */
class Plugin extends PHPClassFile {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
   *
   * A Plugin generator should use as its name the part of the plugin manager
   * service name after 'plugin.manager.'
   * TODO: change this so we can generate more than one plugin of a particular
   * type at a time!
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
   *   (or all if this is entirely omitted) are given default values. Valid
   *   properties are:
   *    - 'class': The name of the annotation class that defines the plugin
   *      type, e.g. 'Drupal\Core\Entity\Annotation\EntityType'.
   *      TODO: since the classnames are unique regardless of namespace, figure
   *      out if there is a way of just specifying the classname.
   */
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    // Set some default properties.
    $component_data += array(
      'injected_services' => [],
    );

    // Prefix the plugin name with the module name.
    $component_data['original_plugin_name'] = $component_data['plugin_name'];
    $component_data['plugin_name'] = $root_generator->component_data['root_name'] . '_' . $component_data['plugin_name'];

    $plugin_type = $component_data['plugin_type'];

    $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $plugin_data = $mb_task_handler_report_plugins->listPluginData();
    $plugin_data = $plugin_data[$plugin_type];

    $component_data['plugin_type_data'] = $plugin_data;

    parent::__construct($component_name, $component_data, $generate_task, $root_generator);
  }

  /**
   * {@inheritdoc}
   */
  protected function setClassNames($component_name) {
    // Create the fully-qualified class name.
    // This is of the form:
    //  \Drupal\{MODULE}\Plugin\{PLUGINTYPE}\{MODULE}{PLUGINNAME}
    $qualified_class_name = implode('\\', [
      'Drupal',
      // Module name.
      $this->root_component->component_data['root_name'],
      // Plugin subdirectory.
      $this->pathToNamespace($this->component_data['plugin_type_data']['subdir']),
      // Plugin ID.
      $this->toCamel($this->component_data['original_plugin_name'])
    ]);

    parent::setClassNames($qualified_class_name);
  }

  /**
   * Define the component data this component needs to function.
   */
  protected static function componentDataDefinition() {
    return array(
      'plugin_type' => array(
        'label' => 'Plugin type',
        'required' => TRUE,
        'options' => function(&$property_info) {
          $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');

          $options = $mb_task_handler_report_plugins->listPluginNamesOptions();

          return $options;
        },
      ),
      'plugin_name' => array(
        'label' => 'Plugin name',
        'required' => TRUE,
        // NOT WORKING!
        'Xdefault' => function($component_data) {
          return $component_data['root_name'] . 'PANTS';
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
    return $this->class_annotation();
  }

  /**
   * Produces the plugin class annotation.
   */
  function class_annotation() {
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
    $class_declaration = 'class ' . $this->plain_class_name;
    if (!empty($this->injectedServices)) {
      $class_declaration .= " implements \\Drupal\\Core\\Plugin\\ContainerFactoryPluginInterface";
    }
    $class_declaration .= ' {';

    return [
      $class_declaration,
    ];
  }

  /**
   * Return the body of the class's code.
   */
  function class_code_body() {
    $code = array();

    // Injected services.
    if (!empty($this->injectedServices)) {
      // Class properties.
      foreach ($this->injectedServices as $service_info) {
        $var_doc = $this->docBlock([
          $service_info['description'] . '.',
          '',
          '@var ' . $service_info['interface']
        ]);
        $code = array_merge($code, $var_doc);
        $code[] = 'protected $' . $service_info['property_name'] . ';';
        $code[] = '';
      }

      // Class constructor.

      // TODO: cleaner system for adding methods!
      // __construct() method
      $code = array_merge($code, $this->codeBodyClassMethodConstruct());

      // create() method.
      $code = array_merge($code, $this->codeBodyClassMethodCreate());
    }

    foreach ($this->component_data['plugin_type_data']['plugin_interface_methods'] as $interface_method_name => $interface_method_data) {
      $function_doc = $this->docBlock('{@inheritdoc}');
      $code = array_merge($code, $function_doc);

      // Trim the semicolon from the end of the interface method.
      $method_declaration = substr($interface_method_data['declaration'], 0, -1);

      $code[] = "$method_declaration {";
      // Add a comment with the method's first line of docblock, so the user
      // has something more informative than '{@inheritdoc}' to go on!
      $code[] = '  // ' . $interface_method_data['description'];
      $code[] = '}';
      $code[] = '';
    }

    // Indent all the class code.
    // TODO: is there a nice way of doing indents?
    $code = array_map(function ($line) {
      return empty($line) ? $line : '  ' . $line;
    }, $code);

    return $code;
  }

  /**
   * Creates the code lines for the __construct() method.
   */
  protected function codeBodyClassMethodConstruct() {
    $code = [];
    $constructor_doc_lines = [
      "Creates a {$this->plain_class_name} instance.",
      '',
      '@param array $configuration',
      '  A configuration array containing information about the plugin instance.',
      '@param string $plugin_id',
      '  The plugin_id for the plugin instance.',
      '@param mixed $plugin_definition',
      '  The plugin implementation definition.',
    ];
    foreach ($this->injectedServices as $service_info) {
      $constructor_doc_lines[] = "@param {$service_info['interface']} \${$service_info['variable_name']}";
      $constructor_doc_lines[] = "  {$service_info['description']}.";
    }
    $constructor_doc = $this->docBlock($constructor_doc_lines);
    $code = array_merge($code, $constructor_doc);

    $constructor_declaration = 'public function __construct(array $configuration, $plugin_id, $plugin_definition';
    foreach ($this->injectedServices as $service_info) {
      $constructor_declaration .= ", {$service_info['interface']} \${$service_info['variable_name']}";
    }
    $constructor_declaration .= ') {';
    $code[] = $constructor_declaration;

    $code[] = '  ' . 'parent::__construct($configuration, $plugin_id, $plugin_definition);';

    foreach ($this->injectedServices as $service_info) {
      $code[] = "  \$this->{$service_info['property_name']} = \${$service_info['variable_name']};";
    }
    $code[] = '}';
    $code[] = '';
    return $code;
  }

  /**
   * Creates the code lines for the create() method.
   */
  protected function codeBodyClassMethodCreate() {
    $code = $this->docBlock('{@inheritdoc}');
    $code[] = 'public static function create(\\Symfony\\Component\\DependencyInjection\\ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {';
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
    $code[] = '';
    return $code;
  }

  /**
   * TODO: is there a core function for this?
   */
  function pathToNamespace($path) {
    return str_replace('/', '\\', $path);
  }

}
