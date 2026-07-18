<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use MutableTypedData\Definition\PropertyListInterface;
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
      'theme_hook_name' => PropertyDefinition::create('string')
        ->setLabel('Theme hook name')
        ->setRequired(TRUE)
        // TODO: doesn't work in UI!
        ->setValidators('machine_name'),
      'initial_preprocess' => PropertyDefinition::create('boolean')
        ->setLabel('Preprocess hook')
        ->setDescription('Add an initial_preprocess function (also known as a template_preprocess_HOOK()).'),
      'initial_preprocess_method_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(function ($component_data) {
          return 'preprocess' . CaseString::snake($component_data->getParent()->theme_hook_name->value)->pascal();
        }),
    ]);
  }

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    $theme_hook_name = $this->component_data->theme_hook_name->value;

    $twig_file_name = $declaration = str_replace('_', '-', $theme_hook_name) . '.html.twig';

    $components = [
      'hooks' => [
        'component_type' => 'Hooks',
        'hooks' => [
          'hook_theme',
        ],
      ],
      $twig_file_name => [
        'component_type' => 'TwigFile',
        'template_name' => $twig_file_name,
        'theme_hook_name' => $theme_hook_name,
      ],
    ];

    if (!$this->component_data->initial_preprocess->isEmpty()) {
      $components['initial_preprocess'] = [
        'component_type' => 'PHPFunction',
        'function_name' => $this->component_data->initial_preprocess_method_name->value,
        'function_docblock_lines' => [
          "Initial preprocess for the {$theme_hook_name} theme hook.",
        ],
        // @todo This is brittle and assumes the hook_theme() won't go into
        // a specialised hooks class, but AFAIK at the moment it doesn't.
        'containing_component' => '%requester:hooks:hooks_class',
        'prefixes' => ['public'],
        'parameters' => [
          0 => [
            'name' => 'variables',
            'by_reference' => TRUE,
            'typehint' => 'array',
            'description' => "The theme hook variables.",
          ],
        ],
        'return' => [
          'return_type' => 'void',
        ],
      ];

      // Add a legacy procedural hook for pre-11.2 BC.
      // See https://www.drupal.org/node/3504125.
      $components['initial_preprocess_legacy'] = [
        'component_type' => 'PHPFunction',
        'containing_component' => '%requester:%module.module',
        'function_name' => 'template_preprocess_' . $theme_hook_name,
        'parameters' => $components['initial_preprocess']['parameters'],
        'return' => [
          'omit_return_tag' => TRUE,
        ],
        'function_docblock_lines' => $components['initial_preprocess']['function_docblock_lines'],
        'body' => [
          '\Drupal::service(%PascalHooks::class)->' . $this->component_data->initial_preprocess_method_name->value . '(£variables);'
        ],
      ];

      $components['%module.module'] = [
        'component_type' => 'ExtensionCodeFile',
        'filename' => '%module.module',
      ];
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function containingComponent() {
    return '%self:hooks:hook_theme:body';
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
    $theme_hook_name = $this->component_data->theme_hook_name->value;

    $code = [];

    $code[] = "  '$theme_hook_name' => [";
    $code[] = "    'render element' => 'elements',";

    if (!$this->component_data->initial_preprocess->isEmpty()) {
      $code[] = "    'initial preprocess' => static::class . ':" . $this->component_data->initial_preprocess_method_name->value . "',";
    }

    $code[] = "  ],";

    return $code;
  }

}
