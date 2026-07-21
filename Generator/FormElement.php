<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Data\DataItem;
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
  public static function getDifferentiatedLabelSuffix(DataItem $data): ?string {
    return $data->form_key->value ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    // Allow this to be overriden by incoming data, for entity form handlers
    // where the request chain is different.
    return $this->component_data->containing_component->value ?? '%requester:buildForm';
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
      '#type' => $this->component_data->element_type->value,
    ];

    if (!empty($this->component_data->element_title->value)) {
      $form_api_array['#title'] = '£this->t("' . $this->component_data->element_title->value . '")';
    }
    if (!empty($this->component_data->element_description->value)) {
      $form_api_array['#description'] = '£this->t("' . $this->component_data->element_description->value . '")';
    }

    foreach ($this->component_data->element_array->value as $attribute => $value) {
      $form_api_array['#' . $attribute] = $value;
    }

    // Special handling for checkboxes & radios: they must have an #options
    // property, or the form code will crash.
    if (in_array($this->component_data->element_type->value, ['checkboxes', 'radios'])) {
      $form_api_array['#options'] = [];
    }

    return $form_api_array;
  }

}
