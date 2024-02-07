<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use Ckr\Util\ArrayMerger;

/**
 * Abstract generator for module info data.
 *
 * This represents the data for the module info file, but the file itself is
 * requested as a separate component. This is to handle the orthogonal variables
 * of file format and extension type. Putting the logic from this class in the
 * generator for the file would mean that module-specific properties are too
 * tightly bound to the file format.
 */
class InfoModule extends BaseGenerator {

  /**
   * The component type for the info file.
   */
  const INFO_COMPONENT_TYPE = 'YMLFile';

  /**
   * The filename for the info file.
   */
  const INFO_FILENAME = '%module.info.yml';

  /**
   * The order of keys in the info file.
   *
   * @todo: Make this protected once our minimum PHP version is 7.1.
   */
  const INFO_LINE_ORDER = [
    'name',
    'type',
    'description',
    'package',
    'version',
    'lifecycle',
    'experimental',
    'core',
    'core_version_requirement',
    'dependencies',
  ];

  /**
   * Properties that should be automatically defined and acquired from the root.
   *
   * @var array
   */
  protected static $propertiesAcquiredFromRoot = [
    'base',
    'readable_name',
    'short_description',
    'module_dependencies',
    'module_package',
    'lifecycle',
  ];

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

    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('module_dependencies')
      ->setMultiple(TRUE)
      ->setAcquiringExpression('requester.module_dependencies.export()')
    );
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
   * Builds the array of data for the info file.
   *
   * @return array
   *   An array of data whose keys and values correspond to info file
   *   properties. This array may have empty lines, which are preserved in the
   *   return value to allow subclasses to still have the order from
   *   static::INFO_LINE_ORDER. Callers should run the return through
   *   array_filter() to remove these.
   */
  function infoData(): array {
    $module_data = $this->component_data;
    // dump("FILE BODY");
    // dump($module_data->export());

    $lines = $this->getInfoFileEmptyLines();
    $lines['name'] = $module_data->readable_name->value;
    $lines['type'] = $module_data->base->value;
    $lines['description'] = $module_data->short_description->value;
    // For lines which form a set with the same key and array markers,
    // simply make an array.
    foreach ($module_data->module_dependencies as $dependency) {
      $lines['dependencies'][] = $dependency->value;
    }

    if ($module_data->hasProperty('lifecycle') && $module_data->lifecycle->value) {
      $lines['lifecycle'] = $module_data->lifecycle->value;
    }

    if (!$module_data->module_package->isEmpty()) {
      $lines['package'] = $module_data->module_package->value;
    }

    // TODO: Move this to a helper method.
    $lines['core_version_requirement'] = '^8 || ^9 || ^10';

    if (!empty($extra_lines = $this->getContainedComponentInfoLines())) {
      $lines = array_merge($lines, $extra_lines);
    }

    return $lines;
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
    return array_fill_keys(self::INFO_LINE_ORDER, []);
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

}
