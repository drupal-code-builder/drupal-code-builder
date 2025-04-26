<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for a single OO hook implementation.
 */
class HookImplementationClassMethod extends HookImplementationBase {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    // Change the options provider to exclude obligate procedural hooks.
    $definition->getProperty('hook_name')
      ->setOptionSetDefinition(\DrupalCodeBuilder\Factory::getTask('ReportHookClassMethodData'));

    $variants = $definition->getVariants();

    // For a literal hook, the method name is the camel case version of the
    // short hook name, e.g. 'formAlter()'.
    $variants['literal']->addProperty(PropertyDefinition::create('string')
      ->setName('hook_method_name')
      ->setInternal(TRUE)
      ->setCallableDefault(function ($component_data) {
        $short_hook_name = $component_data->getParent()->short_hook_name->value;
        return CaseString::snake($short_hook_name)->camel();
      }),
    );

    $variants['tokenized']->addProperty(PropertyDefinition::create('string')
      ->setName('hook_method_name')
      ->setInternal(TRUE)
      ->setCallableDefault(function ($component_data) {
        // The short_hook_name already has had tokens replaced.
        $short_hook_name = $component_data->getParent()->short_hook_name->value;
        return CaseString::snake($short_hook_name)->camel();
      }),
    );

    // Add or update properties common to both variants.
    foreach ($variants as $variant) {
      // The address to get the class component that holds this method.
      $variant->addProperty(PropertyDefinition::create('string')
        ->setName('class_component_address')
        ->setInternal(TRUE)
      );

      // This needs both a default and processing, as in some cases this gets
      // set by the requester.
      // TODO: always derive this in default.
      $variant->getProperty('declaration')
        ->setCallableDefault(function ($component_data) {
          $hook_info = $component_data->getParent()->hook_info->value;

          $declaration = $hook_info['definition'];

          // Run the default through processing, as that's not done
          // automatically.
          $processing = $component_data->getDefinition()->getProcessing();
          $component_data->set($declaration);
          $processing($component_data);

          return $component_data->get();
        })
        ->setProcessing(function(DataItem $component_data) {
          // Replace the hook name from the hook info's declaration with the
          // method name.
          $declaration = preg_replace(
            '/(?<=function )hook_(\w+)/',
            $component_data->getParent()->hook_method_name->get(),
            $component_data->get()
          );

          // Add the 'public' modifier.
          $declaration = 'public ' . $declaration;

          $component_data->set($declaration);
        });

      $variant->getProperty('body')
        ->setCallableDefault(function ($component_data) {
          $hook_name = $component_data->getParent()->hook_name->value;
          $hook_info = $component_data->getParent()->hook_info->value;

          $template = $hook_info['body'];

          // This needs to be split into an array of lines for things such as
          // PHPFile::extractFullyQualifiedClasses() to work.
          $template = explode("\n", $template);

          // Trim lines from start and end of body, as hook definitions
          // have newlines at start and end.
          $template = array_slice($template, 1, -1);

          return $template;
        });

      $variant->addProperties([
        'class_name' => PropertyDefinition::create('string')
          ->setInternal(TRUE),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    // Allow multiple copies of the same hook.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // Add a legacy procedural hook if required.
    // Declaring the hooks class as a service for legacy is handled in
    // HooksClass.
    if ($this->component_data->getItem('module:hook_implementation_type')->value == 'oo_legacy') {
      $hook_name = $this->component_data->hook_name->value;

      $mb_task_handler_report = \DrupalCodeBuilder\Factory::getTask('ReportHookData');
      $hook_info = $mb_task_handler_report->getHookDeclarations()[strtolower($this->component_data->hook_name->value)];

      $component_name = $hook_name . '_legacy';

      $class_name_component_address = $this->component_data->class_component_address->value;
      $hooks_class_name = $this->component_data->getItem($class_name_component_address)->qualified_class_name->value;

      // The legacy hook body is just a call to the hook method.
      $hook_method_name = $this->component_data->hook_method_name->value;
      // Get the parameters.
      $matches = [];
      preg_match_all('@(\$\w+)@', $hook_info['definition'], $matches);
      $arguments = implode(', ', $matches[0]);

      // Use a return statement if the hook returns a value.
      $return = !empty($hook_info['has_return']) ? 'return ' : '';

      $legacy_method_body_line = "{$return}\Drupal::service(\\{$hooks_class_name}::class)->{$hook_method_name}({$arguments});";

      $components[$component_name] = [
        // We don't need to check for specialised hook generators, as there's no
        // special function body for the legacy hook.
        'component_type' => 'HookImplementationProcedural',
        'code_file' => $hook_info['destination'],
        'hook_name' => $hook_name,
        'short_hook_name' => $this->component_data->short_hook_name->value,
        'attribute' => 'Drupal\Core\Hook\LegacyHook',
        'description' => $hook_info['description'],
        'function_docblock_lines' => [
          'Legacy hook implementation.',
          '@todo Remove this method when support for Drupal core < 11.1 is dropped.',
        ],
        'body' => [$legacy_method_body_line],
        // The code is a single string, already indented. Ensure we don't
        // indent it again.
        'body_indented' => FALSE,
      ];
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFunctionAttributes(): array {
    $attribute = PhpAttributes::method(
      '\Drupal\Core\Hook\Attribute\Hook',
      $this->component_data->short_hook_name->value,
    );
    return [$attribute];
  }

}
