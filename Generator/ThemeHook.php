<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a theme hook in a module, i.e. a themeable element.
 */
class ThemeHook extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      'theme_hook_name' => [
        'label' => 'The theme hook name',
        'internal' => TRUE,
        'primary' => TRUE,
      ],
    ];
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $twig_file_name = $declaration = str_replace('_', '-', $this->component_data['theme_hook_name']) . '.html.twig';

    $components = array(
      'hooks' => array(
        'component_type' => 'Hooks',
        'hooks' => array(
          'hook_theme' => TRUE,
        ),
      ),
      $twig_file_name => array(
        'component_type' => 'TwigFile',
        'filename' => $twig_file_name,
        'theme_hook_name' => $this->component_data['theme_hook_name'],
      ),
    );

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
  protected function buildComponentContents($children_contents) {
    // Return code for a single hook_theme() item.
    $theme_hook_name = $this->component_data['theme_hook_name'];

    $code = array();

    $code[] = "  '$theme_hook_name' => array(";
    $code[] = "    'render element' => 'elements',";
    $code[] = "  ),";

    return [
      'item' => [
        'role' => 'item',
        'content' => $code,
      ],
    ];

  }

}
