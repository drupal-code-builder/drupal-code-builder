<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use Ckr\Util\ArrayMerger;

/**
 * Generator base class for module info file.
 */
abstract class Info extends File {

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
    'core',
    'core_version_requirement',
    'dependencies',
  ];

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    // Properties acquired from the requesting root component.
    $plugin_type_properties = [
      'readable_name',
      'short_description',
      'module_dependencies',
      'module_package',
    ];
    foreach ($plugin_type_properties as $property_name) {
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

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $lines = [];
    foreach ($this->filterComponentContentsForRole($children_contents, 'infoline') as $component_name => $component_lines) {
      // Assume that children components don't tread on each others' toes and
      // provide the same property names.
      $lines += $component_lines;
    }

    // Temporary, until Generate handles the return from this.
    $this->extraLines = $lines;
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $data = $this->infoData();

    // Filter before merging with existing, as if scalar properties have not
    // been set, they will have the empty arrays from getInfoFileEmptyLines();
    $data = array_filter($data);

    $file_info = [];

    if ($this->exists) {
      $merger = new ArrayMerger($this->existing, $data);
      $merger->preventDoubleValuesWhenAppendingNumericKeys(TRUE);
      $data = $merger->mergeData();

      $file_info['merged'] = TRUE;
    }

    $body = $this->process_info_lines($data);

    $file_info += [
      'path' => '',
      'filename' => '%module.info',
      'body' => $body,
      'build_list_tags' => ['info'],
    ];

    return $file_info;
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
  abstract protected function infoData(): array;

  /**
   * Gets an array of info file lines in the correct order to be populated.
   *
   * @return array
   *   The array of lines.
   */
  protected function getInfoFileEmptyLines() {
    return array_fill_keys(self::INFO_LINE_ORDER, []);
  }

}
