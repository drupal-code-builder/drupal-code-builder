<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for a twig template.
 */
class TwigFile extends File {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition($definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      'theme_hook_name' => PropertyDefinition::create('string')
        ->setLabel('The theme hook name'),
    ]);
  }

  /**
   * Return the data for the file this component provides.
   */
  public function getFileInfo() {
    return [
      'path' => 'templates',
      'filename' => $this->component_data['filename'],
      'use_file_info_filename' => TRUE,
      'body' => [
        $this->getTwigContents(),
      ],
    ];
  }

  protected function getTwigContents() {
    $theme_hook_name = $this->component_data['theme_hook_name'];

    $twig = <<<EOT
      {#
      /**
       * @file
       * Default theme implementation to display a $theme_hook_name.
       *
       * Available variables:
       * - todo:
       *
       * @ingroup themeable
       */
      #}
      <article>
        Content here.
      </article>
      EOT;

    return $twig;
  }

}
