<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a plugin type manager service.
 *
 * @todo: Consider removing this class once Service generator uses child
 * components for its methods.
 */
class PluginTypeManager extends Service {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    // Properties acquired from the requesting PluginType component.
    $plugin_type_properties = [
      'plugin_type',
      'plugin_subdirectory',
      'annotation_class',
      'info_alter_hook',
      'interface',
    ];
    foreach ($plugin_type_properties as $property_name) {
      $data_definition[$property_name] = [
        'acquired' => TRUE,
      ];
    }

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    $this->constructor = $this->codeBodyClassMethodConstruct();
  }

  /**
   * {@inheritdoc}
   */
  protected function codeBodyClassMethodConstruct() {
    $parameters = [
      [
        'name' => 'namespaces',
        'typehint' => '\Traversable',
        'description' => "An object that implements \Traversable which contains the root paths keyed by the corresponding namespace to look for plugin implementations.",
      ],
      [
        'name' => 'cache_backend',
        'typehint' => '\Drupal\Core\Cache\CacheBackendInterface',
        'description' => 'The cache backend.',
      ],
      [
        'name' => 'module_handler',
        'typehint' => '\Drupal\Core\Extension\ModuleHandlerInterface',
        'description' => 'The module handler.',
      ],
    ];

    $constructor_code = $this->buildMethodHeader(
      '__construct',
      $parameters,
      [
        'docblock_first_line' => "Constructs a new {$this->component_data['plain_class_name']}.",
        'prefixes' => ['public'],
      ]
    );

    //dump($this->component_data);

    $code = [];
    $code[] = 'parent::__construct(';
    $code[] = '  ' . "'Plugin/{$this->component_data['plugin_subdirectory']}',";
    $code[] = '  $namespaces,';
    $code[] = '  $module_handler,';
    // TODO: use $this->component_data['interface'], but that has initial \!
    $code[] = "  " . $this->component_data['interface'] . '::class' . ",";
    $code[] = "  " . '\\' . $this->makeQualifiedClassName([
      'Drupal',
      $this->component_data['root_component_name'],
      'Annotation',
      // TODO: cleanup!
      $this->component_data['annotation_class'],
    ]) . '::class';
    $code[] = ');';
    $code[] = '';

    $code[] = "\$this->alterInfo('{$this->component_data['info_alter_hook']}');";
    $code[] = "\$this->setCacheBackend(\$cache_backend, '{$this->component_data['plugin_type']}_plugins');";

    // Indent the body.
    $code = $this->indentCodeLines($code);

    $code = array_merge($constructor_code, $code);

    $code[] = '}';

    return $code;
  }

}
