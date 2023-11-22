<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Represents a section in the README.md file.
 */
class ReadmeSection extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'section';
  }

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'title' => PropertyDefinition::create('string')
        ->setLabel('The title of the section')
        ->setInternal(TRUE),
      'text' => PropertyDefinition::create('string')
        ->setLabel('The text content. An array of lines. Last line should be empty.')
        ->setMultiple(TRUE)
        ->setInternal(TRUE),
      'readable_name' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    // Force a README component, in case it's not been selected.
    return [
      'readme' => [
        'component_type' => 'Readme',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    // If the README component was only requested by this generator, it won't
    // be available yet, so we can't use %nearest_root to reach it.
    return '%self:readme';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    return [
      $this->component_data['title'] => $this->component_data['text'],
    ];
  }

}
