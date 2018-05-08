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
      // This is supplied when this component is requested by a Form
      // component.
      // TODO: temporarily removed, as the acquiring system expects to always
      // find this, and entity types don't have it. We have to either make the
      // acquiring system more flexible, which seems like a rabbithole, or
      // figure out a better way to do this!
      /*
      'form_id' => [
        'internal' => TRUE,
        'acquired' => TRUE,
      ],
      */
      // This is supplied when this component is requested by an entity type
      // component. These cannot know their actual form ID, because that is
      // determined dynamically by Drupal's form system when the form is
      // actually used, as it depends on the entity bundle.
      'pseudo_form_id' => [
        'internal' => TRUE,
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
        // Not required; elements such as #machine_name don't use it.
      ],
      'element_description' => [
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
      implode(':', [
        $this->type,
        // TODO: change this to an elvis when form_id property is restored.
        $this->component_data['form_id'] ?? $this->component_data['pseudo_form_id'],
        $this->name,
      ]);
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
