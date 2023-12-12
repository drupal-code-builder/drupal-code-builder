<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator base class for module README file.
 *
 * (You were going to write a README file, right?)
 *
 * Template text and ordering of sections is based on documentation at
 * https://www.drupal.org/docs/develop/managing-a-drupalorg-theme-module-or-distribution-project/documenting-your-project/readmemd-template.
 */
class Readme extends File {

  /**
   * {@inheritdoc}
   */
  protected static $dataType = 'boolean';

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition($definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'readable_name' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);

    $definition->getProperty('filename')
      // The extension is in lowercase for good reasons which I don't remember
      // right now, but probably to do with Windows being rubbish.
      ->setLiteralDefault('README.md');
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%nearest_root';
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return 'readme';
  }

  /**
   * Collect the code files.
   */
  public function getFileInfo() {
    return [
      'path' => '', // Means the base folder.
      // The extension is in lowercase for good reasons which I don't remember
      // right now, but probably to do with Windows being rubbish.
      'filename' => 'README.md',
      'body' => $this->lines(),
      'build_list_tags' => ['readme'],
    ];
  }

  /**
   * Return an array of lines.
   *
   * @return
   *  An array of lines of text.
   */
  function lines() {
    $body = [
      '# ' . $this->component_data['readable_name'],
      '',
      'TODO: write some documentation.',
      '',
    ];

    // Order for the sections.
    $headings_order = [
      'Requirements',
      'Installation',
      'Configuration',
    ];

    $sections = $this->getContainedComponentSections();

    // Order the sections by the ordering array, considering that some sections
    // might not be present.
    $sections = array_filter(array_merge(array_fill_keys($headings_order, NULL), $sections));

    foreach ($sections as $title => $section_text) {
      $body[] = '## ' . $title;
      $body[] = '';

      foreach ($section_text as $line) {
        $wrapped_line = wordwrap($line, 80);
        $body = array_merge($body, explode("\n", $wrapped_line));
      }
    }

    return $body;
  }

  /**
   * Gets additional info lines from contained components.
   *
   * @return array
   */
  protected function getContainedComponentSections(): array {
    $lines = [];
    foreach ($this->containedComponents['section'] as $key => $child_item) {
      $contents = $child_item->getContents();

      // Assume that children components don't tread on each others' toes and
      // provide the same property names.
      $lines[array_key_first($contents)] = reset($contents);
    }

    return $lines;
  }


}
