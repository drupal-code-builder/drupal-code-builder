<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for a theme hook in a module, i.e. a themeable element.
 */
class ThemeHook extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      // Needs to be set to public even though this is not actually seen.
      'theme_hook_name' => PropertyDefinition::create('string')
        ->setLabel('Theme hook name')
        ->setRequired(TRUE)
        // TODO: doesn't work in UI!
        ->setValidators('machine_name'),
    ]);
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    $twig_file_name = $declaration = str_replace('_', '-', $this->component_data['theme_hook_name']) . '.html.twig';

    $components = [
      'hooks' => [
        'component_type' => 'Hooks',
        'hooks' => [
          'hook_theme',
        ],
      ],
      $twig_file_name => [
        'component_type' => 'TwigFile',
        'filename' => $twig_file_name,
        'theme_hook_name' => $this->component_data['theme_hook_name'],
      ],
    ];

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%self:hooks:hook_theme';
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'element';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    // Return code for a single hook_theme() item.
    $theme_hook_name = $this->component_data['theme_hook_name'];

    $code = [];

    $code[] = "  '$theme_hook_name' => [";
    $code[] = "    'render element' => 'elements',";
    $code[] = "  ],";

    return $code;
  }

}
