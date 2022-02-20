<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\ClassAnnotation;
use DrupalCodeBuilder\Utility\InsertArray;
use DrupalCodeBuilder\Utility\NestedArray;
use CaseConverter\CaseString;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\OptionDefinition;

/**
 * Base generator entity types.
 */
abstract class EntityTypeBase extends PHPClassFile {

  use NameFormattingTrait;

  /**
   * The class to use for the entity class annotation.
   *
   * Child classes must override this.
   */
  protected $annotationClassName = '';

  /**
   * The ordering to apply to annotation top-level properties.
   *
   * Child classes must override this.
   *
   * It is permissible for:
   * - a generated annotation to not have a property that is given in this
   *   array.
   * - a generated annotation to have a property that is not given in this
   *   array.
   */
  protected $annotationTopLevelOrder = [];

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $properties = [
      'entity_type_id' => PropertyDefinition::create('string')
        ->setLabel('Entity type ID')
        ->setDescription("The identifier of the entity type.")
        ->setRequired(TRUE)
        ->setValidators('machine_name'),
        // TODO: validation? static::ID_MAX_LENGTH
      'entity_type_label' => PropertyDefinition::create('string')
        ->setLabel('Entity type label')
        ->setDescription("The human-readable label for the entity type.")
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("machineToLabel(get('..:entity_type_id'))")
            ->setDependencies('..:entity_type_id')
        ),
      'plain_class_name' => PropertyDefinition::create('string')
        ->setLabel('Entity class name')
        ->setDescription("The short class name of the entity.")
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("machineToClass(get('..:entity_type_id'))")
            ->setDependencies('..:entity_type_id')
        ),
      'functionality' => PropertyDefinition::create('string')
        // Presets provided by child classes.
        ->setLabel('Entity functionality')
        ->setDescription("Characteristics of the entity type that provide different kinds of functionality.")
        ->setMultiple(TRUE),
      // UI property. This forces the route provider which in turn forces other
      // things, and also sets:
      // - the links annotation properties
      // - the menu links
      // - the menu actions
      // - the menu tasks
      'entity_ui' => PropertyDefinition::create('string')
        ->setLabel('Provide UI')
        ->setDescription("Whether this entity has a UI. If selected, this will override the route provider, default form, list builder, and admin permission options if they are left empty.")
        ->setOptionsArray([
          // An empty value means processing won't be called.
          '' => 'No UI',
          'default' => 'Default UI',
          'admin' => 'Admin UI',
        ])
        ->setProcessing(function(DataItem $component_data) {
          $entity_data = $component_data->getParent();
          if ($entity_data->handler_route_provider->isEmpty() ||
            $entity_data->handler_route_provider->value != $component_data->value) {
            $entity_data->handler_route_provider = $component_data->value;
          }

          $entity_data->handler_form_default = 'custom';

          // The UI option sets the 'delete-form' link template, so we need to
          // set a form to handler it. The core form suffices.
          $entity_data->handler_form_delete = 'core';

          $entity_data->handler_list_builder = 'custom';
        }),
      'interface_parents' => PropertyDefinition::create('string')
        ->setLabel('Interface parents')
        ->setDescription("The interfaces the entity interface inherits from.")
        ->setMultiple(TRUE)
        ->setInternal(TRUE),
      'entity_keys' => PropertyDefinition::create('mapping')
        // Child classes set the default value.
        ->setLabel('Entity keys')
        ->setInternal(TRUE),
      'entity_interface_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("get('..:plain_class_name') ~ 'Interface'")
            ->setDependencies('..:plain_class_name')
        ),
    ];

    // Create the property for the handler.
    foreach (static::getHandlerTypes() as $key => $handler_type_info) {
      $handler_type_property_name = "handler_{$key}";

      switch ($handler_type_info['mode']) {
        case 'core_default':
          // Handler that core fills in if not specified, e.g. the access
          // handler.
          $handler_property = PropertyDefinition::create('boolean')
            ->setLabel("Custom {$handler_type_info['label']} handler");
          break;

        case 'core_none':
          // Handler that core leaves empty if not specified, e.g. the list
          // builder handler.
          $handler_property = PropertyDefinition::create('string')
            ->setLabel(ucfirst("{$handler_type_info['label']} handler"))
            ->setOptionsArray([
              'none' => 'Do not use a handler',
              'core' => 'Use the core handler class',
              'custom' => 'Provide a custom handler class',
            ]);
          break;

        case 'custom_default':
          $default_handler_type = $handler_type_info['default_type'];
          $handler_property = PropertyDefinition::create('string')
            ->setLabel(ucfirst("{$handler_type_info['label']} handler"))
            ->setOptionsArray([
              'none' => 'Do not use a handler',
              'default' => "Use the '{$default_handler_type}' handler class (forces '{$default_handler_type}' to use the default if not set)",
              'custom' => "Provide a custom handler class (forces '{$default_handler_type}' to use the default if not set)",
            ])
            // Force the default type to at least be specified if it isn't
            // already.
            // TODO: this assumes the mode of the default handler type is
            // 'core_none'.
            ->setProcessing(function(DataItem $component_data) use ($default_handler_type) {
              if ($component_data->isEmpty() || $component_data->value == 'none') {
                // Nothing to do; this isn't set to use anything.
                return;
              }

              $default_handler_key = "handler_{$default_handler_type}";

              if ($component_data->getParent()->{$default_handler_key}->isEmpty() || $component_data->getParent()->{$default_handler_key}->value == 'none') {
                $component_data->getParent()->{$default_handler_key} = 'core';
              }
            });
          break;
      }

      // Allow the handler type to provide a UI description.
      if (isset($handler_type_info['description'])) {
        $handler_property->setDescription($handler_type_info['description']);
      }

      // Add extra options specific to the handler type.
      if (isset($handler_type_info['options'])) {
        foreach ($handler_type_info['options'] as $option_value => $option_label) {
          $handler_property->addOption(new OptionDefinition($option_value, $option_label));
        }
      }

      // FUCK.
      // $handler_property['parent_class_name'] = $handler_type_info['base_class'];

      $properties[$handler_type_property_name] = $handler_property;
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
    $properties['handler_route_provider']->setProcessing(function(DataItem $component_data) {
      $entity_data = $component_data->getParent();
      if (!$entity_data->handler_route_provider->isEmpty() && $entity_data->handler_route_provider->value != 'none') {
        $entity_data->admin_permission = TRUE;

        if ($entity_data->handler_form_default->value != 'custom') {
          $entity_data->handler_form_default = 'core';
        }

        if ($entity_data->handler_list_builder->value != 'custom') {
          $entity_data->handler_list_builder = 'core';
        }
      }
    });

    // Admin permission.
    $properties['admin_permission'] = PropertyDefinition::create('boolean')
      ->setLabel('Admin permission')
      ->setDescription("Whether to provide an admin permission. (Always set if a route provider handler is used.)");

    $properties['admin_permission_name'] = PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("'administer ' ~ get('..:entity_type_id') ~ ' entities'")
            ->setDependencies('..:entity_type_id')
        );

    $definition = parent::getPropertyDefinition();

    // Put the parent definitions after ours.
    $properties += $definition->getProperties();
    $definition->setProperties($properties);

    // Make one of the basic class name properties internal.
    $definition->getProperty('relative_class_name')->setInternal(TRUE);

    // Override some defaults.
    // Put the class in the 'Entity' relative namespace.
    $definition->getProperty('relative_namespace')->getDefault()
      ->setLiteral('Entity');

    $definition->getProperty('class_docblock_lines')
      ->setDefault(
        DefaultDefinition::create()
          // Expression Language lets us define arrays, which is nice.
          ->setExpression("['Provides the ' ~ get('..:entity_type_label') ~ ' entity.']")
      );

    $definition->getProperty('interfaces')->setDefault(
      DefaultDefinition::create()
        // Expression Language lets us define arrays, which is nice.
        // TODO: why do we have the separate entity_interface_name??
        ->setExpression("[get('..:entity_interface_name')]")
    );

    return $definition;
  }

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
   *   - 'options': An array of additional options for the handler property.
   *     These are added to the options provided by the mode.
   *   - 'property_path': (optional) The path to set this into the annotation
   *      beneath the 'handlers' key. Only required if this is not simply the
   *      handler type key.
   *   - 'class_name_suffix': (optional) Specifies the suffix to be added to the
   *     entity type when forming the class name for this handler type.
   *   - 'class_namespace': (optional) Array giving the namespace of the handler
   *     class beneath the module's namespace. Defaults to giving the handler
   *     a namespace of Entity\Handler.
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
          // Overwrite the label for the 'core' option which the mode provides.
          // This is OK because addOption() replaces an existing option.
          'core' => 'Default core route provider',
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
        'class_namespace' => 'Form',
        'mode' => 'core_none',
        // base_class for all form handlers is set by child classes.
      ],
      'form_add' => [
        'label' => 'add form',
        'description' => "The entity form class for the 'add' operation.",
        'component_type' => 'EntityForm',
        'property_path' => ['form', 'add'],
        'class_name_suffix' => 'AddForm',
        'class_namespace' => 'Form',
        'mode' => 'custom_default',
        'default_type' => 'form_default',
      ],
      'form_edit' => [
        'label' => 'edit form',
        'description' => "The entity form class for the 'edit' operation.",
        'component_type' => 'EntityForm',
        'property_path' => ['form', 'edit'],
        'class_name_suffix' => 'EditForm',
        'class_namespace' => 'Form',
        'mode' => 'custom_default',
        'default_type' => 'form_default',
      ],
      'form_delete' => [
        'label' => 'delete form',
        'description' => "The entity form class for the 'delete' operation.",
        'class_name_suffix' => 'DeleteForm',
        'class_namespace' => 'Form',
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
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $components["entity_type_{$this->component_data['entity_type_id']}_interface"] = [
      'component_type' => 'PHPInterfaceFile',
      'relative_class_name' => 'Entity\\' . $this->component_data['entity_interface_name'],
      'docblock_first_line' => "Interface for {$this->component_data['entity_type_label']} entities.",
      'parent_interface_names' => $this->component_data->interface_parents->values(),
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
        'plain_class_name' =>  $this->makeShortHandlerClassName($key, $handler_type_info),
        'relative_namespace' =>
          $handler_type_info['class_namespace']
          ??
          $this->component_data->getItem('module:configuration:entity_handler_namespace')->value,
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
      $class_name = '\Drupal\\'
        . $this->component_data->root_component_name->value
        . '\\'
        . $components['handler_form_default']['relative_namespace']
        . '\\'
        . $components['handler_form_default']['plain_class_name'];

      foreach (['handler_form_add', 'handler_form_edit'] as $key) {
        if (isset($components[$key])) {
          $components[$key]['parent_class_name'] = $class_name;
        }
      }
    }

    // Admin permission.
    if ($this->component_data['admin_permission']) {
      $admin_permission_name = $this->component_data['admin_permission_name'];

      $components[$admin_permission_name] = [
        'component_type' => 'Permission',
        'permission' => $admin_permission_name,
        'title' => 'Administer ' . CaseString::snake($this->component_data->entity_type_id->value)->sentence() . ' entities',
      ];
    }

    // Add menu plugins for the entity type if the UI option is set.
    if ($this->component_data['entity_ui']) {
      // Add the 'add' button to appear on the collection route.
      $components['collection_menu_action' . $this->component_data['entity_type_id']] = [
        'component_type' => 'Plugin',
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

    $annotation_data = $this->getAnnotationData();

    // Order the annotation data by the ordering array.
    $annotation_data_ordered = array_fill_keys($this->annotationTopLevelOrder, NULL);
    $annotation_data_ordered = array_replace($annotation_data_ordered, $annotation_data);
    // Filter the annotation data to remove any keys which are NULL; that is,
    // which are still in the state that the array fill put them in and that
    // have not had any actual data in. AFAIK annotation values are never
    // actually NULL, so this is ok.
    $annotation_data_ordered = array_filter($annotation_data_ordered, function($item) {
      return !is_null($item);
    });

    $annotation = ClassAnnotation::{$this->annotationClassName}($annotation_data_ordered);

    $docblock_lines = array_merge($docblock_lines, $annotation->render());

    return $docblock_lines;
  }

  /**
   * Gets the data for the annotation.
   *
   * @return array
   *   A data array suitable for passing to ClassAnnotation.
   */
  protected function getAnnotationData() {
    $annotation_data = [
      'id' => $this->component_data['entity_type_id'],
      'label' => ClassAnnotation::Translation($this->component_data['entity_type_label']),
      'label_collection' => ClassAnnotation::Translation($this->component_data['entity_type_label'] . 's'),
      'label_singular' => ClassAnnotation::Translation(strtolower($this->component_data['entity_type_label'])),
      'label_plural' => ClassAnnotation::Translation(strtolower($this->component_data['entity_type_label']) . 's'),
      'label_count' => ClassAnnotation::PluralTranslation([
        'singular' => "@count " . strtolower($this->component_data['entity_type_label']),
        'plural' => "@count " . strtolower($this->component_data['entity_type_label']) . 's',
      ]),
      'entity_keys' => $this->component_data['entity_keys'],
    ];

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
      $annotation_data['handlers'] = $handler_data;
    }

    if ($this->component_data['admin_permission']) {
      $annotation_data['admin_permission'] = $this->component_data['admin_permission_name'];
    }

    return $annotation_data;
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
      $short_class_name = $this->component_data['plain_class_name'] .  $handler_type_info['class_name_suffix'];
    }
    else {
      $short_class_name = $this->component_data['plain_class_name'] .  CaseString::snake($handler_type_key)->pascal();
    }

    return $short_class_name;
  }

  /**
   * Helper to create the relative namespaced class name for a handler.
   *
   * @param string $key
   *   The handler type key as defined by getHandlerTypes().
   * @param array $handler_type_info
   *   The handler type info as defined by getHandlerTypes().
   *
   * @return string[]
   *   The handler class name, qualified relative to the module, as an array
   *   of name pieces.
   */
  protected function getRelativeHandlerClassNamePieces($handler_type_key, $handler_type_info) {
    $handler_short_class_name = $this->makeShortHandlerClassName($handler_type_key, $handler_type_info);
    if (isset($handler_type_info['class_namespace'])) {
      $handler_relative_class_name = [$handler_type_info['class_namespace']];
      $handler_relative_class_name[] = $handler_short_class_name;
    }
    else {
      $handler_relative_class_name = [
        $this->component_data->getItem('module:configuration:entity_handler_namespace')->value,
        $handler_short_class_name
      ];

      // The config value might be an empty string, so filter.
      $handler_relative_class_name = array_filter($handler_relative_class_name);
    }

    return $handler_relative_class_name;
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
    $relative_name_pieces = $this->getRelativeHandlerClassNamePieces($handler_type_key, $handler_type_info);
    $name_pieces = array_merge([
      'Drupal',
      $this->component_data->root_component_name->value,
    ], $relative_name_pieces);


    $handler_class_name = static::makeQualifiedClassName($name_pieces);

    return $handler_class_name;
  }

}
