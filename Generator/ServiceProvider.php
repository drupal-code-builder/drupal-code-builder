<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for a service provider.
 */
class ServiceProvider extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    $data_definition['relative_class_name']
      ->setExpressionDefault("machineToClass(get('..:root_component_name')) ~ 'ServiceProvider'");

    $data_definition['class_docblock_lines']
      ->setLiteralDefault(['Alters services dynamically for the %sentence module.']);

    $data_definition['parent_class_name']
      ->setLiteralDefault('\Drupal\Core\DependencyInjection\ServiceProviderBase');

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = [
      'alter' => [
        'component_type' => 'PHPFunction',
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
