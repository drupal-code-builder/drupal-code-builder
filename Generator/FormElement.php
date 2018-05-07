<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator class for form elements.
 */
class FormElement extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    $data_definition = parent::componentDataDefinition() + [
      'form_id' => [
        'internal' => TRUE,
        // Means the ComponentCollector should copy in the property from the
        // requesting component.
        // TODO: ComponentCollector will need changing so it doesn't clobber
        // a value already here?
        'acquired' => TRUE,
      ],
      'form_key' => [
        'internal' => TRUE,
        'required' => TRUE,
      ],
      'element_type' => [
        'internal' => TRUE,
        'required' => TRUE,
      ],
      'element_title' => [
        'internal' => TRUE,
        'required' => TRUE,
      ],
      // Further FormAPI attributes, without the initial '#'.
      'element_array' => [
        'internal' => TRUE,
        'format' => 'array',
        'default' => [],
      ],
    ];

    return $data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function getUniqueID() {
    // Include the form ID, as element names are not unique.
    return
      $this->component_data['root_component_name'] . '/' .
      implode(':', [$this->type, $this->component_data['form_id'], $this->name]);
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%sibling:buildForm';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $form_api_array = [
      '#type' => $this->component_data['element_type'],
      '#title' => '"' . $this->component_data['element_title'] . '"',
    ];
    foreach ($this->component_data['element_array'] as $attribute => $value) {
      if (is_string($value)) {
        $value = '"' . $value . '"';
      }

      $form_api_array['#' . $attribute] = $value;
    }

    return [
      'element' => [
        'role' => 'element',
        'content' => [
          'key' => $this->component_data['form_key'],
          'array' => $form_api_array,
        ],
      ],
    ];
  }

}
