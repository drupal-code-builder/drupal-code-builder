<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;

/**
 * General class for plugin classes.
 *
 * Used for:
 * - plugin base classes
 * - base class for class-based discovery plugins
 * - custom plugin class for Yaml-based discovery plugins.
 */
class PluginClassBase extends PHPClassFileWithInjection {

  /**
   * {@inheritdoc}
   */
  protected const CLASS_DI_INTERFACE = '\Drupal\Core\Plugin\ContainerFactoryPluginInterface';

  /**
   * The plugin type data.
   *
   * @var array
   */
  protected $plugin_type_data;

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
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('use_static_factory_method')
      ->setLiteralDefault(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getConstructBaseParameters() {
    return static::STANDARD_FIXED_PARAMS;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateParameters() {
    return static::STANDARD_FIXED_PARAMS;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // TODO: remove this once tests are updated.
    if (isset($components['construct'])) {
      $components['construct']['use_primitive_parameter_type_declarations'] = FALSE;
    }

    return $components;
  }

}
