<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Utility\NestedArray;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\File\CodeFile;
use DrupalCodeBuilder\File\DrupalExtension;
use Ckr\Util\ArrayMerger;
use DrupalCodeBuilder\Generator\Render\Yaml;

/**
 * Generator for general YML files.
 *
 * Expects an array of data to output as YAML in the 'yaml_data' property.
 *
 * Note that replacement tokens should be avoided in YAML properties, as the
 * initial '%' causes the property to be quoted by the Symfony YAML dumper,
 * apparently  unnecessarily once the token is replaced.
 */
class YMLFile extends File {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'yaml_data' => PropertyDefinition::create('mapping')
        ->setLabel('The data for the YAML file')
        ->setInternal(TRUE),
      'yaml_inline_level' => PropertyDefinition::create('string')
        ->setLabel('The level at which to switch YAML properties to inline formatting.')
        ->setInternal(TRUE)
        ->setLiteralDefault(6),
      // The YAML data level at which to add linebreaks.
      //  - integer to add a linebreak between every element at this depth. May
      //    be 0. Note this is level, not number of indentation spaces.
      //  - NULL to add no linebreaks.
      // TODO: fix data type!
      'line_break_between_blocks_level' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault(Yaml::NEVER),
      'inline_levels_extra' => PropertyDefinition::create('mapping')
        ->setInternal(TRUE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return $this->component_data['filename'];
  }

  /**
   * {@inheritdoc}
   */
  public function detectExistence(DrupalExtension $extension) {
    $filename = $this->getFilename();
    $this->exists = $extension->hasFile($filename);

    if (!$this->exists) {
      return;
    }

    $yaml = $extension->getFileYaml($filename);

    // No idea of format here! Probably unique for each generator!
    // For info files, the only thing which is mergeable
    $this->existing = $yaml;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileInfo(): \DrupalCodeBuilder\File\CodeFile {
    $yaml_data = [];
    foreach ($this->containedComponents['element'] as $key => $child_item) {
      $child_item_yaml_data = $child_item->getContents();

      // Use array merge as child items may provide numerically-keyed lists,
      // which should not clobber each other.
      $yaml_data = ArrayMerger::doMerge($yaml_data, $child_item_yaml_data);
    }

    if (empty($yaml_data)) {
      // If children don't provide anything, use the property, as that may have
      // been set by a requesting component.
      $yaml_data = $this->component_data->yaml_data->value;
    }

    $file_info = [];

    if ($this->exists) {
      $merger = new ArrayMerger($this->existing, $yaml_data);
      $merger->preventDoubleValuesWhenAppendingNumericKeys(TRUE);
      $yaml_data = $merger->mergeData();

      $merged = TRUE;
    }

    return new CodeFile(
      body_pieces: $this->getYamlBody($yaml_data),
      merged: $merged ?? FALSE,
    );
  }

  /**
   * Get the YAML body for the file.
   *
   * @param $yaml_data_array
   *  An array of data to convert to YAML.
   *
   * @return
   *  An array containing the YAML string.
   */
  protected function getYamlBody($yaml_data_array) {
    $this->expandInlineItems($yaml_data_array);

    $yaml = Yaml::create(
      $yaml_data_array,
      inline_from_level: $this->component_data->yaml_inline_level->value,
      blank_lines_until_level: $this->component_data->line_break_between_blocks_level->value,
    );

    $yaml_lines = $yaml->render();

    // Add a terminal newline.
    $yaml_lines[] = '';

    return $yaml_lines;
  }

  /**
   * Change specified YAML properties from inlined to expanded.
   *
   * We need this because some Drupal YAML files have variable levels of
   * inlining, and Symfony's YAML dumper does not support this, nor plan to:
   * see https://github.com/symfony/symfony/issues/19014#event-688175812.
   *
   * For example, a services.yml file has 'arguments' and 'tags' at the same
   * level, but while 'arguments' is inlined, 'tags' is expanded, and inlined
   * one level lower:
   *
   * @code
   *   forum_manager:
   *    class: Drupal\forum\ForumManager
   *    arguments: ['@config.factory', '@entity.manager', '@database', '@string_translation', '@comment.manager']
   *    tags:
   *      - { name: backend_overridable }
   * @endcode
   *
   * The properties to expand are specified in the 'inline_levels_extra'
   * component data. This is an array of rules, keyed by an arbitrary name,
   * where each rule is an array consisting of:
   *  - 'address': An address array of the property or properties to expand.
   *    This supports verbatim address pieces, and a '*' for a wildcard.
   *  - 'level': NOT YET USED.
   *
   * @param array &$yaml_data_array
   *   The YAML data array, passed by reference.
   */
  protected function expandInlineItems(&$yaml_data_array) {
    if (empty($this->component_data['inline_levels_extra'])) {
      return;
    }

    foreach ($this->component_data['inline_levels_extra'] as $extra_expansion_rule) {
      // The rule address may use wildcards. Get a list of actual properties
      // to expand.
      $rule_address = $extra_expansion_rule['address'];
      $properties_to_expand = [];

      // Iterate recursively through the whole YAML data structure.
      $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($yaml_data_array), \RecursiveIteratorIterator::SELF_FIRST);
      foreach ($iterator as $key => $value) {
        $depth = $iterator->getDepth();
        if ($depth != count($rule_address) - 1) {
          // The current item's depth does not match the rule's address: skip.
          // Note that the iterator's notion of depth is zero-based.
          continue;
        }

        // Get the address of the iterator's current location.
        // See https://stackoverflow.com/questions/7590662/walk-array-recursively-and-print-the-path-of-the-walk
        $current_address = [];
        for ($i = 0; $i <= $depth; $i++) {
          $current_address[] = $iterator->getSubIterator($i)->key();
        }

        // Compare the current address with the rule address.
        for ($i = 0; $i <= $depth; $i++) {
          if ($rule_address[$i] == '*') {
            // Wildcard matches anything: pass this level.
            continue;
          }

          if ($rule_address[$i] != $current_address[$i]) {
            // There is a mismatch: give up on this item in the iterator and
            // move on to the next.
            continue 2;
          }
        }

        // If we are still here, all levels of the current address passed the
        // comparison with the rule address: the address is valid.
        $properties_to_expand[] = $current_address;
      }

      foreach ($properties_to_expand as $property) {
        // Get the value for the property.
        $value = NestedArray::getValue($yaml_data_array, $property);

        // Create a nested YAML renderer.
        $nested_yaml = Yaml::create($value, inline_from_level: 1);

        // Put it back in the data array.
        NestedArray::setValue($yaml_data_array, $property, $nested_yaml);
      }
    }
  }

}
