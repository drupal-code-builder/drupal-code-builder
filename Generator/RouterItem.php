<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Utility\NestedArray;
use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\VariantDefinition;
use MutableTypedData\Data\DataItem;

/**
 * Generator for router item on Drupal 8.
 *
 * This adds a routing item to the routing component.
 */
class RouterItem extends BaseGenerator {

  use NameFormattingTrait;

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'path' => PropertyDefinition::create('string')
        ->setLabel("Route path")
        ->setDescription("The path of the route. Include the initial '/'.")
        ->setRequired(TRUE)
        ->setValidators('path'),
      'route_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setCallable([static::class, 'defaultRouteName'])
            ->setDependencies('..:path')
        ),
      'title' => PropertyDefinition::create('string')
        ->setLabel("The page title for the route.")
        ->setLiteralDefault('myPage'),
      'menu_link' => PropertyDefinition::create('complex')
        ->setLabel("Menu link")
        ->setProperties([
          'title' => PropertyDefinition::create('string')
            ->setLabel('The title for the menu link')
            ->setLiteralDefault('My Page')
        ]),
      // TODO: remove this if possible? Probably need to allow PHPClassFile
      // to take a full classname.
      'controller_relative_class_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(DefaultDefinition::create()
          ->setCallable(function (DataItem $component_data) {
            // Create a controller name from the route path.
            $path  = str_replace(['{', '}'], '', $component_data->getItem('..:path')->value);
            $snake = str_replace(['/', '-'], '_', $path);
            $controller_class_name = 'Controller\\' . CaseString::snake($snake)->pascal() . 'Controller';
            return $controller_class_name;
          })
        ),
      'controller' => PropertyDefinition::create('mutable')
        ->setLabel('Controller type')
        ->setRequired(TRUE)
        ->setProperties([
          'controller_type' => PropertyDefinition::create('string')
            ->setLabel('Controller type')
        ])
        ->setVariants([
          'controller' => VariantDefinition::create()
            ->setLabel('Controller class')
            ->setProperties([
              'routing_value' => PropertyDefinition::create('string')
                ->setInternal(TRUE)
                ->setDefault(DefaultDefinition::create()
                  ->setCallable(function (DataItem $component_data) {
                    $path = $component_data->getItem('..:..:path')->value;
                    $class = static::controllerClassFromRoutePath($path);
                    return $class . '::content';
                  })
                ),
            ]),
          'form' => VariantDefinition::create()
            ->setLabel('Form')
            ->setProperties([
              'routing_value' => PropertyDefinition::create('string')
                ->setInternal(TRUE)
                ->setLiteralDefault('\Drupal\module\Form\FormClassName')
            ]),
          'entity_view' => VariantDefinition::create()
            ->setLabel('Entity view display')
            ->setProperties([
              'entity_type_id' => PropertyDefinition::create('string')
                ->setLabel("Entity type ID")
                ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportEntityTypes'))
                ->setRequired(TRUE),
              'entity_view_mode' => PropertyDefinition::create('string')
                ->setLabel("Entity view mode")
                ->setRequired(TRUE),
              'routing_value' => PropertyDefinition::create('string')
                ->setInternal(TRUE)
                ->setExpressionDefault("get('..:entity_type_id') ~ '.' ~ get('..:entity_view_mode')"),
            ]),
          'entity_form' => VariantDefinition::create()
            ->setLabel('Entity form display')
            ->setProperties([
              'entity_type_id' => PropertyDefinition::create('string')
                ->setLabel("Entity type ID")
                ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportEntityTypes'))
                ->setRequired(TRUE),
              'entity_form_mode' => PropertyDefinition::create('string')
                ->setLabel("Entity form mode")
                ->setRequired(TRUE),
              'routing_value' => PropertyDefinition::create('string')
                ->setInternal(TRUE)
                ->setExpressionDefault("get('..:entity_type_id') ~ '.' ~ get('..:entity_form_mode')"),
            ]),
          'entity_list' => VariantDefinition::create()
            ->setLabel('Entity list')
            ->setProperties([
              'entity_type_id' => PropertyDefinition::create('string')
                ->setLabel("Entity type ID")
                ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportEntityTypes'))
                ->setRequired(TRUE),
              'routing_value' => PropertyDefinition::create('string')
                ->setInternal(TRUE)
                ->setExpressionDefault("get('..:entity_type_id')"),
            ]),
          ]),
      'access' => PropertyDefinition::create('mutable')
        ->setLabel('Access type')
        ->setRequired(TRUE)
        ->setProperties([
          'access_type' => PropertyDefinition::create('string')
            ->setLabel('Access type')
        ])
        ->setVariants([
          // This is the 'none' option, but the YAML routing key is '_access',
          // so the option value is weird because of this.
          'access' => VariantDefinition::create()
            ->setLabel('No access control')
            ->setProperties([
              // The value to use in the YAML for the key this variant provides.
              'routing_value' => PropertyDefinition::create('string')
                ->setInternal(TRUE)
                ->setLiteralDefault('TRUE')
            ]),
          'permission' => VariantDefinition::create()
            ->setLabel('Permission')
            ->setProperties([
              'routing_value' => PropertyDefinition::create('string')
                ->setLabel("Permission machine name")
                ->setRequired(TRUE)
                ->setLiteralDefault('access content')
            ]),
          'role' => VariantDefinition::create()
            ->setLabel('Role')
            ->setProperties([
              'routing_value' => PropertyDefinition::create('string')
                ->setLabel("User role machine name")
                ->setLiteralDefault('authenticated')
            ]),
          'custom_access' => VariantDefinition::create()
            ->setLabel('Custom access')
            ->setProperties([
              // Ugly hack: need to fetch the routing_value from the mutable
              // data for custom_access_callback.
              'routing_value' => PropertyDefinition::create('string')
                ->setInternal(TRUE)
                ->setExpressionDefault("get('..:custom_access_callback:routing_value')"),
              'custom_access_callback' => PropertyDefinition::create('mutable')
                ->setLabel('Custom access')
                ->setRequired(TRUE)
                ->setProperties([
                  'callback_location' => PropertyDefinition::create('string')
                    ->setLabel("Access callback")
                ])
                ->setVariants([
                  'controller' => VariantDefinition::create()
                    ->setLabel('Method in the route controller')
                    ->setProperties([
                      'routing_value' => PropertyDefinition::create('string')
                        ->setInternal(TRUE)
                        ->setDefault(DefaultDefinition::create()
                          ->setCallable(function (DataItem $component_data) {
                            $path = $component_data->getItem('..:..:..:path')->value;
                            $class = static::controllerClassFromRoutePath($path);
                            return $class . '::access';
                          })
                        )
                    ]),
                  'custom' => VariantDefinition::create()
                    ->setLabel('Custom class')
                    ->setProperties([
                      'routing_value' => PropertyDefinition::create('string')
                        ->setLabel("Class name, relative to this module's namespace"),
                    ]),
                  'existing' => VariantDefinition::create()
                    ->setLabel('Existing class')
                    ->setProperties([
                      'routing_value' => PropertyDefinition::create('string')
                        ->setLabel('Static method name, with fully-qualified class'),
                    ]),
                ]),
            ]),
          'entity_access' => VariantDefinition::create()
            ->setLabel('Entity access')
            ->setProperties([
              'entity_type_id' => PropertyDefinition::create('string')
                ->setLabel("Entity type ID")
                ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportEntityTypes'))
                ->setRequired(TRUE),
              'entity_access_operation' => PropertyDefinition::create('string')
                ->setLabel("Access operation")
                ->setRequired(TRUE)
                ->setOptionsArray([
                  'view' => 'View',
                  'update' => 'Update',
                  'delete' => 'Delete',
                ]),
              'routing_value' => PropertyDefinition::create('string')
                ->setInternal(TRUE)
                ->setExpressionDefault("get('..:entity_type_id') ~ '.' ~ get('..:entity_access_operation')")
            ]),
        ]),


      // 'controller_plain_class_name' => PropertyDefinition::create('string')
      //   ->setDefault(
      //     DefaultDefinition::create()
      //       ->setCallable([static::class, 'defaultControllerPlainClassName'])
      //       ->setDependencies('..:TODO')
      //   ),
      // 'controller_relative_class_name_pieces' => [
      //   'internal' => TRUE,
      //   'process_default' => TRUE,
      //   'default' => function($component_data) {
      //     return ['Controller', $component_data['controller_plain_class_name']];
      //   },
      // ],
      // 'controller_qualified_class_name' => [
      //   'internal' => TRUE,
      //   'process_default' => TRUE,
      //   'default' => function($component_data) {
      //     return implode('\\', [
      //       'Drupal',
      //       '%module',
      //       'Controller',
      //       $component_data['controller_plain_class_name'],
      //     ]);
      //   },
      // ],
      // 'controller_type' => PropertyDefinition::create('string')
      //   ->setLabel('Controller type')
      //   ->setDescription("The way in which plugins of this type are formed.")
      //   ->setOptions(
      //     OptionDefinition::create(
      //       'annotation',
      //       'Annotation plugin',
      //       "Plugins are classes with an annotation."
      //     ),
      //     OptionDefinition::create(
      //       'yaml',
      //       'YAML plugin',
      //       "Plugins are declared in a single YAML file, usually sharing the same class."
      //     )
      //   )


      //   'label' => "Controller type",
      //   'options' => [
      //     // These are all YAML keys that take an initial '_', but having that
      //     // in the option key makes it harder to enter in the Drush UI.
      //     'controller' => 'Controller class',
      //     'form' => 'Form',
      //     'entity_view' => 'Entity view mode',
      //     'entity_form' => 'Entity form',
      //     'entity_list' => 'Entity list',
      //   ],
      //   'yaml_address' => ['defaults'],
      // ],
      // // The value for the YAML property that's set in controller_type.
      // // This is a bit fiddly, but there's no UI for a key-value pair.
      // 'controller_type_value' => [
      //   'internal' => TRUE,
      //   'default' => function ($component_data) {
      //     $lookup = [
      //       // This will contain a placeholder token, but it's ok to use here as
      //       // the value will be quoted in the rendered YAML anyway.
      //       'controller' => '\\' . $component_data['controller_qualified_class_name'] . '::content',
      //       'form' => 'Drupal\%module\Form\MyFormClass',
      //       'entity_view' => 'ENTITY_TYPE.VIEW_MODE',
      //       'entity_form' => 'ENTITY_TYPE.FORM_MODE',
      //       'entity_list' => 'ENTITY_TYPE',
      //     ];
      //     if (isset($component_data['controller_type'])) {
      //       return $lookup[$component_data['controller_type']];
      //     }
      //   },
      // ],
      // 'access_type' => [
      //   'label' => "Access type",
      //   'options' => [
      //     'access' => 'No access control',
      //     'permission' => 'Permission',
      //     'role' => 'Role',
      //     'entity_access' => 'Entity access',
      //   ],
      //   'yaml_address' => ['requirements'],
      // ],
      // // The value for the YAML property that's set in access_type.
      // 'access_type_value' => [
      //   'internal' => TRUE,
      //   'default' => function ($component_data) {
      //     $lookup = [
      //       'access' => 'TRUE',
      //       'permission' => 'TODO: set permission machine name',
      //       'role' => 'authenticated',
      //       'entity_access' => 'ENTITY_TYPE.OPERATION',
      //     ];
      //     if (!empty($component_data['access_type'])) {
      //       return $lookup[$component_data['access_type']];
      //     }
      //   },
      // ],
    ]);

    return $definition;
  }

  /**
   * Default value callback.
   */
  public static function defaultRouteName($data_item) {
    $component_data = $data_item->getParent();

    // Strip the initial slash so it's not turned into a surplus dot.
    $path = ltrim($component_data['path'], '/');

    // Remove any parameter braces.
    $path = str_replace(['{', '}'], '', $path);

    // Convert hyphens to underscores.
    $path = str_replace('-', '_', $path);

    // Get the module name rather than using the token, to avoid the
    // property name getting quoted.
    $module = $component_data['root_component_name'];
    $route_name = $module . '.' . str_replace('/', '.', $path);
    return $route_name;
  }

  /**
   * Helper for default callbacks for the controller class name.
   */
  public static function controllerClassFromRoutePath(string $path) {
    // Create a controller name from the route path.
    $path  = str_replace(['{', '}'], '', $path);
    $snake = str_replace(['/', '-'], '_', $path);
    $controller_class_name = '\Drupal\%module\Controller\\' . CaseString::snake($snake)->pascal() . 'Controller';
    return $controller_class_name;
  }

  /**
   * Declares the subcomponents for this component.
   *
   * @return
   *  An array of subcomponent names and types.
   */
  public function requiredComponents(): array {
    $components = [];

    // Each RouterItem that gets added will cause a repeat request of these
    // components.
    $components['%module.routing.yml'] = [
      'component_type' => 'Routing',
    ];

    // Controller class content callback.
    if ($this->component_data->controller->controller_type->value == 'controller') {
      $controller_class_required = TRUE;

      $components["controller-content"] = [
        'component_type' => 'PHPFunction',
        'function_name' => 'validate',
        'containing_component' => "%requester:controller",
        'declaration' => 'public function content()',
        'doxygen_first' => "Callback for the {$this->component_data['route_name']} route.",
      ];
    }

    // Controller class access callback.
    // Technically we allow this without a content callback, because validating
    // that both are in sync would be very fiddly.
    if ($this->component_data->access->access_type->value == 'custom_access') {
      if ($this->component_data->access->custom_access_callback->callback_location->value == 'controller') {
        $controller_class_required = TRUE;
        $containing_component = '%requester:controller';
      }

      if ($this->component_data->access->custom_access_callback->callback_location->value == 'custom') {
        $components['access'] = [
          'component_type' => 'PHPClassFile',
          'relative_class_name' => $this->component_data->access->custom_access_callback->routing_value->value,
        ];

        $containing_component = '%requester:access';

        // Hack the routing value to be a complete class name and method name.
        $this->component_data->access->custom_access_callback->routing_value->value =
          '\Drupal\%module\\' .
          $this->component_data->access->custom_access_callback->routing_value->value .
          '::access';
      }

      if (in_array($this->component_data->access->custom_access_callback->callback_location->value, ['controller', 'custom'])) {
        $components["access-method"] = [
          'component_type' => 'PHPFunction',
          'function_name' => 'access',
          'containing_component' => $containing_component,
          'declaration' => 'public function access(\Drupal\Core\Session\AccountInterface $account)',
          'doxygen_first' => "Checks access for the {$this->component_data->route_name->value} route.",
        ];
      }
    }

    if (!empty($controller_class_required)) {
      $controller_relative_class = $this->component_data->controller_relative_class_name->value;

      $components['controller'] = [
        'component_type' => 'PHPClassFile',
        'relative_class_name' => $controller_relative_class,
      ];
    }

    if (!$this->component_data->menu_link->isEmpty()) {
      // Strip off the module name prefix from the route name to make the plugin
      // name, as the plugin generator will add it back again.
      $plugin_name = $this->component_data['route_name'];
      $plugin_name = substr($plugin_name, strlen($this->component_data['root_component_name']) + 1);

      $components['menu_link'] = [
        'component_type' => 'Plugin',
        'plugin_type' => 'menu.link',
        'plugin_name' => $plugin_name,
        'plugin_properties' => [
          'title' => $this->component_data->menu_link->title->value,
          'route_name' => $this->component_data['route_name'],
        ],
      ];
    }

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
  protected function buildComponentContents($children_contents) {
    $path = $this->component_data['path'];

    $route_yaml = [];

    // Prepend a slash to the path for D8 if one not given.
    if (substr($path, 0, 1) != '/') {
      $path = '/' . $path;
    }
    $route_yaml['path'] = $path;
    $route_yaml['defaults']['_title'] = $this->component_data['title'];

    // Controller value.
    $controller_yaml_key = '_' . $this->component_data->controller->controller_type->value;
    $route_yaml['defaults'][$controller_yaml_key] = $this->component_data->controller->routing_value->value;

    // Access value.
    $access_yaml_key = '_' . $this->component_data->access->access_type->value;
    $route_yaml['requirements'][$access_yaml_key] = $this->component_data->access->routing_value->value;

    $route_name = $this->component_data['route_name'];
    $routing_data[$route_name] = $route_yaml;

    return [
      'route' => [
        'role' => 'yaml',
        'content' => $routing_data,
      ],
    ];
  }

}
