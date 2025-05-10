<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator a dynamic route provider.
 */
class DynamicRouteProvider extends PHPClassFileWithInjection {

  /**
   * {@inheritdoc}
   */
  protected const CLASS_DI_INTERFACE = '\Drupal\Core\DependencyInjection\ContainerInjectionInterface';

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('relative_namespace')
      ->setLiteralDefault('Routing');

    $definition->getProperty('plain_class_name')
      ->setLabel('The short class name of the route provider')
      ->setRequired(TRUE)
      ->setLiteralDefault('RouteProvider');

    $definition->getProperty('relative_class_name')
      ->setInternal(TRUE);

    $definition->getProperty('use_static_factory_method')
      ->setLiteralDefault(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // Each RouterItem that gets added will cause a repeat request of these
    // components.
    $components['%module.routing.yml'] = [
      'component_type' => 'Routing',
      'yaml_data' => [
        'route_callbacks' => [
          '\\' . $this->component_data->qualified_class_name->value . '::routes',
        ],
      ],
    ];

    $components["route_provider_method"] = [
      'component_type' => 'PHPFunction',
      'function_name' => 'routes',
      'containing_component' => "%requester",
      'prefixes' => ['public'],
      'return' => [
        'return_type' => 'array',
        'doc_type' => '\Symfony\Component\Routing\Route[]',
      ],
      'function_docblock_lines' => ["Returns an array of routes."],
      'body' => explode("\n", <<<EOT
        £routes = [];
        £routes['my_route'] = new \Symfony\Component\Routing\Route(
          // Path to attach this route to:
          '/example',
          // Route defaults:
          [
            '_controller' => '\Drupal\\example\Controller\ExampleController::content',
            '_title' => 'Hello',
          ],
          // Route requirements:
          [
            '_permission'  => 'access content',
          ]
        );
        return £routes;
        EOT,
      ),
    ];

    return $components;
  }

}
