<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\File\CodeFile;
use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Abstract base class for file generators.
 */
abstract class File extends BaseGenerator {

  /**
   * Gets the filename, relative to the main root component.
   *
   * This should be used instead of accessing the filename property directly, as
   * it replaces the '%module' token. This is particularly important as the
   * replacement value depends on the component's closest root.
   *
   * @return string
   *   The relative filename, with the '%module' token replaced.
   */
  public function getFilename(): string {
    $filename = $this->component_data->filename->value;
    assert(!empty($filename));
    $filename = str_replace('%module', $this->component_data->root_component_name->value, $filename);

    $component_base_path = $this->component_data->component_base_path->value;
    if (!empty($component_base_path)) {
      $filename = $component_base_path . '/' . $filename;
    }

    return $filename;
  }

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperty(PropertyDefinition::create('string')
      // The filename with the path relative to the nearest containing
      // component. E.g. 'templates/foo.twig.html'.
      ->setName('filename')
      ->setInternal(TRUE)
      ->setRequired(TRUE)
    );
  }

  /**
   * Return an empty array of subcomponent types.
   *
   * Files are (so far!) always terminal components.
   */
  public function requiredComponents(): array {
    return [];
  }

  /**
   * Return this component's parent in the component tree.
   *
   * Files are usually contained by the root component.
   */
  function containingComponent() {
    // Ensure we're not overriding the containing_component property.
    assert($this->component_data->containing_component->value == NULL, 'Overriding containing_component value');
    return '%root';
  }

  /**
   * Returns the data for the file this component provides.
   *
   * @return \DrupalCodeBuilder\File\CodeFile
   *  A CodeFile object holding at least the array of code lines for the file.
   */
  abstract public function getFileInfo(): CodeFile;

}
