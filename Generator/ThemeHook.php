<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a theme hook in a module, i.e. a themeable element.
 */
class ThemeHook extends BaseGenerator {

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   An array of data for the component. Valid properties are:
   *    - 'theme_hook_name': The machine name of the theme hook.
   */
  function __construct($component_name, $component_data, $root_generator) {
    // Set some default properties.
    $component_data += array(
      'theme_hook_name' => $component_name,
    );

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents() {
    $twig_file_name = $declaration = str_replace('_', '-', $this->name) . '.html.twig';

    $components = array(
      'hooks' => array(
        'component_type' => 'Hooks',
        'hooks' => array(
          'hook_theme' => TRUE,
        ),
      ),
      $twig_file_name => array(
        'component_type' => 'TwigFile',
        'theme_hook_name' => $this->component_data['theme_hook_name'],
      ),
    );

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return $this->component_data['root_component_name'] . '/' . 'HookTheme:hook_theme';
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponentContents($children_contents) {
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
