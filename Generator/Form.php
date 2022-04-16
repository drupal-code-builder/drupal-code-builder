<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator class for forms on Drupal 8.
 *
 * Note that entity forms use the EntityForm generator which does *not*
 * inherit from this class!
 */
class Form extends PHPClassFileWithInjection {

  protected $hasStaticFactoryMethod = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $properties = [
      // Move the form class name property to the top, and override its default.
      'plain_class_name' => $definition->getProperty('plain_class_name')
        ->setLabel("Form class name.")
        ->setDescription("The form class's plain class name, e.g. \"MyForm\"."),
      'form_id' => PropertyDefinition::create('string')
        ->setLabel('The form ID.')
        ->setInternal(TRUE)
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("get('..:root_component_name') ~ '_' ~ machineFromPlainClassName(get('..:plain_class_name'))")
            ->setDependencies('..:root_component_name', '..:plain_class_name')
        ),
      'injected_services' => PropertyDefinition::create('string')
        ->setLabel('Injected services')
        ->setDescription("Services to inject. Additionally, use 'storage:TYPE' to inject entity storage handlers.")
        ->setMultiple(TRUE)
        ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportServiceData')),
      'form_elements' => static::getLazyDataDefinitionForGeneratorType('FormElement')
        ->setLabel('Form elements')
        ->setMultiple(TRUE),
    ];

    // Put the rest of the parent definitions after ours.
    $definition->addProperties($properties);

    // Put the class in the 'Form' relative namespace.
    $definition->getProperty('relative_namespace')
      ->setLiteralDefault('Form');

    $definition->getProperty('plain_class_name')
      ->setLiteralDefault('MyForm');

      $definition->getProperty('relative_class_name')->setInternal(TRUE);

    // Set the parent class.
    $definition->getProperty('parent_class_name')
      ->setLiteralDefault('\Drupal\Core\Form\FormBase');

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $form_name = $this->component_data['form_id'];

    $components = [
      // Request the form functions.
      'getFormId' => [
        'component_type' => 'PHPFunction',
        'function_name' => 'getFormId',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function getFormId()',
        'body' => [
          "return '$form_name';",
        ],
      ],
      'buildForm' => [
        'component_type' => 'FormBuilder',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'function_name' => 'buildForm',
        'body' => [
          "// Uncomment this line if you change the base class.",
          "// £form = parent::buildForm(£form, £form_state);",
          "",
          "£form['element'] = [",
          "  '#type' => 'textfield',",
          "  '#title' => £this->t('Enter a value'),",
          "  '#description' => £this->t('Enter a description'),",
          "  '#default_value' => 'enter the default value',",
          "  '#required' => TRUE,",
          "];",
          "",
          "£form['submit'] = [",
          "  '#type' => 'submit',",
          "  '#value' => £this->t('Submit'),",
          "];",
          "",
          "return £form;",
        ],
      ],
      'validateForm' => [
        'component_type' => 'PHPFunction',
        'function_name' => 'validateForm',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)',
        'body' => '',
      ],
      'submitForm' => [
        'component_type' => 'PHPFunction',
        'function_name' => 'submitForm',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)',
        'body' => '',
      ],
    ];

    foreach ($this->component_data['injected_services'] as $service_id) {
      $components['service_' . $service_id] = [
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
      ];
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // TEMPORARY, until Generate task handles returned contents.
    $this->functions = $this->filterComponentContentsForRole($children_contents, 'function');
    $this->injectedServices = $this->filterComponentContentsForRole($children_contents, 'service');
    $this->childContentsGrouped = $this->groupComponentContentsByRole($children_contents);

    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    $this->collectSectionBlocksForDependencyInjection();
  }

}
