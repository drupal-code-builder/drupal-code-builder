<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Utility\NestedArray;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\File\DrupalExtension;
use Ckr\Util\ArrayMerger;

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
   * The value of the indent parameter to pass to the YAML dumper.
   *
   * @var int
   */
  const YAML_INDENT = 2;

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

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
        ->setInternal(TRUE),
      'inline_levels_extra' => PropertyDefinition::create('mapping')
        ->setInternal(TRUE),
    ]);

    return $definition;
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
    $this->exists = $extension->hasFile($this->component_data['filename']);

    if (!$this->exists) {
      return;
    }

    $yaml = $extension->getFileYaml($this->component_data['filename']);

    // No idea of format here! Probably unique for each generator!
    // For info files, the only thing which is mergeable
    $this->existing = $yaml;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    $yaml_data = [];
    foreach ($this->filterComponentContentsForRole($children_contents, 'yaml') as $component_name => $component_yaml_data) {
      $yaml_data += $component_yaml_data;
    }

    // TEMPORARY, until Generate task handles returned contents.
    if (!empty($yaml_data)) {
      // Only zap this if children provide something, as other components still
      // set this property by request.
      $this->component_data->yaml_data->set($yaml_data);
    }

    return [];
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    $yaml_data = $this->component_data['yaml_data'];

    $file_info = [];

    if ($this->exists) {
      $merger = new ArrayMerger($this->existing, $yaml_data);
      $merger->preventDoubleValuesWhenAppendingNumericKeys(TRUE);
      $yaml_data = $merger->mergeData();

      $file_info['merged'] = TRUE;
    }

    $file_info += [
      'path' => '', // Means base folder.
      'filename' => $this->component_data['filename'],
      'body' => $this->getYamlBody($yaml_data),
    ];

    return $file_info;
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
    $yaml_parser = new \Symfony\Component\Yaml\Yaml;

    $yaml_parser_inline_switch_level = $this->component_data['yaml_inline_level'];

    $yaml = $yaml_parser->dump($yaml_data_array, $yaml_parser_inline_switch_level, static::YAML_INDENT);

    $this->expandInlineItems($yaml_data_array, $yaml);

    $yaml_lines = explode("\n", $yaml);

    if (!is_null($this->component_data['line_break_between_blocks_level'])) {
      // $indent = str_repeat('  ', $this->component_data['line_break_between_blocks_level']);
      $line_break_indent = $this->component_data['line_break_between_blocks_level'] * 2;

      $body = [];
      $line_indent = NULL;
      foreach ($yaml_lines as $index => $line) {
        $previous_line_indent = $line_indent;
        $line_indent = strlen($line) - strlen(ltrim($line));

        if ($line_indent == $line_break_indent && $previous_line_indent > $line_indent) {
          $body[] = '';
        }

        $body[] = $line;
      }
    }
    else {
      $body = $yaml_lines;
    }

    return $body;
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
   * TODO: this is not currently run for YAML which puts line breaks between
   * blocks: there's no use case for this yet.
   *
   * @param array $yaml_data_array
   *   The original YAML data array.
   * @param string &$yaml
   *   The generated YAML text.
   */
  protected function expandInlineItems($yaml_data_array, &$yaml) {
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
        // Create a YAML subarray that has the key for the value.
        $key = end($property);
        $yaml_data_sub_array = [
          $key => $value,
        ];

        $yaml_parser = new \Symfony\Component\Yaml\Yaml;

        $original = $yaml_parser->dump($yaml_data_sub_array, 1, static::YAML_INDENT);
        $replacement = $yaml_parser->dump($yaml_data_sub_array, 2, static::YAML_INDENT);

        // We need to put the right indent at the front of all lines.
        // The indent is one level less than the level of the address, which
        // itself is one less than the count of the address array.
        $indent = str_repeat('  ', static::YAML_INDENT * (count($property) - 2));
        $original = $indent . $original;
        $replacement = preg_replace('@^@m', $indent, $replacement);

        // Replace the inlined original YAML text with the multi-line
        // replacement.
        // WARNING: this is a bit dicey, as we might be replacing multiple
        // instances of this data, at ANY level!
        // However, since the only use of this so far is for services.yml
        // file tags, that's not a problem: YAGNI.
        // A better way to do this -- but far more complicated -- might be to
        // replace the data with a  placeholder token before we  generate the
        // YAML, so we are sure we are replacing the right thing.
        $yaml = str_replace($original, $replacement, $yaml);
      }
    }
  }

}
