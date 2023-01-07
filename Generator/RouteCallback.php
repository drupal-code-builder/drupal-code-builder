<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator a dynamic route provider.
 */
class RouteCallback extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'provider_class_short_name' => PropertyDefinition::create('string')
        ->setLabel('The short class name of the route provider')
        ->setRequired(TRUE)
        ->setLiteralDefault('RouteProvider'),
      'provider_qualified_class_name' => PropertyDefinition::create('string')
        ->setRequired(TRUE)
        ->setInternal(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setCallable(function (DataItem $component_data) {
            $default = implode('\\', [
              'Drupal',
              $component_data->getParent()->root_component_name->value,
              'Routing',
              $component_data->getParent()->provider_class_short_name->value,
            ]);
            return $default;
          })
          ->setDependencies('..:provider_class_short_name')
      ),
    ]);

    return $definition;
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
    ];

    $components['route_provider'] = [
      'component_type' => 'PHPClassFile',
      'plain_class_name' => $this->component_data['provider_class_short_name'],
      'relative_namespace' => 'Routing',
      'docblock_first_line' => "Defines dynamic routes.",
    ];

    $components["route_provider_method"] = [
      'component_type' => 'PHPFunction',
      'function_name' => 'routes',
      'containing_component' => "%requester:route_provider",
      'prefixes' => ['public'],
      'return_type' => 'array',
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

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%self:%module.routing.yml';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    $routing_data = [
      'route_callbacks' => [
        '\\' . $this->component_data['provider_qualified_class_name'] . '::routes',
      ],
    ];

    return $routing_data;
  }

}
