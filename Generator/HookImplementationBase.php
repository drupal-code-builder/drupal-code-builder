<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\PropertyListInterface;
use MutableTypedData\Definition\VariantDefinition;

/**
 * Abstract base class for hook implementations.
 *
 * This is specialised with child classes for:
 * - class method hooks
 * - procedural hooks
 * - specific hooks that collect contents which are only procedural, e.g.
 *   hook_menu()
 * - hook_updateN() which needs to change the function name.
 *
 * Furthermore, hooks that collect contents and can be procedural or OO use
 * a hook body class, e.g. hook_theme().
 */
abstract class HookImplementationBase extends PHPFunction {

  /**
   * {@inheritdoc}
   */
  protected static $dataType = 'mutable';

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    // Make a dummy property list to get parent properties.
    $common_properties = PropertyDefinition::create('complex');
    parent::addToGeneratorDefinition($common_properties);

    $variants = [
      'literal' => VariantDefinition::create()
        ->setLabel('Literal'),
      'tokenized' => VariantDefinition::create()
        ->setLabel('Tokenized'),
    ];

    $definition->setProperties([
      'hook_name' => PropertyDefinition::create('string')
        ->setLabel('Hook')
        ->setRequired(TRUE)
        ->setOptionSetDefinition(\DrupalCodeBuilder\Factory::getTask('ReportHookData')),
      ])
      ->setVariantMappingProvider(\DrupalCodeBuilder\Factory::getTask('ReportHookData'))
      ->setVariants($variants);

    $variants['literal']->addProperties([
      // The short hook name.
      'short_hook_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(fn ($component_data) => preg_replace('@^hook_@', '', $component_data->getParent()->hook_name->value)),
    ]);

    $variants['tokenized']->addProperties([
      'hook_name_parameters' => PropertyDefinition::create('string')
        ->setLabel('Hook name replacement parameters')
        ->setDescription("Replacement values for the tokens in a hook name such as 'FORM_ID' or 'ENTITY_TYPE'. Enter the values to replace these in their order in the hook name.")
        ->setMultiple(TRUE),
      // The short hook name, with tokens replaced with any given parameters.
      // We replace tokens only once, in this property. Hook function / method
      // name then derive from this. For class method hooks, this is also used
      // for the attribute.
      'short_hook_name' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setCallableDefault(function ($component_data) {
          $short_hook_name = preg_replace('@^hook_@', '', $component_data->getParent()->hook_name->value);

          $hook_name_parameters = $component_data->getParent()->hook_name_parameters->values();

          // Split on an UPPER_CASE token in the hook name, without the bounding
          // underscores, and include the token in the pieces. For example,
          // 'form_FORM_ID_alter' becomes:
          // - form_
          // - FORM_ID
          // - _alter
          $pieces = preg_split(
            '@(?<=_)([[:upper:]_]+)(?=_)@',
            $short_hook_name,
            flags: PREG_SPLIT_DELIM_CAPTURE,
          );

          // Because hook names never start with a token, we know that the
          // tokens are all the odd-indexed pieces, and the fixed portions the
          // even-indexed.
          foreach ($pieces as $i => &$piece) {
            if ($i % 2 == 1) {
              // An odd-indexed piece is replaced with hook name parameter if
              // one exists.
              if ($hook_name_parameters) {
                // Use up each parameter, so the array empties out.
                $parameter = array_shift($hook_name_parameters);
                $piece = $parameter;
              }
            }
          }

          $short_hook_name = implode('', $pieces);
          return $short_hook_name;
        })
    ]);

    foreach ($variants as $variant) {
      $variant->addProperties($common_properties->getProperties());

      $variant->addProperties([
        // The name of the file that this hook implementation should be placed
        // into.
        // For HookImplementationClassMethod this is unused, but simpler to have
        // this here rather than have Hooks decide whether to set it or not. Plus
        // we might use it at some point to decide which class to use.
        'code_file' => PropertyDefinition::create('string')
          ->setInternal(TRUE)
          ->setLiteralDefault('%module.module'),
        // The long hook name.
        'hook_name' => PropertyDefinition::create('string')
          ->setLabel('Hook')
          ->setRequired(TRUE)
          ->setOptionSetDefinition(\DrupalCodeBuilder\Factory::getTask('ReportHookData')),
        'hook_info' => PropertyDefinition::create('mapping')
          ->setInternal(TRUE)
          ->setCallableDefault(function ($component_data) {
            $task_handler_report = \DrupalCodeBuilder\Factory::getTask('ReportHookData');
            $hook_info = $task_handler_report->getHookDeclarations()[strtolower($component_data->getParent()->hook_name->value)];
            return $hook_info;
          }),
        // The first docblock line from the hook's api.php definition.
        'description' => PropertyDefinition::create('string')
          ->setInternal(TRUE),
      ]);

      $variant->getProperty('function_docblock_lines')->getDefault()
        // Expression Language lets us define arrays, which is nice.
        ->setExpression("['Implements ' ~ get('..:hook_name') ~ '().']");

      // This appears to be necessary even though it's not used. WTF!
      $variant->getProperty('function_name')
        ->setCallableDefault(function ($component_data) {
          $short_hook_name = $component_data->getParent()->short_hook_name->value;
          $function_name = '%module_' . $short_hook_name;
          return $function_name;
        });

      // Hook bodies are just sample code from the code documentation, so if
      // there are contained components, these should override the sample code.
      $variant->getProperty('body_overriden_by_contained')
        ->setLiteralDefault(TRUE);

        $variant->getProperty('body_indented')
        ->setLiteralDefault(TRUE);

      // Hook implementations have no @return documentation.
      $variant->getProperty('return')->getProperty('omit_return_tag')
        ->setLiteralDefault(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // Determine if there is a hook body generator for this hook.
    // We need dynamic hook bodies to be a separate generator so they are
    // orthogonal to hook implementations being prodecural/class methods.
    $long_hook_name = $this->component_data->hook_name->value;
    $hook_class_name = 'HookBody' . CaseString::snake($long_hook_name)->pascal();
    // Make the fully qualified class name.
    $hook_class = $this->classHandler->getGeneratorClass($hook_class_name);
    if (class_exists($hook_class)) {
      $components['body'] = [
        'component_type' => $hook_class_name,
        'containing_component' => '%requester',
      ];
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    // Allow for subclasses that provide their own body code, which is not
    // indented.
    // TODO: clean this up!
    if (!$this->containedComponents->isEmpty()) {
      $this->component_data->body_indented = FALSE;
    }

    return parent::getContents();
  }

}
