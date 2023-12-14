<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for module permissions on Drupal 8 and higher.
 */
class Permission extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'permission' => PropertyDefinition::create('string')
        ->setLabel('Permission machine name')
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
            ->setExpression("'Description for ' ~ get('..:title') ~ '.'")
            ->setDependencies('..:title')
        ),
      'restrict_access' => PropertyDefinition::create('boolean')
        ->setLabel('Access warning')
        ->setDescription('Whether the permission should show a warning that it should be granted with care.')
        ->setRequired(TRUE)
        ->setLiteralDefault(FALSE),
    ]);
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
  public function getContents(): array {
    $permission_name = $this->component_data['permission'];

    $permission_info = [
      'title' => $this->component_data['title'],
      'description' => $this->component_data['description'],
    ];
    if (!empty($this->component_data['restrict_access'])) {
      $permission_info['restrict access'] = TRUE;
    }

    $yaml_data[$permission_name] = $permission_info;

    return $yaml_data;
  }

}
