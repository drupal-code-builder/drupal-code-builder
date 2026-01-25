<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for plugin deriver classes.
 */
class PluginDeriver extends PHPClassFileWithInjection {

  /**
   * {@inheritdoc}
   */
  protected const CLASS_DI_INTERFACE = '\Drupal\Core\Plugin\Discovery\ContainerDeriverInterface';

  /**
   * The interface to use if there is no DI.
   */
  protected const CLASS_NO_DI_INTERFACE = '\Drupal\Component\Plugin\Derivative\DeriverInterface';

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
      'name' => 'base_plugin_id',
      'description' => 'The base plugin ID.',
      'typehint' => 'string',
    ],
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
   * Produces the class declaration.
   */
  function classDeclaration() {
    if (!$this->needsDiInterface()) {
      // Numeric key will clobber, so make something up!
      // TODO: fix!
      $this->component_data->interfaces->add(['CLASS_NO_DI_INTERFACE' => static::CLASS_NO_DI_INTERFACE]);
    }

    return parent::classDeclaration();
  }

  /**
   * {@inheritdoc}
   */
  protected function getConstructBaseParameters() {
    // Deriver classes do not pass on the $base_plugin_id create() parameter to
    // the constructor.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCreateParameters() {
    return static::STANDARD_FIXED_PARAMS;
  }

}
