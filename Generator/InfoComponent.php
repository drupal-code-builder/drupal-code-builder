<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Abstract generator for component info data.
 *
 * This represents the data for the component info file, but the file itself is
 * requested as a separate component. This is to handle the orthogonal variables
 * of file format and extension type. Putting the logic from this class in the
 * generator for the file would mean that component type-specific properties are
 * too tightly bound to the file format.
 */
abstract class InfoComponent extends BaseGenerator {

  /**
   * The component type for the info file.
   */
  protected const INFO_COMPONENT_TYPE = 'YMLFile';

  /**
   * The filename for the info file.
   */
  protected const INFO_FILENAME = '%extension.info.yml';

  /**
   * The order of keys in the info file.
   *
   * Child classes should override this.
   */
  protected const INFO_LINE_ORDER = [];

  /**
   * Properties that should be automatically defined and acquired from the root.
   *
   * @var array
   */
  protected static $propertiesAcquiredFromRoot = [];

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    // Properties acquired from the requesting root component.
    foreach (static::$propertiesAcquiredFromRoot as $property_name) {
      $definition->addProperty(PropertyDefinition::create('string')
        ->setName($property_name)
        ->setAutoAcquiredFromRequester()
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $components['info_file'] = [
      'component_type' => static::INFO_COMPONENT_TYPE,
      'filename' => static::INFO_FILENAME,
      // Too early to pass yaml_data, as we need to collect contents for that.
    ];

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    // This needs to be contained by the info file, as it has to be contained by
    // something that's a file in order to assemble its own contents.
    return '%self:info_file';
  }

  /**
   * Gets additional info lines from contained components.
   *
   * @return array
   */
  protected function getContainedComponentInfoLines(): array {
    $lines = [];
    foreach ($this->containedComponents['element'] ?? [] as $key => $child_item) {
      $contents = $child_item->getContents();

      // Assume that children components don't tread on each others' toes and
      // provide the same property names.
      $lines[array_key_first($contents)] = reset($contents);
    }

    return $lines;
  }

  /**
   * Gets an array of info file lines in the correct order to be populated.
   *
   * @return array
   *   The array of lines.
   */
  protected function getInfoFileEmptyLines() {
    return array_fill_keys(static::INFO_LINE_ORDER, []);
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    $data = $this->infoData();

    // Filter before merging with existing, as if scalar properties have not
    // been set, they will have the empty arrays from getInfoFileEmptyLines();
    $data = array_filter($data);

    return $data;
  }

  /**
   * Gets the value for the 'core_version_requirement' property.
   *
   * @return string
   *   The version compatibility value string.
   */
  protected function getCoreVersionCompatibilityValue(): string {
    return '^8 || ^9 || ^10 || ^11';
  }

}
