<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for a service provider.
 */
class ServiceProvider extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  protected static $dataType = 'boolean';

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition($definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('relative_class_name')
      ->setExpressionDefault("machineToClass(get('..:root_component_name')) ~ 'ServiceProvider'");

      $definition->getProperty('class_docblock_lines')
      ->setLiteralDefault(['Alters services dynamically for the %sentence module.']);

      $definition->getProperty('parent_class_name')
      ->setLiteralDefault('\Drupal\Core\DependencyInjection\ServiceProviderBase');
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = [
      'alter' => [
        'component_type' => 'PHPFunction',
        'function_name' => 'alter',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function alter(\Drupal\Core\DependencyInjection\ContainerBuilder $container)',
        'body' => [
          "£definition = £container->getDefinition('some_service');",
          "£definition->setClass('Drupal\some_module\SomeOtherClass');",
        ],
      ],
    ];

    return $components;
  }

}
