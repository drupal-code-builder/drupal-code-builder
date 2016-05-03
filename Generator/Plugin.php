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
    $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
    $service_data = $task_handler_report_services->listServiceData();

    $component_data['service_definitions'] = array_intersect_key($service_data, array_fill_keys($component_data['injected_services'], TRUE));

    // Add in some extra generated data.
    foreach ($component_data['service_definitions'] as &$service_info) {
      $id_pieces = preg_split('@[_.]@', $service_info['id']);
      $service_info['variable_name'] = implode('_', $id_pieces);
      $id_pieces_first = array_shift($id_pieces);
      $service_info['property_name'] = implode('', array_merge([$id_pieces_first], array_map('ucfirst', $id_pieces)));
      $interface_pieces = explode('\\', $service_info['interface']);
      $service_info['unqualified_interface'] = array_pop($interface_pieces);
    }

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
   * Return the main body of the file code.
   *
   * TODO: messier and messier arrays within arrays! file_contents() needs
   * rewriting!
   */
  function code_body() {
    return array_merge(
      $this->code_namespace(),
      $this->imports(),
      $this->class_annotation(),
      $this->class_body()
    );
  }

  /**
   * Produces the namespace import statements.
   */
  function imports() {
    $imports = [];

    $imported_classes = [];
    foreach ($this->component_data['service_definitions'] as $service_info) {
      $imported_classes[] = trim($service_info['interface'], '\\');
    }

    if (!empty($this->component_data['service_definitions'])) {
      $imported_classes[] = 'Drupal\Core\Plugin\ContainerFactoryPluginInterface';
      $imported_classes[] = 'Symfony\Component\DependencyInjection\ContainerInterface';
    }

    if (!empty($imported_classes)) {
      foreach ($imported_classes as $class) {
        $imports[] = "use $class;";
      }
      $imports[] = '';
    }

    return $imports;
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
   * Produce the class.
   */
  function class_body() {
    $code = array();

    // Injected services.
    if (!empty($this->component_data['service_definitions'])) {
      // Class properties.
      foreach ($this->component_data['service_definitions'] as $service_info) {
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
      foreach ($this->component_data['service_definitions'] as $service_info) {
        $constructor_doc_lines[] = "@param {$service_info['interface']} \${$service_info['variable_name']}";
        $constructor_doc_lines[] = "  {$service_info['description']}.";
      }
      $constructor_doc = $this->docBlock($constructor_doc_lines);
      $code = array_merge($code, $constructor_doc);

      $constructor_declaration = 'public function __construct(array $configuration, $plugin_id, $plugin_definition';
      foreach ($this->component_data['service_definitions'] as $service_info) {
        $constructor_declaration .= ", {$service_info['unqualified_interface']} \${$service_info['variable_name']}";
      }
      $constructor_declaration .= ') {';
      $code[] = $constructor_declaration;

      $code[] = '  ' . 'parent::__construct($configuration, $plugin_id, $plugin_definition);';

      foreach ($this->component_data['service_definitions'] as $service_info) {
        $code[] = "  \$this->{$service_info['property_name']} = \${$service_info['variable_name']};";
      }
      $code[] = '}';
      $code[] = '';

      $code = array_merge($code, $this->docBlock('{@inheritdoc}'));
      $code[] = 'public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {';
      $code[] = '  return new static(';
      $code[] = '    $configuration,';
      $code[] = '    $plugin_id,';
      $code[] = '    $plugin_definition,';

      end($this->component_data['service_definitions']);
      $last_service_id = key($this->component_data['service_definitions']);
      foreach ($this->component_data['service_definitions'] as $service_id => $service_info) {
        $line = "    \$container->get('{$service_info['id']}')";
        if ($service_id != $last_service_id) {
          $line .= ',';
        }
        $code[] = $line;
      }
      $code[] = '  );';
      $code[] = '}';
      $code[] = '';
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

    // Add the top and bottom.
    // Urgh, going backwards! Improve DX here!
    array_unshift($code, '');

    $class_declaration = 'class ' . $this->plain_class_name;
    if (!empty($this->component_data['service_definitions'])) {
      $class_declaration .= " implements ContainerFactoryPluginInterface";
    }
    $class_declaration .= ' {';
    array_unshift($code, $class_declaration);

    $code[] = '}';
    // Newline at end of file. TODO: this should be automatic!
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
