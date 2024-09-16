<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\OptionsSortOrder;

/**
 * Generator class for form elements.
 */
class FormElement extends BaseGenerator {

  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'form_key' => PropertyDefinition::create('string')
        ->setLabel('Element name')
        ->setDescription("The element's key in the form array.")
        ->setRequired(TRUE),
      'element_type' => PropertyDefinition::create('string')
        ->setLabel('Element type')
        ->setRequired(TRUE)
        ->setOptionSetDefinition(\DrupalCodeBuilder\Factory::getTask('ReportElementTypes'))
        ->setOptionsSorting(OptionsSortOrder::Label),
      // Not required; elements such as #machine_name don't use it.
      'element_title' => PropertyDefinition::create('string')
        ->setLabel('Element title')
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("machineToLabel(get('..:form_key'))")
            ->setDependencies('..:form_key')
        ),
      'element_description' => PropertyDefinition::create('string')
        ->setLabel('Element description'),
      'element_array' => PropertyDefinition::create('mapping')
        ->setInternal(TRUE)
        ->setLiteralDefault([]),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    // Allow this to be overriden by incoming data, for entity form handlers
    // where the request chain is different.
    return $this->component_data['containing_component'] ?? '%requester:buildForm';
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'element';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    $form_api_array = [
      '#type' => $this->component_data['element_type'],
    ];

    if (!empty($this->component_data['element_title'])) {
      $form_api_array['#title'] = '£this->t("' . $this->component_data['element_title'] . '")';
    }
    if (!empty($this->component_data['element_description'])) {
      $form_api_array['#description'] = '£this->t("' . $this->component_data['element_description'] . '")';
    }

    foreach ($this->component_data['element_array'] as $attribute => $value) {
      $form_api_array['#' . $attribute] = $value;
    }

    return $form_api_array;
  }

}
