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
   * Return the body of the class's code.
   */
  function classCodeBody() {
    // TODO: this code sets up class properties for the parent classCodeBody()
    // to work with, as if they had been set by buildComponentContents().
    // This should be refactored in due course.
    $this->constructor = $this->codeBodyClassMethodConstruct();

    // Call the grandparent method... ugly.
    return PHPClassFile::classCodeBody();
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
    $code[] = "  '" . $this->makeQualifiedClassName([
      'Drupal',
      $this->component_data['root_component_name'],
      'Plugin',
      // TODO: won't work if plugin subdir has nesting.
      $this->component_data['plugin_subdirectory'],
      // TODO: cleanup!
      $this->component_data['annotation_class'] . 'Interface',
    ]) . "',";
    $code[] = "  '" . $this->makeQualifiedClassName([
      'Drupal',
      $this->component_data['root_component_name'],
      'Annotation',
      // TODO: cleanup!
      $this->component_data['annotation_class'],
    ]) . "'";
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
