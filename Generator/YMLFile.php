<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Utility\NestedArray;

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
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'yaml_data' => [
        'label' => 'The data for the YAML file.',
        'format' => 'array',
        'internal' => TRUE,
      ],
      'yaml_inline_level' => [
        'label' => 'The level at which to switch YAML properties to inline formatting.',
        'format' => 'string',
        'default' => 6,
        'internal' => TRUE,
      ],
      'line_break_between_blocks' => [
        'label' => 'Whether to add line breaks between the top-level properties.',
        'format' => 'boolean',
        'default' => FALSE,
        'internal' => TRUE,
      ],
      'inline_levels_extra' => [
        'default' => FALSE,
        'internal' => TRUE,
      ],
    ];
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
  protected function buildComponentContents($children_contents) {
    $yaml_data = array();
    foreach ($this->filterComponentContentsForRole($children_contents, 'yaml') as $component_name => $component_yaml_data) {
      $yaml_data += $component_yaml_data;
    }

    // TEMPORARY, until Generate task handles returned contents.
    if (!empty($yaml_data)) {
      // Only zap this if children provide something, as other components still
      // set this property by request.
      $this->component_data['yaml_data'] = $yaml_data;
    }

    return array();
  }

  /**
   * Build the code files.
   */
  public function getFileInfo() {
    return array(
      'path' => '', // Means base folder.
      'filename' => $this->component_data['filename'],
      'body' => $this->getYamlBody($this->component_data['yaml_data']),
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
    $yaml_parser = new \Symfony\Component\Yaml\Yaml;

    $yaml_parser_inline_switch_level = $this->component_data['yaml_inline_level'];

    if ($this->component_data['line_break_between_blocks']) {
      $body = [];

      foreach (range(0, count($yaml_data_array) -1 ) as $index) {
        $yaml_slice = array_slice($yaml_data_array, $index, 1);

        // Each YAML piece comes with a terminal newline, so when these are
        // joined there will be the desired blank line between each section.
        $body[] = $yaml_parser->dump($yaml_slice, $yaml_parser_inline_switch_level, static::YAML_INDENT);
      }
    }
    else {
      $yaml = $yaml_parser->dump($yaml_data_array, $yaml_parser_inline_switch_level, static::YAML_INDENT);

      $this->expandInlineItems($yaml_data_array, $yaml);

      // Because the yaml is all built for us, this is just a singleton array.
      $body = array($yaml);
    }
    //drush_print_r($yaml);

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
