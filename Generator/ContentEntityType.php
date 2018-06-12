<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\FormattingTrait\AnnotationTrait;
use DrupalCodeBuilder\Generator\Render\FluentMethodCall;
use DrupalCodeBuilder\Utility\InsertArray;
use CaseConverter\CaseString;

/**
 * Generator for a content entity type.
 */
class ContentEntityType extends EntityTypeBase {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition();

    // Set up the entity type functionality preset options.
    $data_definition['functionality']['presets'] = [
      'fieldable' => [
        'label' => 'Fieldable - allows custom fields',
        // TODO: Not supported yet; will work on 3.3.x.
        'description' => "Whether this entity type allows custom fields.",
        // No actual data, as the field_ui_base_route depends on whether this
        // is a bundle entity!
        // TODO: this would work if bundle entity is a subclass generator.
        'data' => [
        ],
      ],
      'revisionable' => [
        'label' => 'Revisionable - entities can have multiple revisions',
        'data' => [
          'force' => [
            'entity_keys' => [
              'value' => [
                'revision' => 'revision_id',
              ],
            ],
          ],
        ],
      ],
    ];
    $data_definition['functionality']['default'] = [
      'fieldable',
    ];

    $bundle_entity_properties = [
      'fieldable' => [
        'label' => 'Fieldable',
        'description' => "Whether this entity type allows custom fields.",
        'format' => 'boolean',
        'default' => TRUE,
      ],
      // '' TODO - characteristics preset
      // TODO: UI preset
      'revisionable' =>  [
        'label' => 'Revisionable',
        'description' => "Whether this entity type allows multiple revisions of a single entity.",
        'format' => 'boolean',
        'default' => TRUE, // ???
      ],
      'translatable' => [
        'label' => 'Translatable',
        'description' => "Whether this entity type allows translation.",
        'format' => 'boolean',
        'default' => TRUE,
      ],
      'bundle_entity' => [
        'label' => 'Bundle config entity type',
        'description' => "Creates a config entity type which provides the bundles for this entity type. "
          . "This is analogous to the Node Type entity type providing bundles for the Node entity type.",
        'format' => 'compound',
        'cardinality' => 1,
        'component' => 'ConfigEntityType',
        'default' => function($component_data) {
          return [
            0 => [
              // The bundle entity type ID defaults to CONTENT_TYPE_type.
              'entity_type_id' => $component_data['entity_type_id'] . '_type',
              'bundle_of_entity' => $component_data['entity_type_id'],
            ],
          ];
        },
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          // Fill in defaults if an item is requested.
          // Bit faffy, but needed for non-progressive UIs.
          if (isset($component_data['bundle_entity'][0]['entity_type_id'])) {
            $component_data['bundle_entity'][0]['bundle_of_entity'] = $component_data['entity_type_id'];
          }
        },
      ],
      'bundle_label' => [
        'computed' => TRUE,
        'default' => function($component_data) {
          if (isset($component_data['bundle_entity'][0]['entity_type_id'])) {
            // TODO: get the actual value of the entity_type_label property from
            // the bundle entity -- but this is proving rather labyrinthine...
            return CaseString::snake($component_data['bundle_entity'][0]['entity_type_id'])->title();
          }
        },
      ],
      'field_ui_base_route' => [
        'label' => 'Field UI base route',
        // TODO: expose to UI in 3.3 when we have dynamic defaults.
        // This will then be dependent on the 'fieldable' property.
        'computed' => TRUE,
        'default' => function($component_data) {
          if (empty($component_data['fieldable'])) {
            return NULL;
          }

          if (isset($component_data['bundle_entity'][0]['entity_type_id'])) {
            return 'entity.' . $component_data['bundle_entity'][0]['entity_type_id'] . '.edit_form';
          }
          else {
            return 'entity.' . $component_data['entity_type_id'] . '.admin_form';
          }
        },
      ],
    ];
    InsertArray::insertAfter($data_definition, 'entity_ui', $bundle_entity_properties);

    $base_fields_property = [
      'base_fields' => [
        'label' => 'Base fields',
        'description' => "The base fields for this content entity.",
        'format' => 'compound',
        // TODO: default, populated by things such as interface choice!
        'properties' => [
          'name' => [
            'label' => 'Field name',
            'required' => TRUE,
          ],
          'label' => [
            'label' => 'Field label',
            'default' => function($component_data) {
              $entity_type_id = $component_data['name'];
              return CaseString::snake($entity_type_id)->title();
            },
            'process_default' => TRUE,
          ],
          'type' => [
            'label' => 'Field type',
            'required' => TRUE,
            'options' => 'ReportFieldTypes:listFieldTypesOptions',
          ],
          // TODO: options for revisionable and translatable in 3.3.x once
          // we have conditional properties.
        ],
      ],
    ];
    InsertArray::insertAfter($data_definition, 'interface_parents', $base_fields_property);

    $bundle_entity_type_property = [
      'bundle_entity_type' => [
        'label' => 'Bundle entity type',
        'format' => 'string',
        'internal' => TRUE,
        'default' => function($component_data) {
          return $component_data['bundle_entity'][0]['entity_type_id'] ?? NULL;
        },
      ],
    ];
    // Bundle entity type must go before entity_keys, as we change the default
    // of that to depend on this.
    InsertArray::insertBefore($data_definition, 'entity_keys', $bundle_entity_type_property);

    $data_definition['parent_class_name']['default'] = '\Drupal\Core\Entity\ContentEntityBase';

    // Change the computed value for entity keys.
    // TODO: obsolete!
    $data_definition['entity_keys']['default'] = function($component_data) {
      $keys = [
        'id' => $component_data['entity_type_id'] . '_id',
        'label' => 'title',
        'uuid' => 'uuid',
      ];

      if (!empty($component_data['bundle_entity_type'])) {
        $keys['bundle'] = 'type';
      }

      if (!empty($component_data['revisionable'])) {
        $keys['revision'] = 'revision_id';
      }

      if (!empty($component_data['translatable'])) {
        $keys['langcode'] = 'langcode';
      }

      // TODO: convert these to draw from the interface parents definition.
      if (in_array('EntityOwnerInterface', $component_data['interface_parents'])) {
        $keys['uid'] = 'uid';
      }
      if (in_array('EntityPublishedInterface', $component_data['interface_parents'])) {
        $keys['published'] = 'status';
      }

      return $keys;
    };
    $data_definition['entity_keys']['processing'] = function($value, &$component_data, $property_name, &$property_info) {
      $value += [
        'id' => $component_data['entity_type_id'] . '_id',
        'label' => 'title',
        'uuid' => 'uuid',
      ];

      // TODO: all the following will move to the functionality preset.
      if (!empty($component_data['bundle_entity_type'])) {
        $value['bundle'] = 'type';
      }

      if (!empty($component_data['translatable'])) {
        $value['langcode'] = 'langcode';
      }

      // TODO: convert these to draw from the interface parents definition.
      if (in_array('EntityOwnerInterface', $component_data['interface_parents'])) {
        $value['uid'] = 'uid';
      }
      if (in_array('EntityPublishedInterface', $component_data['interface_parents'])) {
        $value['published'] = 'status';
      }

      // Apply a standard ordering to the keys.
      $entity_key_ordering = [
        'id',
        'label',
        'uuid',
        'bundle',
        'revision',
        'langcode',
        'uid',
        'published',
      ];

      // dump($component_data);
      dump($value);

      $ordered_value = [];
      foreach ($entity_key_ordering as $key) {
        if (isset($value[$key])) {
          $ordered_value[$key] = $value[$key];
        }
      }

      $component_data[$property_name] = $ordered_value;
    };

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getHandlerTypes() {
    $handler_types = parent::getHandlerTypes() + [
      'view_builder' => [
        'label' => 'view builder',
        'mode' => 'core_default',
        'base_class' => '\Drupal\Core\Entity\EntityViewBuilder',
      ],
      "views_data" => [
        'label' => 'Views data',
        // Core leaves empty if not specified.
        'mode' => 'core_none',
        'base_class' => '\Drupal\views\EntityViewsData',
      ],
      'translation' => [
        'label' => 'translation',
        // Technically, the content_translation module does this rather than the
        // entity system.
        'mode' => 'core_default',
        'base_class' => '\Drupal\content_translation\ContentTranslationHandler',
      ],
      // routing: several options...
    ];

    $handler_types['storage']['base_class'] = '\Drupal\Core\Entity\Sql\SqlContentEntityStorage';

    $handler_types['list_builder']['handler_properties']['entity_type_group'] = 'content';

    foreach (['form_default', 'form_add', 'form_edit'] as $form_handler_type) {
      $handler_types[$form_handler_type]['base_class'] = '\Drupal\Core\Entity\ContentEntityForm';

      $handler_types[$form_handler_type]['handler_properties'] = [
        // Content entity forms redirect to their view page.
        'redirect_link_template' => 'canonical',
      ];
    }

    $handler_types['form_delete']['base_class'] = '\Drupal\Core\Entity\ContentEntityDeleteForm';

    $storage_schema_type = [
      'storage_schema' => [
        'label' => 'storage schema',
        'mode' => 'core_default',
        'base_class' => '\Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema',
      ],
    ];
    InsertArray::insertAfter($handler_types, 'storage', $storage_schema_type);

    return $handler_types;
  }

  /**
   * {@inheritdoc}
   */
  protected static function interfaceParents() {
    return [
      'EntityChangedInterface' => [
        'label' => 'EntityChangedInterface, for entities that store a timestamp for their last change',
        'interface' => '\Drupal\Core\Entity\EntityChangedInterface',
        'trait' => '\Drupal\Core\Entity\EntityChangedTrait',
      ],
      'EntityOwnerInterface' => [
        'label' => 'EntityOwnerInterface, for entities that have an owner',
        'interface' => '\Drupal\user\EntityOwnerInterface',
      ],
      'EntityPublishedInterface' => [
        'label' => "EntityPublishedInterface, for entities that have a 'published' status",
        'interface' => '\Drupal\Core\Entity\EntityPublishedInterface',
        'trait' => "\Drupal\Core\Entity\EntityPublishedTrait",
        // Calls to bame in baseFieldDefinitions().
        'helper_methods' => [
          'publishedBaseFieldDefinitions',
        ],
        // Extra items for entity_keys annotation.
        // TODO: doesn't work yet!
        'entity_keys' => [
          "published" => "status",
        ],
      ],
      // EntityDescriptionInterface? but only used in core for config.
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function interfaceBasicParent() {
    return '\Drupal\Core\Entity\ContentEntityInterface';
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = parent::requiredComponents();

    $use_revisionable = !empty($this->component_data['revisionable']);
    $use_translatable = !empty($this->component_data['translatable']);

    //dump($this->component_data);

    $method_body = [];
    // Calling the parent defines fields for entity keys.
    $method_body[] = '$fields = parent::baseFieldDefinitions($entity_type);';
    $method_body[] = '';

    // Some interface-helper traits provide helper methods to define base
    // fields.
    $parent_interface_info = static::interfaceParents();
    foreach ($this->component_data['interface_parents'] as $interface_parent_value) {
      if (!isset($parent_interface_info[$interface_parent_value]['helper_methods'])) {
        continue;
      }

      foreach ($parent_interface_info[$interface_parent_value]['helper_methods'] as $method_name) {
        $method_body[] = "£fields += static::$method_name(£entity_type);";
        $method_body[] = '';
      }
    }

    // The parent doesn't supply a label field, so the entity class has to.
    $calls = [];

    $method_body[] = "£fields['title'] = \Drupal\Core\Field\BaseFieldDefinition::create('string')";
    $title_field_calls = [
      'setLabel' => "t('Title')",
      'setRequired' => TRUE,
    ];
    if ($use_revisionable) {
      $title_field_calls['setRevisionable'] = TRUE;
    }
    if ($use_translatable) {
      $title_field_calls['setTranslatable'] = TRUE;
    }
    $title_field_calls += [
      'setSetting' => ['max_length', 255],
      'setDisplayOptions' => ['form', new \DrupalCodeBuilder\Generator\Render\FormAPIArrayRenderer([
          'type' => 'string_textfield',
          'weight' => -5,
        ]),
      ],
      'setDisplayConfigurable' => ['form', TRUE],
      'setDisplayConfigurable__1' => ['view', TRUE],
    ];

    $fluent_call_renderer = new \DrupalCodeBuilder\Generator\Render\FluentMethodCallRenderer($title_field_calls);
    $call_lines = $fluent_call_renderer->render();
    $method_body = array_merge($method_body, $call_lines);
    $method_body[] = '';

    // Add a uid field if the entities have an owner.
    if (in_array('EntityOwnerInterface', $this->component_data['interface_parents'])) {
      $method_body[] = "£fields['uid'] = \Drupal\Core\Field\BaseFieldDefinition::create('entity_reference')";
      $uid_field_calls = new FluentMethodCall;
      $uid_field_calls
        ->setLabel(FluentMethodCall::t('Authored by'))
        ->setDescription(FluentMethodCall::t('The user ID of the author.'));
      if ($use_revisionable) {
        $uid_field_calls->setRevisionable(TRUE);
      }
      if ($use_translatable) {
        $uid_field_calls->setTranslatable(TRUE);
      }
      $uid_field_calls->setSetting('target_type', 'user')
        ->setDefaultValueCallback(FluentMethodCall::code("static::class . '::getCurrentUserId'"))
        ->setDisplayOptions('form', [
          'type' => 'entity_reference_autocomplete',
          'weight' => 5,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => '60',
            'autocomplete_type' => 'tags',
            'placeholder' => '',
          ],
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayOptions('view', [
          'label' => 'hidden',
          'type' => 'author',
          'weight' => 0,
        ])
        ->setDisplayConfigurable('view', TRUE);
      $call_lines = $uid_field_calls->getCodeLines();
      $method_body = array_merge($method_body, $call_lines);
      $method_body[] = '';
    }

    // Add a 'changed' field if entities use the changed interface.
    if (in_array('EntityChangedInterface', $this->component_data['interface_parents'])) {
      $method_body[] = "£fields['changed'] = \Drupal\Core\Field\BaseFieldDefinition::create('changed')";
      $changed_field_calls = new FluentMethodCall;
      $changed_field_calls->setLabel(FluentMethodCall::t('Changed'))
        ->setDescription(FluentMethodCall::t('The time that the node was last edited.'));
      if ($use_revisionable) {
        $changed_field_calls->setRevisionable(TRUE);
      }
      if ($use_translatable) {
        $changed_field_calls->setTranslatable(TRUE);
      }
      $method_body = array_merge($method_body, $changed_field_calls->getCodeLines());
      $method_body[] = '';
    }

    foreach ($this->component_data['base_fields'] as $base_field_data) {
      $method_body[] = "£fields['{$base_field_data['name']}'] = \Drupal\Core\Field\BaseFieldDefinition::create('{$base_field_data['type']}')";

      $fluent_calls = [];
      $fluent_calls[] = "  ->setLabel(t('{$base_field_data['label']}'))";
      $fluent_calls[] = "  ->setDescription(t('TODO: description of field.'))";
      if ($use_revisionable) {
        $fluent_calls[] = "  ->setRevisionable(TRUE)";
      }
      if ($use_translatable) {
        $fluent_calls[] = "  ->setTranslatable(TRUE)";
      }

      // Add a terminal ';' to the last of the fluent method calls.
      $fluent_calls[count($fluent_calls) - 1] .= ';';

      $method_body = array_merge($method_body, $fluent_calls);

      $method_body[] = '';
    }

    $method_body[] = 'return $fields;';

    $components["baseFieldDefinitions"] = [
      'component_type' => 'PHPFunction',
      'containing_component' => '%requester',
      'declaration' => 'public static function baseFieldDefinitions(\Drupal\Core\Entity\EntityTypeInterface $entity_type)',
      'doxygen_first' => '{@inheritdoc}',
      'body' => $method_body,
    ];

    // The uid field annoyingly need a default value callback that's just
    // boilerplate. See https://www.drupal.org/project/drupal/issues/2975503.
    if (in_array('EntityOwnerInterface', $this->component_data['interface_parents'])) {
      $components["getCurrentUserId"] = [
        'component_type' => 'PHPFunction',
        'containing_component' => '%requester',
        'declaration' => 'public static function getCurrentUserId()',
        'function_docblock_lines' => [
          "Default value callback for 'uid' base field definition.",
          "@see ::baseFieldDefinitions()",
          "",
          "@return array",
          "  An array of default values.",
        ],
        'body' => [
          "return [\Drupal::currentUser()->id()];",
        ],
      ];
    }

    // TODO: other methods!

    // Add menu plugins for the entity type if the UI option is set.
    if (!empty($this->component_data['entity_ui'])) {
      // Add a menu task if there is a route provider handler.
      // Content entities don't get a menu item, but rather a task (i.e. a tab)
      // alongside the content admin for nodes.
      // is fixed.
      // TODO: Change this when https://www.drupal.org/project/drupal/issues/2862859
      $components['collection_menu_task' . $this->component_data['entity_type_id']] = [
        'component_type' => 'PluginYAML',
        'plugin_type' => 'menu.local_task',
        'prefix_name' => FALSE,
        'plugin_name' => "entity.{$this->component_data['entity_type_id']}.collection",
        'plugin_properties' => [
          'title' => $this->component_data['entity_type_label'] . 's',
          'route_name' => "entity.{$this->component_data['entity_type_id']}.collection",
          'base_route' => 'system.admin_content',
          // Media module sets 10 for its tab; go further along.
          'weight' => 15,
        ],
      ];

      // If there is a bundle entity, change the 'add' local action to go to
      // the add page route, where a bundle can be selected, rather than the
      // add form.
      if (!empty($this->component_data['bundle_entity_type'])) {
        $components['collection_menu_action' . $this->component_data['entity_type_id']]['plugin_properties']['route_name'] = "entity.{$this->component_data['entity_type_id']}.add_page";
      }

      // Make local tasks (aka tabs) for the view, edit, and delete routes.
      $entity_tabs = [
        'canonical' => 'View',
        'edit_form' => 'Edit',
        'delete_form' => 'Delete',
      ];
      foreach ($entity_tabs as $route_suffix => $title) {
        $components["collection_menu_task_{$route_suffix}_{$this->component_data['entity_type_id']}"] = [
          'component_type' => 'PluginYAML',
          'plugin_type' => 'menu.local_task',
          'prefix_name' => FALSE,
          'plugin_name' => "entity.{$this->component_data['entity_type_id']}.{$route_suffix}",
          'plugin_properties' => [
            'title' => $title,
            'route_name' => "entity.{$this->component_data['entity_type_id']}.{$route_suffix}",
            'base_route' => "entity.{$this->component_data['entity_type_id']}.canonical",
          ],
        ];
      }
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAnnotationData() {
    $annotation = parent::getAnnotationData();

    $annotation['#class'] = 'ContentEntityType';

    // Standard ordering for our annotation keys.
    $annotation_keys = [
      'id',
      'label',
      'label_collection',
      'label_singular',
      'label_plural',
      'label_count',
      'bundle_label',
      'base_table',
      'data_table',
      'revision_table',
      'revision_data_table',
      'translatable',
      'handlers',
      'admin_permission',
      'entity_keys',
      'bundle_entity_type',
      'field_ui_base_route',
      'links',
    ];
    $annotation_data = array_fill_keys($annotation_keys, NULL);

    // Re-create the annotation #data array, with the properties in our set
    // order.
    foreach ($annotation['#data'] as $key => $data) {
      $annotation_data[$key] = $data;
    }

    // Add further annotation properties.
    // Use the entity type ID as the base table.
    $annotation_data['base_table'] = $this->component_data['entity_type_id'];

    if (!empty($this->component_data['entity_ui'])) {
      $annotation_data['links'] = [];
      $entity_path_component = $this->component_data['entity_type_id'];
      $bundle_entity_type_path_argument = $this->component_data['bundle_entity_type'];

      // The structure of the add UI depends on whether there is a bundle
      // entity.
      if (isset($this->component_data['bundle_entity_type'])) {
        // If there's a bundle entity, the add UI is made up of first a page to
        // select the bundle, and then a form with a bundle parameter.
        $annotation_data['links']["add-page"] = "/$entity_path_component/add";
        $annotation_data['links']["add-form"] = "/$entity_path_component/add/{{$bundle_entity_type_path_argument}}";
      }
      else {
        // If there's no bundle entity, it's just an add form with no
        // parameter.
        $annotation_data['links']["add-form"] = "/$entity_path_component/add";
      }

      $annotation_data['links']["canonical"] = "/$entity_path_component/{{$entity_path_component}}";
      $annotation_data['links']["collection"] = "/admin/content/$entity_path_component";
      $annotation_data['links']["delete-form"] = "/$entity_path_component/{{$entity_path_component}}/delete";
      $annotation_data['links']["edit-form"] = "/$entity_path_component/{{$entity_path_component}}/edit";
      // TODO: revision link template.
      // $annotation_data['links']["revision"] = "/$entity_path_component/{}/revisions/{media_revision}/view";
    }

    if (isset($this->component_data['bundle_entity_type'])) {
      $annotation_data['bundle_entity_type'] = $this->component_data['bundle_entity_type'];
      $annotation_data['bundle_label'] = [
        '#class' => 'Translation',
        '#data' => $this->component_data['bundle_label'],
      ];
    }

    $revisionable = !empty($this->component_data['revisionable']);
    $translatable = !empty($this->component_data['translatable']);

    if (!empty($this->component_data['field_ui_base_route'])) {
      $annotation_data['field_ui_base_route'] = $this->component_data['field_ui_base_route'];
    }

    if ($revisionable) {
      $annotation_data['revision_table'] = "{$annotation_data['base_table']}_revision";
    }

    if ($translatable) {
      $annotation_data['translatable'] = 'TRUE';
      $annotation_data['data_table'] = "{$annotation_data['base_table']}_field_data";
    }

    if ($translatable && $revisionable) {
      $annotation_data['revision_data_table'] = "{$annotation_data['base_table']}_field_revision";
    }

    // Filter the annotation data to remove any keys which are NULL; that is,
    // which are still in the state that the array fill put them in and that
    // have not had any actual data in. AFAIK annotation values are never
    // actually NULL, so this is ok.
    $annotation_data = array_filter($annotation_data, function($item) {
      return !is_null($item);
    });

    // Put our data into the annotation array.
    $annotation['#data'] = $annotation_data;

    return $annotation;
  }

}
