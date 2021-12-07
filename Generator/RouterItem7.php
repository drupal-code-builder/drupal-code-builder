<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for a router item.
 *
 * This class covers Drupal 6 and 7, where it is purely an intermediary which
 * adds a HookMenu component.
 */
class RouterItem7 extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      'path' => PropertyDefinition::create('string')
        ->setLabel("The menu item path")
        ->setRequired(TRUE),
      'title' => PropertyDefinition::create('string')
        ->setLabel("The page title for the route.")
        ->setInternal(TRUE)
        ->setLiteralDefault('myPage'),
      'description' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      'file' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      'page callback' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault('example_page'),
      'page arguments' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        // These have to be a code string, not an actual array!
        ->setLiteralDefault("array()"),
      'access arguments' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault("array('access content')"),
    ]);

    return $definition;
  }

  /**
   * Declares the subcomponents for this component.
   *
   * @return
   *  An array of subcomponent names and types.
   */
  public function requiredComponents(): array {
    $return = [
      'hooks' => [
        'component_type' => 'Hooks',
        'hooks' => [
          'hook_menu',
        ],
      ],
    ];

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%self:hooks:hook_menu';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildComponentContents($children_contents) {
    // Return code for a single menu item. Our parent in the component tree,
    // HookMenu, will merge it in its own buildComponentContents().
    $code = [];
    $code[] = "£items['{$this->component_data['path']}'] = array(";
    $code[] = "  'title' => '{$this->component_data['title']}',";
    if (isset($this->component_data['description'])) {
      $code[] = "  'description' => '{$this->component_data['description']}',";
    }
    $code[] = "  'page callback' => '{$this->component_data['page callback']}',";
    // This is an array, so not quoted.
    $code[] = "  'page arguments' => {$this->component_data['page arguments']},";
    // This is an array, so not quoted.
    $code[] = "  'access arguments' => {$this->component_data['access arguments']},";
    if (isset($this->component_data['file'])) {
      $code[] = "  'file' => '{$this->component_data['file']}',";
    }
    if (isset($this->component_data['type'])) {
      // The type is a constant, so is not quoted.
      $code[] = "  'type' => {$this->component_data['type']},";
    }
    $code[] = ");";

    return [
      'route' => [
        'role' => 'item',
        'content' => $code,
      ],
    ];
  }

}
