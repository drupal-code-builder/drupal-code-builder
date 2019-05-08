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
      'discovery_type',
      'plugin_type',
      'plugin_label',
      'plugin_subdirectory',
      'annotation_class',
      'info_alter_hook',
      'interface',
      'base_class',
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
  protected function buildComponentContents($children_contents) {
    parent::buildComponentContents($children_contents);

    // For YAML plugin type, we need to hack out various bits of injection,
    // which is a PITA.
    if ($this->component_data['discovery_type'] == 'yaml') {
      // The cache.discover service is injected, but not set to a property.
      foreach ($this->childContentsGrouped['service_property'] as $key => $content) {
        if ($content['id'] == 'cache.discovery') {
          unset($this->childContentsGrouped['service_property'][$key]);
        }
      }

      // The cache.discovery param name needs to be tweaked.
      // TODO: fix this hack, do it somewhere like code analysis?
      foreach ($this->childContentsGrouped['constructor_param'] as $key => $content) {
        if ($content['id'] == 'cache.discovery') {
          $this->childContentsGrouped['constructor_param'][$key]['name'] = 'cache_backend';
        }
      }

      // The cache.discovery param doesn't get assigned.
      foreach ($this->childContentsGrouped['property_assignment'] as $key => $content) {
        if ($content['id'] == 'cache.discovery') {
          unset($this->childContentsGrouped['property_assignment'][$key]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    $this->constructor = $this->codeBodyClassMethodConstruct();

    if ($this->component_data['discovery_type'] == 'yaml') {
      $this->functions[] = $this->codeBodyGetDiscovery();

      $this->properties[] = $this->createPropertyBlock(
        'defaults',
        'array',
        [
          'docblock_first_line' => "Provides some default values for all {$this->component_data['plugin_label']} plugins.",
          'default' => [
            // Need to trim the initial backslash.
            'class' => substr($this->component_data['base_class'], 1),
          ],
          'break_array_value' => TRUE,
        ]
      );
    }

    // Call this last so the plugin $defaults property is above any injected
    // services.
    $this->collectSectionBlocksForDependencyInjection();
  }

  /**
   * {@inheritdoc}
   */
  protected function codeBodyClassMethodConstruct() {
    $parameters = [];

    // Annotation plugins have injection parameters that don't come from the
    // service definition, as they have a parent service.
    if ($this->component_data['discovery_type'] == 'annotation') {
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
    }
    else {
      foreach ($this->childContentsGrouped['constructor_param'] as $service_parameter) {
        $parameters[] = $service_parameter;
      }
    }

    $parent_injected_services = $this->getConstructParentInjectedServices();
    $parameters = array_merge($parameters, $parent_injected_services);

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

    // Only annotation type plugins call the parent constructor.
    if ($this->component_data['discovery_type'] == 'annotation') {
      $code[] = 'parent::__construct(';
      $code[] = '  ' . "'Plugin/{$this->component_data['plugin_subdirectory']}',";
      $code[] = '  $namespaces,';
      $code[] = '  $module_handler,';
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
    }
    else {
      $code[] = '// Skip calling the parent constructor, since that assumes annotation-based';
      $code[] = '// discovery.';
    }

    if (isset($this->childContentsGrouped['property_assignment'])) {
      foreach ($this->childContentsGrouped['property_assignment'] as $content) {
        $code[] = "\$this->{$content['property_name']} = \${$content['variable_name']};";
      }
      $code[] = '';
    }

    $code[] = "\$this->alterInfo('{$this->component_data['info_alter_hook']}');";
    $code[] = "\$this->setCacheBackend(\$cache_backend, '{$this->component_data['plugin_type']}_plugins');";

    // Indent the body.
    $code = $this->indentCodeLines($code);

    $code = array_merge($constructor_code, $code);

    $code[] = '}';

    return $code;
  }

  /**
   * Generates the code for the getDiscovery() method for YAML plugins.
   */
  protected function codeBodyGetDiscovery() {
    $header_code = $this->buildMethodHeader(
      'getDiscovery',
      [],
      [
        'inheritdoc' => TRUE,
        'prefixes' => ['protected'],
      ]
    );

    $code = [];

    $code[] = 'if (!$this->discovery) {';
    $code[] = "  \$discovery = new \Drupal\Core\Plugin\Discovery\YamlDiscovery('{$this->component_data['plugin_type']}', \$this->moduleHandler->getModuleDirectories());";
    $code[] = '  $this->discovery = new \Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator($discovery);';
    $code[] = '}';
    $code[] = 'return $this->discovery;';

    // Indent the body.
    $code = $this->indentCodeLines($code);

    $code = array_merge($header_code, $code);

    $code[] = '}';

    return $code;
  }

}
