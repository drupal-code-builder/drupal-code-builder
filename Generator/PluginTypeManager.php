<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for a plugin type manager service.
 */
class PluginTypeManager extends Service {

  /**
   * Plugin managers always have a constructor.
   *
   * @var bool
   */
  protected $forceConstructComponent = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyDefinition $definition) {
    $definition = parent::getPropertyDefinition();

    // Properties acquired from the requesting PluginType component.
    $plugin_type_properties = [
      'discovery_type',
      'plugin_type',
      'plugin_label',
      'plugin_subdirectory',
      'plugin_plain_class_name',
      'info_alter_hook',
      'interface',
      'base_class',
    ];
    foreach ($plugin_type_properties as $property_name) {
      $definition->addProperty(
        PropertyDefinition::create('string')
          ->setName($property_name)
          ->setAutoAcquiredFromRequester()
      );
    }

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // Add the getType() method,
    $components['method-get-type'] = [
      'component_type' => 'PHPFunction',
      'function_name' => 'getType',
      'containing_component' => '%requester',
      'docblock_inherit' => TRUE,
      'prefixes' => ['protected'],
      'body' => [
        "return '{$this->component_data['plugin_type']}';",
      ],
    ];

    if ($this->component_data['discovery_type'] == 'yaml') {
      $components['method-get-discovery'] = [
        'component_type' => 'PHPFunction',
        'function_name' => 'getDiscovery',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'prefixes' => ['protected'],
        'body' => [
          'if (!$this->discovery) {',
          "  \$discovery = new \Drupal\Core\Plugin\Discovery\YamlDiscovery('{$this->component_data['plugin_type']}', \$this->moduleHandler->getModuleDirectories());",
          '  $this->discovery = new \Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator($discovery);',
          '}',
          'return $this->discovery;',
          ],
      ];
    }

    $components['construct']['function_docblock_lines'] = ["Constructs a new {$this->component_data['plain_class_name']}Manager."];

    if ($this->component_data->discovery_type->value == 'annotation') {
      $components['construct']['parameters'] = [
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

    if ($this->component_data->discovery_type->value == 'yaml') {
      // The cache doesn't get assigned normally but in a custom code line
      // set further down.
      $components['service_cache.discovery']['omit_assignment'] = TRUE;
    }

    // Only annotation type plugins call the parent constructor.
    $code = [];
    if ($this->component_data->discovery_type->value == 'annotation') {
      $code[] = 'parent::__construct(';
      $code[] = '  ' . "'Plugin/{$this->component_data['plugin_subdirectory']}',";
      $code[] = '  $namespaces,';
      $code[] = '  $module_handler,';
      $code[] = "  " . $this->component_data['interface'] . '::class' . ",";
      $code[] = "  " . '\\' . $this->makeQualifiedClassName([
        'Drupal',
        $this->component_data['root_component_name'],
        'Annotation',
        // We can't acquire the annotation class name, as it's a mutable
        // property and so not always present. Use this instead.
        $this->component_data['plugin_plain_class_name'],
      ]) . '::class';
      $code[] = ');';
      $code[] = '';
    }
    else {
      $code[] = '// Skip calling the parent constructor, since that assumes annotation-based';
      $code[] = '// discovery.';
      // YAML managers have more code here from InjectedService components.
      $code[] = 'CONTAINED_COMPONENTS';
      $code[] = '';
    }

    $code[] = "\$this->alterInfo('{$this->component_data['info_alter_hook']}');";
    $code[] = "\$this->setCacheBackend(\$cache_backend, '{$this->component_data['plugin_type']}_plugins');";

    $components['construct']['body'] = $code;

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function getContentsElement(string $type): array {
    $subcontents = parent::getContentsElement($type);

    // For YAML plugin type, we need to hack out various bits of injection,
    // which is a PITA.
    if ($this->component_data['discovery_type'] == 'yaml') {
      // The cache.discover service is injected, but not set to a property.
      if ($type == 'service_property') {
        foreach ($subcontents as $key => $content) {
          if ($content['id'] == 'cache.discovery') {
            unset($subcontents[$key]);
          }
        }
      }

      // The cache.discovery param name needs to be tweaked.
      // TODO: fix this hack, do it somewhere like code analysis?
      if ($type == 'constructor_param') {
        foreach ($subcontents as $key => $content) {
          if ($content['id'] == 'cache.discovery') {
            $subcontents[$key]['name'] = 'cache_backend';
          }
        }
      }

      // The cache.discovery param doesn't get assigned.
      if ($type == 'property_assignment') {
        foreach ($subcontents as $key => $content) {
          if ($content['id'] == 'cache.discovery') {
            unset($subcontents[$key]);
          }
        }
      }
    }

    return $subcontents;
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    if ($this->component_data['discovery_type'] == 'yaml') {
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

}
