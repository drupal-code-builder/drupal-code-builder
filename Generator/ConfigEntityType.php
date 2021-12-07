<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\ClassAnnotation;
use DrupalCodeBuilder\Utility\InsertArray;
use CaseConverter\CaseString;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Generator for a config entity type.
 */
class ConfigEntityType extends EntityTypeBase {

  /**
   * {@inheritdoc}
   */
  protected $annotationClassName = 'ConfigEntityType';

  /**
   * {@inheritdoc}
   */
  protected $annotationTopLevelOrder = [
    'id',
    'label',
    'label_collection',
    'label_singular',
    'label_plural',
    'label_count',
    'handlers',
    'admin_permission',
    'entity_keys',
    'config_export',
    'links',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    // Set up the entity type functionality preset options.
    $definition->getProperty('functionality')->setPresets([
      'plugin_collection' => [
        'label' => "Plugin collection - entities use plugins; implements EntityWithPluginCollectionInterface",
        'data' => [
          'force' => [
            'interface_parents' => [
              'value' => ['\Drupal\Core\Entity\EntityWithPluginCollectionInterface'],
            ],
          ],
        ],
      ],
    ]);

    $config_schema_property = PropertyDefinition::create('complex')
      ->setName('entity_properties')
      ->setLabel('Entity properties')
      ->setDescription("The config properties that are stored for each entity of this type. An ID and label property are provided automatically.")
      ->setMultiple(TRUE)
      ->setProperties([
        'name' => PropertyDefinition::create('string')
          ->setLabel('Property name')
          ->setRequired(TRUE)
          ->setValidators('machine_name'),
        'label' => PropertyDefinition::create('string')
          ->setLabel('Property label')
          ->setRequired(TRUE)
          ->setDefault(
            DefaultDefinition::create()
              ->setExpression("machineToLabel(get('..:name'))")
            ->setDependencies('..:name')
          ),
        'type' => PropertyDefinition::create('string')
          ->setLabel('Data type')
          ->setRequired(TRUE)
          ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportDataTypes')),
      ]);
    $definition->addPropertyAfter('interface_parents', $config_schema_property);

    $definition->getProperty('parent_class_name')
      ->setDefault(
        DefaultDefinition::create()
          ->setLiteral('\Drupal\Core\Config\Entity\ConfigEntityBase')
      );

    $definition->getProperty('interface_parents')
      ->setLiteralDefault(['\Drupal\Core\Config\Entity\ConfigEntityInterface']);

    // Change the computed value for entity keys.
    $definition->getProperty('entity_keys')
      ->setLiteralDefault([
      'id' => 'id',
      'label' => 'label',
    ]);

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getHandlerTypes() {
    $handler_types = parent::getHandlerTypes();

    $handler_types['storage']['base_class'] = '\Drupal\Core\Config\Entity\ConfigEntityStorage';

    $handler_types['list_builder']['handler_properties']['entity_type_group'] = 'config';

    foreach (['form_default', 'form_add', 'form_edit'] as $form_handler_type) {
      $handler_types[$form_handler_type]['base_class'] = '\Drupal\Core\Entity\EntityForm';

      $handler_types[$form_handler_type]['handler_properties'] = [
        // Config entity formss redirect to the collection page.
        'redirect_link_template' => 'collection',
      ];
    }

    $handler_types['form_delete']['base_class'] = '\Drupal\Core\Entity\EntityDeleteForm';

    return $handler_types;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // Filthy hack.
    $label = $this->component_data->entity_properties->insertBefore(0);
    $label->set([
      'name' => 'label',
      'label' => 'Name',
      'type' => 'label',
    ]);

    $id = $this->component_data->entity_properties->insertBefore(0);
    $id->set([
      'name' => 'id',
      'label' => 'Machine name',
      'type' => 'text',
    ]);

    // Add form elements for the form handlers, if present.
    $form_handlers_to_add_form_elements_to = [];
    if (isset($components['handler_form_default'])) {
      $form_handlers_to_add_form_elements_to[] = 'handler_form_default';
    }
    else {
      // Only add form elements to the add and edit form handlers if they are
      // not inheriting from the default form handler.
      foreach (['form_add', 'form_edit'] as $form_handler_key) {
        $data_key = "handler_{$form_handler_key}";
        if (isset($components[$data_key])) {
          $form_handlers_to_add_form_elements_to[] = $data_key;
        }
      }
    }

    foreach ($form_handlers_to_add_form_elements_to as $data_key) {
      // Special handling for the id and label properties.
      $components[$data_key . '-label'] = [
        'component_type' => 'FormElement',
        'containing_component' => "%requester:{$data_key}:form",
        'form_key' => 'label',
        'element_type' => 'textfield',
        'element_title' => "Name",
        'element_description' => "The human-readable name of this entity",
        'element_array' => [
          'default_value' => "£this->entity->get('label')",
        ],
      ];

      $components[$data_key . '-id'] = [
        'component_type' => 'FormElement',
        'containing_component' => "%requester:{$data_key}:form",
        'form_key' => 'id',
        'element_type' => 'machine_name',
        'element_title' => 'Name',
        'element_description' => "A unique machine-readable name for this entity. It must only contain lowercase letters, numbers, and underscores.",
        'element_array' => [
          'default_value' => "£this->entity->id()",
          'machine_name' => [
            'exists' => "['{$this->component_data['qualified_class_name']}', 'load']",
            'source' => "['label']",
          ],
        ],
      ];

      // Add a form element for each custom entity property.
      foreach ($this->component_data['entity_properties'] as $schema_item) {
        $property_name = $schema_item['name'];

        // Skip id and label; done above.
        if ($property_name == 'id' || $property_name == 'label') {
          continue;
        }

        $components[$data_key . ':' . $property_name] = [
          'component_type' => 'FormElement',
          'containing_component' => "%requester:{$data_key}:form",
          'form_key' => $property_name,
          'element_type' => 'textfield',
          'element_title' => $schema_item['label'],
          'element_description' => "TODO: enter a description.",
          'element_array' => [
            'default_value' => "£this->entity->get('{$property_name}')",
          ],
        ];
      }
    }

    $entity_config_key = $this->component_data['entity_type_id'];
    $module = $this->component_data['root_component_name'];

    $schema_properties_yml = [];
    foreach ($this->component_data['entity_properties'] as $schema_item) {
      $schema_properties_yml[$schema_item['name']] = [
        'type' => $schema_item['type'],
        'label' => $schema_item['label'],
      ];
    }

    $components["config/schema/%module.schema.yml"] = [
      'component_type' => 'ConfigSchema',
      'yaml_data' => [
        "{$module}.{$entity_config_key}.*"=> [
          'type' => 'config_entity',
          'label' => $this->component_data['entity_type_label'],
          'mapping' => $schema_properties_yml,
        ],
      ],
    ];

    // Add menu plugins for the entity type if the UI option is set.
    if ($this->component_data['entity_ui']) {
      // Name must be unique among the component type.
      $components['collection_menu_link_' . $this->component_data['entity_type_id']] = [
        'component_type' => 'Plugin',
        'plugin_type' => 'menu.link',
        'prefix_name' => FALSE,
        'plugin_name' => "entity.{$this->component_data['entity_type_id']}.collection",
        'plugin_properties' => [
          'title' => $this->component_data['entity_type_label'] . 's',
          'description' => "Create and manage fields, forms, and display settings for {$this->component_data['entity_type_label']}s.",
          'route_name' => "entity.{$this->component_data['entity_type_id']}.collection",
          'parent' => 'system.admin_structure',
        ],
      ];

      // Make a local task (aka tab) for the edit route (so that Field UI
      // tabs can hang off it).
      $entity_tabs = [
        'edit_form' => 'Edit',
      ];
      foreach ($entity_tabs as $route_suffix => $title) {
        $components["collection_menu_task_{$route_suffix}_{$this->component_data['entity_type_id']}"] = [
          'component_type' => 'Plugin',
          'plugin_type' => 'menu.local_task',
          'prefix_name' => FALSE,
          'plugin_name' => "entity.{$this->component_data['entity_type_id']}.{$route_suffix}",
          'plugin_properties' => [
            'title' => $title,
            'route_name' => "entity.{$this->component_data['entity_type_id']}.{$route_suffix}",
            // Unlike content entities, the base route is the same as the tab.
            'base_route' => "entity.{$this->component_data['entity_type_id']}.{$route_suffix}",
          ],
        ];
      }
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    // Set up properties.
    foreach ($this->component_data['entity_properties'] as $schema_item) {
      // Just take the label as the description.
      // TODO: add a description property?
      $description = $schema_item['label'];
      if (substr($description, 0, 1) != '.') {
        $description .= '.';
      }

      // The PHP type is not the same as the config schema type!
      switch ($schema_item['type']) {
        case 'label':
        case 'text':
          $php_type = 'string';
          break;

        default:
          $php_type = $schema_item['type'];
      }

      $this->properties[] = $this->createPropertyBlock(
        $schema_item['name'],
        $php_type,
        [
          'docblock_first_line' => $schema_item['label'] . '.',
        ]
        // TODO: default value?
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getAnnotationData() {
    $annotation_data = parent::getAnnotationData();

    if (!empty($this->component_data['entity_ui'])) {
      $annotation_data['links'] = [];
      $entity_path_component = $this->component_data['entity_type_id'];
      $annotation_data['links']["add-form"] = "/admin/structure/{$entity_path_component}/add";
      $annotation_data['links']["canonical"] = "/admin/structure/{$entity_path_component}/{{$entity_path_component}}";
      $annotation_data['links']["collection"] = "/admin/structure/{$entity_path_component}";
      $annotation_data['links']["edit-form"] = "/admin/structure/{$entity_path_component}/{{$entity_path_component}}/edit";
      $annotation_data['links']["delete-form"] = "/admin/structure/{$entity_path_component}/{{$entity_path_component}}/delete";
    }

    $config_export_values = [];
    foreach ($this->component_data['entity_properties'] as $schema_item) {
      $config_export_values[] = $schema_item['name'];
    }
    if ($config_export_values) {
      $annotation_data['config_export'] = $config_export_values;
    }

    return $annotation_data;
  }

}
