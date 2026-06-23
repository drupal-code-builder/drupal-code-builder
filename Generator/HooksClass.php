<?php

namespace DrupalCodeBuilder\Generator;

use Drupal\Component\DependencyInjection\ReverseContainer;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\File\DrupalExtension;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\PropertyListInterface;

/**
 * Generator for a hooks class for D11+.
 *
 * This adds HookImplementationClassMethod generators, each of which will take
 * care of generating a legacy procedural hook if needed.
 */
class HooksClass extends Service {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->getProperty('service_tag_type')->setInternal(TRUE);
    $definition->getProperty('service_name')->setInternal(TRUE);
    $definition->getProperty('decorates')->setInternal(TRUE);
    $definition->getProperty('tags')->setInternal(TRUE);

    // Move the form class name property to the top, and make it user-set rather
    // than internal with a default.
    $definition->getProperty('plain_class_name')
      ->setLabel("Hooks class name")
      ->setInternal(FALSE)
      ->setDescription("The hooks class's plain class name, e.g. \"MyHooks\".")
      ->setCallableDefault(function ($component_data) {
        // Add a suffix to the default class name based on the human-readable
        // index.
        $delta = $component_data->getParent()->getName();
        $suffix = match ($delta) {
          '0' => '',
          default => $delta + 1,
        };

        return $component_data->getParent()->root_name_pascal->value . 'Hooks' . $suffix;
      });
    $definition->getProperty('relative_namespace')
      ->setDefault(DefaultDefinition::create()
        ->setLiteral('Hook')
      );

    // The service name is the same as the class name.
    $definition->getProperty('service_name_prefix')->setLiteralDefault('');
    $definition->getProperty('service_name')->setExpressionDefault("get('..:qualified_class_name')");
    $definition->getProperty('autowire')->setLiteralDefault(TRUE);

    $definition->getProperty('class_docblock_lines')
      ->setDefault(
        DefaultDefinition::create()
          ->setLiteral(['Contains hook implementations for the %readable %base.'])
      );

    $definition->addPropertyBefore(
      'injected_services',
      MergingGeneratorDefinition::createFromGeneratorType('HookImplementationClassMethod')
        ->setName('hook_methods')
        ->setDescription('Hook implementations in this class. The same hook can be added multiple times.')
        ->setLabel('Hook implementations')
        ->setMultiple(TRUE)
        ->setProcessing(function(DataItem $component_data) {
          $component_data->containing_component = '%requester';
          $component_data->class_component_address = '..:..';
        }),
    );

    $definition->addProperty(PropertyDefinition::create('string')
      ->setName('root_name_pascal')
      ->setInternal(TRUE)
      ->setExpressionDefault("get('..:..:..:root_name_pascal')")
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getDifferentiatedLabelSuffix(DataItem $data): ?string {
    return $data->plain_class_name->value ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function findAdoptableComponents(DrupalExtension $extension): array {
    $finder = $extension->getFinder();
    $finder
      ->path(['src/Hook'])
      ->files()
      ->ignoreUnreadableDirs();

    $adoptable_items = [];
    foreach ($finder as $file) {
      $relative_pathname = $file->getRelativePathname();

      $adoptable_items[$relative_pathname] = $relative_pathname;
    }

    return $adoptable_items;
  }

  /**
   * {@inheritdoc}
   */
  public static function adoptComponent(DataItem $component_data, DrupalExtension $extension, string $property_name, string $name): void {
    // Include the class file if necessary so we can use reflection on it.
    $hooks_classname = $extension->getClassName($name);
    if (!class_exists($hooks_classname)) {
      $extension->includeFile($name);
    }

    $class_reflection = new \ReflectionClass($hooks_classname);

    $adopted_data = [];

    // Get the short class name.
    $adopted_data['plain_class_name'] = basename($name, '.php');

    // If the class has a constructor, then it is injecting services, which we
    // need to analyse.
    if ($class_reflection->hasMethod('__construct')) {
      $container = \DrupalCodeBuilder\Factory::getEnvironment()->getContainer();
      $reverse_container = $container->get(ReverseContainer::class);

      $constructor_reflection = new \DrupalCodeBuilder\Utility\CodeAnalysis\Method($hooks_classname, '__construct');
      $param_data = $constructor_reflection->getParamData();
      foreach ($param_data as $param_data_item) {
        // Rely on hook classes being autowired, so all their parameters' types
        // must be registered as services.
        $parameter_service = $container->get($param_data_item['type']);

        // The service parameter types will typically be service aliases. Get
        // the real service name from the reverse container.
        $parameter_service_id = $reverse_container->getId($parameter_service);

        $adopted_data['injected_services'][] = $parameter_service_id;
      }
    }

    // Find hook methods.
    $hook_names = \DrupalCodeBuilder\Factory::getTask('ReportHookData')->listHookNames('short');
    $patterned_tokenized_hook_names = NULL;

    $hooks_class_code = $extension->getFileContents($name);
    $matches = [];
    // Quick and dirty: get the Hook attribute parameter values.
    // @todo This won't work with more complex hook implementation which use
    // weights or delegate modules.
    preg_match_all('@#\[Hook\(\'(\w+)\'@', $hooks_class_code, $matches);
    if (!empty($matches[1])) {
      $hook_matches = $matches[1];
      foreach ($hook_matches as $attribute_hook_name) {
        if (in_array($attribute_hook_name, $hook_names)) {
          // The hook name in the attribute is a literal hook name.
          $adopted_data['hook_methods'][] = [
            'hook_name' => 'hook_' . $attribute_hook_name,
          ];
        }
        else {
          // The hook name in the attribute is tokenised.
          // Lazily get the tokenised hook names.
          if (!isset($patterned_tokenized_hook_names)) {
            $patterned_tokenized_hook_names = \DrupalCodeBuilder\Factory::getTask('ReportHookData')->getRegexTokenisedHookNames();
          }

          foreach ($patterned_tokenized_hook_names as $hook_name => $hook_pattern) {
            $matches = [];
            if (preg_match($hook_pattern, $attribute_hook_name, $matches)) {
              $adopted_data['hook_methods'][] = [
                'hook_name' => $hook_name,
                // Slice off the first of the matches array, as that's the whole
                // pattern.
                'hook_name_parameters' => array_slice($matches, 1),
              ];

              break;
            }
          }
        }
      }
    }

    // Check if we already have this hooks class.
    $adopted_class_exists = FALSE;
    foreach ($component_data->hook_classes as $existing_hook_class) {
      // Accessing the class name value will get the default if there is no
      // user-set class name, and that can then be compared with the name of the
      // adopted class.
      if ($existing_hook_class->plain_class_name->value == $adopted_data['plain_class_name']) {
        $adopted_class_exists = TRUE;

        // We've found a matching hooks class we already have data for.
        // Now see if any hooks already exist.
        foreach ($adopted_data['hook_methods'] as $delta => $adopted_hook_method_data) {
          foreach ($existing_hook_class->hook_methods as $existing_hook_method) {
            if ($existing_hook_method->hook_name->value == $adopted_hook_method_data['hook_name']) {
              // If the hook matches an existing one, remove it from the
              // adopted data.
              unset($adopted_data['hook_methods'][$delta]);
              continue;
            }
          }
        }

        // Add any remaining hook methods to the existing hooks class.
        foreach ($adopted_data['hook_methods'] as $adopted_hook_method_data) {
          $existing_hook_class->hook_methods[] = $adopted_hook_method_data;
        }
      }
    }

    // Add the entire adopted data if we didn't find a matching existing hooks
    // class.
    if (!$adopted_class_exists) {
      $component_data->hook_classes[] = $adopted_data;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // If there's no legacy support, this service class doesn't need to be
    // declared.
    if ($this->component_data->getItem('module:hook_implementation_type')->value == 'oo') {
      unset($components['%module.services.yml']);
    }

    return $components;
  }

}

