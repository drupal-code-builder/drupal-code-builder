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
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'path' => [
        'label' => "Route path",
        'description' => "The path of the route. Include the initial '/'.",
        'required' => TRUE,
      ],
      'route_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setCallable([static::class, 'defaultRouteName'])
            ->setLazy(TRUE)
            ->setDependencies('..:TODO')
        ),
      'title' => [
        'label' => "The page title for the route.",
        'default' => 'myPage',
        'process_default' => TRUE,
      ],
      'menu_link' => [
        'label' => "Menu link",
        'format' => 'compound',
        'cardinality' => 1,
        'properties' => [
          'title' => [
            'label' => "The title for the menu link.",
            'default' => 'My Page',
          ],
        ],
      ],
      // TODO! how does the data type here resolve with the data type from the
      // generator class???????
      // ARGH no, 'mutable' does NOT belong here!
      'controller' => PropertyDefinition::create('mutable')
        ->setLabel('Controller type')
        ->setRequired(TRUE)
        ->setProperties([
          'controller_type' => PropertyDefinition::create('string')
            ->setLabel('Controller type')
            // ->setOptionsArray([
            //   'controller' => 'Controller class',
            //   'form' => 'Form',
            //   'entity_view' => 'Entity view mode',
            //   'entity_form' => 'Entity form',
            //   'entity_list' => 'Entity list',
            // ])
        ])
        ->setVariants([
          'controller' => VariantDefinition::create()
            ->setLabel('Controller class')
            ->setProperties([
              'controller_relative_class_name' => PropertyDefinition::create('string')
                ->setInternal(TRUE)
                ->setDefault(DefaultDefinition::create()
                  ->setLazy(TRUE)
                  ->setCallable(function (DataItem $component_data) {
                    // Create a controller name from the route path.
                    $path  = str_replace(['{', '}'], '', $component_data->getRelative('..:..:path')->value);
                    $snake = str_replace(['/', '-'], '_', $path);
                    $controller_class_name = 'Controller\\' . CaseString::snake($snake)->pascal() . 'Controller';
                    return $controller_class_name;
                  }),
                ),
              'routing_value' => PropertyDefinition::create('string')
                ->setInternal(TRUE)
                ->setDefault(DefaultDefinition::create()
                  ->setCallable(function (DataItem $component_data) {
                    // AARGH HACK! Repeating the work the class component does!
                    return '\Drupal\%module\\' . $component_data->getParent()->controller_relative_class_name->value . '::content';
                  })
                ),
            ]),
          'form' => VariantDefinition::create()
            ->setLabel('Form')
            ->setProperties([]),
          'entity_view' => VariantDefinition::create()
            ->setLabel('Entity view display')
            ->setProperties([
              // TODO: 4.1
              // Needs entity type data gathering!
              // 'entity_type'
            ]),
          'entity_form' => VariantDefinition::create()
            ->setLabel('Entity form display')
            ->setProperties([]),
          'entity_list' => VariantDefinition::create()
            ->setLabel('Entity list')
            ->setProperties([]),
          ]),
      'access' => PropertyDefinition::create('mutable')
        ->setLabel('Access type')
        ->setRequired(TRUE)
        ->setProperties([
          'access_type' => PropertyDefinition::create('string')
            ->setLabel('Access type')
        ])
        ->setVariants([
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
          'entity_access' => VariantDefinition::create()
            ->setLabel('Entity access')
            ->setProperties([
              'entity_type_id' => PropertyDefinition::create('string')
                ->setLabel("Entity type ID")
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
      //       ->setLazy(TRUE)
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
    ];
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

    // Get the module name rather than using the token, to avoid the
    // property name getting quoted.
    $module = $component_data['root_component_name'];
    $route_name = $module . '.' . str_replace('/', '.', $path);
    return $route_name;
  }

  /**
   * Declares the subcomponents for this component.
   *
   * @return
   *  An array of subcomponent names and types.
   */
  public function requiredComponents() {
    $components = [];

    // Each RouterItem that gets added will cause a repeat request of these
    // components.
    $components['%module.routing.yml'] = array(
      'component_type' => 'Routing',
    );


    // Add a controller class if needed.
    if ($this->component_data->controller->controller_type->value == 'controller') {
      $controller_relative_class = $this->component_data->controller->controller_relative_class_name->value;

      $components['controller'] = array(
        'component_type' => 'PHPClassFile',
        'relative_class_name' => $controller_relative_class,
      );
      $components["controller:content"] = [
        'component_type' => 'PHPFunction',
        'containing_component' => "%requester:controller",
        'declaration' => 'public function content()',
        'doxygen_first' => "Callback for the {$this->component_data['route_name']} route.",
      ];
    }

    if (!$this->component_data->menu_link->isEmpty()) {
      // Strip off the module name prefix from the route name to make the plugin
      // name, as the plugin generator will add it back again.
      $plugin_name = $this->component_data['route_name'];
      $plugin_name = substr($plugin_name, strlen($this->component_data['root_component_name']) + 1);

      $components['menu_link'] = array(
        'component_type' => 'PluginYAML',
        'plugin_type' => 'menu.link',
        'plugin_name' => $plugin_name,
        'plugin_properties' => [
          'title' => $this->component_data->menu_link->title->value,
          'route_name' => $this->component_data['route_name'],
        ],
      );
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
