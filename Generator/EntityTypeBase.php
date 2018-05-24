<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\AnnotationTrait;
use DrupalCodeBuilder\Utility\InsertArray;
use DrupalCodeBuilder\Utility\NestedArray;
use CaseConverter\CaseString;

/**
 * Base generator entity types.
 */
abstract class EntityTypeBase extends PHPClassFile {

  use NameFormattingTrait;

  use AnnotationTrait;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = [
      'entity_type_id' => [
        'label' => 'Entity type ID',
        'description' => "The identifier of the entity type.",
        'required' => TRUE,
        // TODO: validation? static::ID_MAX_LENGTH
      ],
      'entity_type_label' => [
        'label' => 'Entity type label',
        'description' => "The human-readable label for the entity type.",
        'process_default' => TRUE,
        'default' => function($component_data) {
          $entity_type_id = $component_data['entity_type_id'];

          // Convert the entity type to camel case. E.g., 'my_entity_type'
          //  becomes 'My Entity Type'.
          return CaseString::snake($entity_type_id)->title();
        },
      ],
      'entity_class_name' => [
        'label' => 'Entity class name',
        'description' => "The short class name of the entity.",
        'process_default' => TRUE,
        'default' => function($component_data) {
          $entity_type_id = $component_data['entity_type_id'];
          return CaseString::snake($entity_type_id)->pascal();
        },
      ],
      // UI property. This forces the route provider which in turn forces other
      // things, and also sets:
      // - the links annotation properties
      // - the menu links
      // - the menu actions
      // - the menu tasks
      'entity_ui' => [
        'label' => 'Provide UI',
        'description' => "Whether this entity has a UI. If selected, this will override the route provider, default form, list builder, and admin permission options if they are left empty.",
        'options' => [
          // An empty value means processing won't be called.
          '' => 'No UI',
          'default' => 'Default UI',
          'admin' => 'Admin UI',
        ],
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          if (!isset($component_data['handler_route_provider']) ||
            $component_data['handler_route_provider'] != $value) {
            $component_data['handler_route_provider'] = $value;
          }

          $component_data['handler_form_default'] = 'custom';

          // The UI option sets the 'delete-form' link template, so we need to
          // set a form to handler it. The core form suffices.
          $component_data['handler_form_delete'] = 'core';

          $component_data['handler_list_builder'] = 'custom';
        },
      ],
      'interface_parents' => [
        'label' => 'Interface parents',
        'description' => "The interfaces the entity interface inherits from.",
        'format' => 'array',
        // Data for the options and processing callbacks.
        // Bit of a hack, as we need full class names internally, but they're
        // unwieldy in the UI.
        '_long_options' => static::interfaceParents(),
        'options' => function(&$property_info) {
          // Trim the namespace off the options, for UIs that show the keys,
          // such as Drush.
          $options = [];
          foreach ($property_info['_long_options'] as $key => $data) {
            $options[$key] = $data['label'];
          }
          return $options;
        },
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          // Clear out the property value.
          $component_data[$property_name] = [];

          // Replace it with the full interface.
          foreach ($value as $value_item) {
            $component_data[$property_name][] = $property_info['_long_options'][$value_item]['interface'];
          }
        },
        // TODO: methods from this in the entity class!
        // TODO: use corresponding traits, eg EntityChangedTrait;
      ],
      'entity_keys' => [
        'label' => 'Entity keys',
        'computed' => TRUE,
        // Child classes set the default callback.
      ],
      'entity_interface_name' => [
        'label' => 'Interface',
        'computed' => TRUE,
        'default' => function($component_data) {
          return $component_data['entity_class_name'] . 'Interface';
        },
      ],
    ];

    // Create the property for the handler.
    foreach (static::getHandlerTypes() as $key => $handler_type_info) {
      $handler_type_property_name = "handler_{$key}";

      switch ($handler_type_info['mode']) {
        case 'core_default':
          // Handler that core fills in if not specified, e.g. the access
          // handler.
          $handler_property = [
            'format' => 'boolean',
            'label' => "Custom {$handler_type_info['label']} handler",
          ];
          break;

        case 'core_none':
          // Handler that core leaves empty if not specified, e.g. the list
          // builder handler.
          $handler_property = [
            'format' => 'string',
            'label' => ucfirst("{$handler_type_info['label']} handler"),
            'options' => [
              'none' => 'Do not use a handler',
              'core' => 'Use the core handler class',
              'custom' => 'Provide a custom handler class',
            ],
          ];
          break;

        case 'custom_default':
          $default_handler_type = $handler_type_info['default_type'];
          $handler_property = [
            'label' => ucfirst("{$handler_type_info['label']} handler"),
            'format' => 'string',
            'options' => [
              'none' => 'Do not use a handler',
              'default' => "Use the '{$default_handler_type}' handler class (forces '{$default_handler_type}' to use the default if not set)",
              'custom' => "Provide a custom handler class (forces '{$default_handler_type}' to use the default if not set)",
            ],
            // Force the default type to at least be specified if it isn't
            // already.
            // TODO: this assumes the mode of the default handler type is
            // 'core_none'.
            'processing' => function($value, &$component_data, $property_name, &$property_info) use ($default_handler_type) {
              if (empty($component_data[$property_name]) || $component_data[$property_name] == 'none') {
                // Nothing to do; this isn't set to use anything.
                return;
              }

              $default_handler_key = "handler_{$default_handler_type}";

              if (empty($component_data[$default_handler_key]) || $component_data[$default_handler_key] == 'none') {
                $component_data[$default_handler_key] = 'core';
              }
            },
          ];

          break;
      }

      // Allow the handler type to provide a UI description.
      if (isset($handler_type_info['description'])) {
        $handler_property['description'] = $handler_type_info['description'];
      }

      // Add extra options specific to the handler type.
      if (isset($handler_type_info['options'])) {
        $handler_property['format'] = 'string';

        InsertArray::insertAfter($handler_property['options'], 'core', $handler_type_info['options']);
        unset($handler_property['options']['core']);
      }

      $handler_property['handler_label'] = $handler_type_info['label'];
      $handler_property['parent_class_name'] = $handler_type_info['base_class'];

      $data_definition[$handler_type_property_name] = $handler_property;
    }

    // If there is a route provider, force the following:
    // - admin_permission, because
    //  DefaultHtmlRouteProvider::getCollectionRoute() checks for it.
    // - list builder handler, because
    //  DefaultHtmlRouteProvider::getCollectionRoute() checks for it.
    // - default form handler, because all the form routes assume that form
    //   handlers exist and crash without one.
    // This can't be done in the processing callback for those properties, as
    // processing callback is not applied to an empty property.
    $data_definition['handler_route_provider']['processing'] = function($value, &$component_data, $property_name, &$property_info) {
      if (!empty($component_data['handler_route_provider']) && $component_data['handler_route_provider'] != 'none') {
        $component_data['admin_permission'] = TRUE;

        if (empty($component_data['handler_form_default']) || $component_data['handler_form_default'] == 'none') {
          $component_data['handler_form_default'] = 'core';
        }

        if (empty($component_data['handler_list_builder']) || $component_data['handler_list_builder'] == 'none') {
          $component_data['handler_list_builder'] = 'core';
        }
      }
    };

    // Admin permission.
    $data_definition['admin_permission'] = [
      'label' => 'Admin permission',
      'description' => "Whether to provide an admin permission. (Always set if a route provider handler is used.)",
      'format' => 'boolean',
    ];
    $data_definition['admin_permission_name'] = [
      'label' => 'Admin permission name',
      'computed' => TRUE,
      'default' => function ($component_data) {
        if (!empty($component_data['admin_permission'])) {
          $entity_type_id = $component_data['entity_type_id'];
          // TODO: add a lower() to case converter!
          return 'administer ' . strtolower(CaseString::snake($entity_type_id)->sentence()) . 's';
        }
      },
    ];

    // Put the parent definitions after ours.
    $data_definition += parent::componentDataDefinition();

    // Override some parent definitions to provide computed defaults.
    $data_definition['relative_class_name']['default'] = function ($component_data) {
      return [
        'Entity',
        $component_data['entity_class_name'],
      ];
    };
    $data_definition['docblock_first_line']['default'] = function ($component_data) {
      return "Provides the {$component_data['entity_type_label']} entity.";
    };

    $data_definition['interfaces']['computed'] = TRUE;
    $data_definition['interfaces']['default'] = function ($component_data) {
      return [
        $component_data['entity_interface_name'],
      ];
    };

    return $data_definition;
  }

  /**
   * Provides options for the interface_parents property's _long_options.
   *
   * @var array
   *   An array whose keys are an arbitrary option key, and whose values are
   *   arrays with:
   *   - label: A description of the interface for the UI.
   *   - interface: The fully-qualified interface name.
   */
  abstract protected static function interfaceParents();

  /**
   * Provides the basic interface that the entity type interface must inherit.
   *
   * @var string
   *   The fully-qualified interface name.
   */
  abstract protected function interfaceBasicParent();

  /**
   * Lists the available handler types.
   *
   * @return array
   *   An array whose keys are the handler type, e.g. 'access', and whose values
   *   are arrays containing:
   *   - 'label': The label of the handler type, in lowercase.
   *   - 'description': (optional) The description text for the component
   *     property.
   *   - 'component_type': (optional) The component type to use. Defaults to
   *      EntityHandler.
   *   - 'handler_properties': (optional) An array of property names and values
   *      to be set verbatim on the requested component.
   *   - 'base_class': The base class for handlers of this type.
   *   - 'mode': Defines how the core entity system handles an entity not
   *     defining a handler of this type. One of:
   *      - 'core_default': Core fills in a default handler: the option is
   *        whether to specify nothing (and get the default), or create a custom
   *        handler.
   *      - 'core_none': No handler is provided: the option is whether to have
   *        nothing, specify a generic core handler, or create a custom handler.
   *      - 'custom_default': No handler is provided, but handler for another
   *        type can be used. The option is whether to use that, or create a
   *        custom handler. The 'default_type' property must also be given.
   *   - 'property_path': (optional) The path to set this into the annotation
   *      beneath the 'handlers' key. Only required if this is not simply the
   *      handler type key.
   *   - 'class_name_suffix': (optional) Specifies the suffix to be added to the
   *     entity type when forming the class name for this handler type.
   */
  protected static function getHandlerTypes() {
    return [
      'access' => [
        'label' => 'access',
        'description' => "Controls access to entities of this type.",
        'mode' => 'core_default',
        'base_class' => '\Drupal\Core\Entity\EntityAccessControlHandler',
      ],
      'route_provider' => [
        'label' => 'route provider',
        'description' => 'Provides a UI for the entity type. If set, forces the admin permission, list builder, and default form handler.',
        'property_path' => ['route_provider', 'html'],
        'mode' => 'core_none',
        'base_class' => '\Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider',
        'options' => [
          'default' => 'Default core route provider',
          'admin' => 'Admin route provider',
        ],
        'options_classes' => [
          'default' => '\Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider',
          'admin' => '\Drupal\Core\Entity\Routing\AdminHtmlRouteProvider',
        ],
      ],
      'form_default' => [
        'label' => 'default form',
        'description' => 'The entity form class to use if no form class is specified for an operation. Always set if a route provider handler is used.',
        'component_type' => 'EntityForm',
        'property_path' => ['form', 'default'],
        'class_name_suffix' => 'Form',
        'mode' => 'core_none',
        // base_class for all form handlers is set by child classes.
      ],
      'form_add' => [
        'label' => 'add form',
        'description' => "The entity form class for the 'add' operation.",
        'component_type' => 'EntityForm',
        'property_path' => ['form', 'add'],
        'class_name_suffix' => 'AddForm',
        'mode' => 'custom_default',
        'default_type' => 'form_default',
      ],
      'form_edit' => [
        'label' => 'edit form',
        'description' => "The entity form class for the 'edit' operation.",
        'component_type' => 'EntityForm',
        'property_path' => ['form', 'edit'],
        'class_name_suffix' => 'EditForm',
        'mode' => 'custom_default',
        'default_type' => 'form_default',
      ],
      'form_delete' => [
        'label' => 'delete form',
        'description' => "The entity form class for the 'delete' operation.",
        'class_name_suffix' => 'DeleteForm',
        'property_path' => ['form', 'delete'],
        'mode' => 'core_none',
      ],
      'storage' => [
        'label' => 'storage',
        'description' => "Defines how the entities are stored. This is typically customised to provide methods with custom queries for entities.",
        'mode' => 'core_default',
        'base_class' => '\Drupal\Core\Entity\EntityStorageBase',
      ],
      'list_builder' => [
        'label' => 'list builder',
        'description' => "Provides the admin listing of entities of this type.",
        'component_type' => 'EntityListBuilder',
        'mode' => 'core_none',
        'base_class' => '\Drupal\Core\Entity\EntityListBuilder',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    //dump($this->component_data);

    $components["entity_type_{$this->component_data['entity_type_id']}_interface"] = [
      'component_type' => 'PHPInterfaceFile',
      'relative_class_name' => [
        'Entity',
        $this->component_data['entity_interface_name'],
      ],
      'docblock_first_line' => "Interface for {$this->component_data['entity_type_label']} entities.",
      'parent_interface_names' => array_merge([
          $this->interfaceBasicParent(),
        ],
        $this->component_data['interface_parents']
      ),
    ];

    // Handlers.
    foreach (static::getHandlerTypes() as $key => $handler_type_info) {
      $data_key = "handler_{$key}";

      // Skip if nothing in the data.
      if (empty($this->component_data[$data_key])) {
        continue;
      }

      // For handlers that core doesn't fill in, only provide a class if
      // for the 'custom' option.
      if ($handler_type_info['mode'] == 'core_none') {
        if ($this->component_data[$data_key] != 'custom') {
          continue;
        }
      }
      if ($handler_type_info['mode'] == 'custom_default') {
        if ($this->component_data[$data_key] != 'custom') {
          continue;
        }
      }

      $components[$data_key] = [
        'component_type' => $handler_type_info['component_type'] ?? 'EntityHandler',
        'handler_type' => $key,
        'handler_label' => $handler_type_info['label'],
        'parent_class_name' => $handler_type_info['base_class'],
        'relative_class_name' => [
          'Entity',
          'Handler',
          $this->makeShortHandlerClassName($key, $handler_type_info),
        ],
      ];

      if (isset($handler_type_info['handler_properties'])) {
        $components[$data_key] += $handler_type_info['handler_properties'];
      }
    }

    // Atrocious hack!
    // The 'add' and 'edit' form handlers should inherit from the 'default'
    // form handler if that is present.
    if (isset($components['handler_form_default'])) {
      // Hackily make the full class name here.
      $class_name_pieces = array_merge([
        'Drupal',
        '%module',
      ], $components['handler_form_default']['relative_class_name']);
      $class_name = '\\' . self::makeQualifiedClassName($class_name_pieces);

      foreach (['handler_form_add', 'handler_form_edit'] as $key) {
        if (isset($components[$key])) {
          $components[$key]['parent_class_name'] = $class_name;
        }
      }
    }

    // Admin permission.
    if ($this->component_data['admin_permission_name']) {
      $admin_permission_name = $this->component_data['admin_permission_name'];

      $components[$admin_permission_name] = array(
        'component_type' => 'Permission',
        'permission' => $admin_permission_name,
      );
    }

    // Add menu plugins for the entity type if the UI option is set.
    if (!empty($this->component_data['entity_ui'])) {
      // Add the 'add' button to appear on the collection route.
      $components['collection_menu_action' . $this->component_data['entity_type_id']] = [
        'component_type' => 'PluginYAML',
        'plugin_type' => 'menu.local_action',
        'prefix_name' => FALSE,
        'plugin_name' => "entity.{$this->component_data['entity_type_id']}.add",
        'plugin_properties' => [
          'title' => 'Add ' . $this->component_data['entity_type_label'],
          'route_name' => "entity.{$this->component_data['entity_type_id']}.add_form",
          // Media module sets 10 for its tab; go further along.
          'weight' => 15,
          'appears_on' => [
             "entity.{$this->component_data['entity_type_id']}.collection",
          ],
        ],
      ];
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function getClassDocBlockLines() {
    //dump($this->component_data);
    $docblock_lines = parent::getClassDocBlockLines();
    $docblock_lines[] = '';

    $annotation = $this->getAnnotationData();

    $docblock_lines = array_merge($docblock_lines, $this->renderAnnnotation($annotation));

    return $docblock_lines;
  }

  /**
   * Gets the data for the annotation.
   *
   * @return array
   *   A data array suitable for renderAnnnotation().
   */
  protected function getAnnotationData() {
    $annotation = [
      '#class' => 'CHILD CLASS SETS THIS',
      '#data' => [
        'id' => $this->component_data['entity_type_id'],
        'label' => [
          '#class' => 'Translation',
          '#data' => $this->component_data['entity_type_label'],
        ],
      ],
    ];

    $annotation['#data'] += [
      'entity_keys' => $this->component_data['entity_keys'],
    ];

    $annotation_data['label_collection'] = [
      '#class' => 'Translation',
      '#data' => $this->component_data['entity_type_label'] . 's',
    ];
    $annotation_data['label_singular'] = [
      '#class' => 'Translation',
      '#data' => strtolower($this->component_data['entity_type_label']),
    ];
    $annotation_data['label_plural'] = [
      '#class' => 'Translation',
      '#data' => strtolower($this->component_data['entity_type_label']) . 's',
    ];
    $annotation_data['label_count'] = [
      '#class' => 'PluralTranslation',
      '#data' => [
        'singular' => "@count " . strtolower($this->component_data['entity_type_label']),
        'plural' => "@count " . strtolower($this->component_data['entity_type_label']) . 's',
      ],
    ];
    // TODO: clean up this array assignment!
    $annotation['#data'] += $annotation_data;

    // Handlers.
    $handler_data = [];
    // Keep track of all the handler classes in a flat array keyed by the type
    // key so we don't have to work with the nested array for references to
    // other handlers.
    $handler_classes = [];
    foreach (static::getHandlerTypes() as $key => $handler_type_info) {
      $data_key = "handler_{$key}";

      // Skip if nothing in the data.
      if (empty($this->component_data[$data_key])) {
        continue;
      }

      // Strict comparison, as could be TRUE.
      if ($this->component_data[$data_key] === 'none') {
        continue;
      }

      $option_value = $this->component_data[$data_key];

      switch ($handler_type_info['mode']) {
        case 'core_default':
          // The FALSE case has been eliminated already, so must be TRUE.
          $handler_class = $this->makeQualifiedHandlerClassName($key, $handler_type_info);
          break;

        case 'core_none':
          if ($option_value == 'core') {
            $handler_class = substr($handler_type_info['base_class'], 1);
          }
          elseif ($option_value == 'custom') {
            $handler_class = $this->makeQualifiedHandlerClassName($key, $handler_type_info);
          }
          else {
            // Another option, specific to the handler type.
            $handler_class = substr($handler_type_info['options_classes'][$option_value], 1);
          }
          break;

        case 'custom_default':
          if ($option_value == 'default') {
            // Use a handler that's from another type.
            $handler_class = $handler_classes[$handler_type_info['default_type']];
          }
          else {
            $handler_class = $this->makeQualifiedHandlerClassName($key, $handler_type_info);
          }
          break;
      }

      $handler_classes[$key] = $handler_class;

      if (isset($handler_type_info['property_path'])) {
        NestedArray::setValue($handler_data, $handler_type_info['property_path'], $handler_class);
      }
      else {
        $handler_data[$key] = $handler_class;
      }
    }
    if ($handler_data) {
      $annotation['#data']['handlers'] = $handler_data;
    }

    if ($this->component_data['admin_permission_name']) {
      $annotation['#data']['admin_permission'] = $this->component_data['admin_permission_name'];
      $handler_class = substr($handler_class, 1);
    }

    return $annotation;
  }

  /**
   * Helper to create the short class name for a handler.
   *
   * @param string $key
   *   The handler type key as defined by getHandlerTypes().
   * @param array $handler_type_info
   *   The handler type info as defined by getHandlerTypes().
   *
   * @return string
   *   The fully-qualified handler class name.
   */
  protected function makeShortHandlerClassName($handler_type_key, $handler_type_info) {
    if (isset($handler_type_info['class_name_suffix'])) {
      $short_class_name = $this->component_data['entity_class_name'] .  $handler_type_info['class_name_suffix'];
    }
    else {
      $short_class_name = $this->component_data['entity_class_name'] .  CaseString::snake($handler_type_key)->pascal();
    }

    return $short_class_name;
  }

  /**
   * Helper to create the full class name for a handler.
   *
   * @param string $key
   *   The handler type key as defined by getHandlerTypes().
   * @param array $handler_type_info
   *   The handler type info as defined by getHandlerTypes().
   *
   * @return string
   *   The fully-qualified handler class name.
   */
  protected function makeQualifiedHandlerClassName($handler_type_key, $handler_type_info) {
    $handler_class_name = static::makeQualifiedClassName([
      // TODO: DRY, with EntityHandler class.
      'Drupal',
      '%module',
      'Entity',
      'Handler',
      $this->makeShortHandlerClassName($handler_type_key, $handler_type_info),
    ]);

    return $handler_class_name;
  }

}
