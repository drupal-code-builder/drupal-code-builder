<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for module permissions on Drupal 8.
 */
class Permission extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'permission' => PropertyDefinition::create('string')
        ->setLabel('Permission human-readable name')
        ->setRequired(TRUE)
        ->setLiteralDefault('access my_module'),
      'title' => PropertyDefinition::create('string')
        ->setLabel('Permission human-readable name')
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
          ->setExpression("machineToLabel(get('..:permission'))")
            ->setDependencies('..:permission')
        ),
      'description' => PropertyDefinition::create('string')
        ->setLabel('Permission description')
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("get('..:title')")
            ->setDependencies('..:title')
        ),
      'restrict_access' => PropertyDefinition::create('boolean')
        ->setLabel('Access warning')
        ->setDescription('Whether the permission should show a warning that it should be granted with care.')
        ->setRequired(TRUE)
        ->setLiteralDefault(FALSE),
    ]);

    return $definition;
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    $components = [
      '%module.permissions.yml' => [
        'component_type' => 'YMLFile',
        'filename' => '%module.permissions.yml',
      ],
    ];

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%self:%module.permissions.yml';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $permission_name = $this->component_data['permission'];

    $permission_info = [
      'title' => $this->component_data['title'],
      'description' => $this->component_data['description'],
    ];
    if (!empty($this->component_data['restrict_access'])) {
      $permission_info['restrict access'] = TRUE;
    }

    $yaml_data[$permission_name] = $permission_info;

    return [
      'permission' => [
        'role' => 'yaml',
        'content' => $yaml_data,
      ],
    ];
  }

}
