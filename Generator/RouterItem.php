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
              // TODO: shorthand for setting defaults! TOO LONG!
              'routing_value' => PropertyDefinition::create('string')->setLiteralDefault('TRUE')
            ]),
          'permission' => VariantDefinition::create()
            ->setLabel('Permission')
            ->setProperties([
              'routing_value' => PropertyDefinition::create('string')
                ->setLiteralDefault('TODO: set permission machine name')
            ]),
          'role' => VariantDefinition::create()
            ->setLabel('Role')
            ->setProperties([
              'routing_value' => PropertyDefinition::create('string')
                ->setLiteralDefault('authenticated')
            ]),
          'entity_access' => VariantDefinition::create()
            ->setLabel('Entity access')
            ->setProperties([
              'routing_value' => PropertyDefinition::create('string')
                ->setLiteralDefault('ENTITY_TYPE.OPERATION')
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
    $trimmed_path = ltrim($component_data['path'], '/');

    // Get the module name rather than using the token, to avoid the
    // property name getting quoted.
    $module = $component_data['root_component_name'];
    $route_name = $module . '.' . str_replace('/', '.', $trimmed_path);
    return $route_name;

    // WTF?
    $function_name = $data_item->getParent()->function_name->value;
    return "public function {$function_name}(array £form, \Drupal\Core\Form\FormStateInterface £form_state)";
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

    // TODO!
    if (!empty($this->component_data['menu_link'][0])) {
      // Strip off the module name prefix from the route name to make the plugin
      // name, as the plugin generator will add it back again.
      $plugin_name = $this->component_data['route_name'];
      $plugin_name = substr($plugin_name, strlen($this->component_data['root_component_name']) + 1);

      $components['menu_link'] = array(
        'component_type' => 'PluginYAML',
        'plugin_type' => 'menu.link',
        'plugin_name' => $plugin_name,
        'plugin_properties' => [
          'title' => $this->component_data['menu_link'][0]['title'],
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



    // // Set the YAML values that come from component data with an address.
    // $yaml_data_component_properties = [
    //   'controller_type',
    //   'access_type',
    // ];
    // foreach ($yaml_data_component_properties as $component_property_name) {
    //   if (empty($this->component_data[$component_property_name])) {
    //     continue;
    //   }

    //   // The value for the property is the YAML key without the initial '_'; the
    //   // YAML value is given in a companion property called PROPERTY_value; the
    //   // 'yaml_address' attribute in the property's info defines where in the
    //   // YAML structure the key and value should be inserted.
    //   $yaml_key = '_' . $this->component_data[$component_property_name];

    //   $yaml_value = $this->component_data["{$component_property_name}_value"];

    //   // Bit of a hack: instantiated generators don't have access to their
    //   // processed data info.
    //   $property_info = static::componentDataDefinition()[$component_property_name];
    //   $property_address = $property_info['yaml_address'];
    //   $property_address[] = $yaml_key;

    //   NestedArray::setValue($route_yaml, $property_address, $yaml_value);
    // }

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
