<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\DefaultDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\File\DrupalExtension;
use DrupalCodeBuilder\Utility\NestedArray;
use MutableTypedData\Data\DataItem;
use PhpParser\NodeFinder;

/**
 * Generator class for forms on Drupal 8 and higher.
 *
 * Note that entity forms use the EntityForm generator which does *not*
 * inherit from this class!
 */
class Form extends PHPClassFileWithInjection implements AdoptableInterface {

  protected $hasStaticFactoryMethod = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $properties = [
      // Move the form class name property to the top, and override its default.
      'plain_class_name' => $definition->getProperty('plain_class_name')
        ->setLabel("Form class name.")
        ->setDescription("The form class's plain class name, e.g. \"MyForm\"."),
      'form_id' => PropertyDefinition::create('string')
        ->setLabel('The form ID.')
        ->setInternal(TRUE)
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("get('..:root_component_name') ~ '_' ~ machineFromPlainClassName(get('..:plain_class_name'))")
            ->setDependencies('..:root_component_name', '..:plain_class_name')
        ),
      'injected_services' => PropertyDefinition::create('string')
        ->setLabel('Injected services')
        ->setDescription("Services to inject. Additionally, use 'storage:TYPE' to inject entity storage handlers.")
        ->setMultiple(TRUE)
        ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportServiceData')),
      'form_elements' => static::getLazyDataDefinitionForGeneratorType('FormElement')
        ->setLabel('Form elements')
        ->setMultiple(TRUE),
    ];

    // Put the rest of the parent definitions after ours.
    $definition->addProperties($properties);

    // Put the class in the 'Form' relative namespace.
    $definition->getProperty('relative_namespace')
      ->setLiteralDefault('Form');

    $definition->getProperty('plain_class_name')
      ->setLiteralDefault('MyForm');

      $definition->getProperty('relative_class_name')->setInternal(TRUE);

    // Set the parent class.
    $definition->getProperty('parent_class_name')
      ->setLiteralDefault('\Drupal\Core\Form\FormBase');

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function findAdoptableComponents(DrupalExtension $extension): array {
    $finder = $extension->getFinder();
    $finder
      // Some module stupidly put forms at the top level.
      ->path(['src', 'src/Form'])
      ->name('*Form.php')
      ->ignoreUnreadableDirs();

    $adoptable_items = [];
    foreach ($finder as $file) {
      $relative_pathname = $file->getRelativePathname();
      // TODO: Check class with reflection for interface/base class? Or too
      // fiddly?

      $adoptable_items[$relative_pathname] = $relative_pathname;
    }

    return $adoptable_items;
  }

  /**
   * {@inheritdoc}
   */
  public static function adoptComponent(DataItem $component_data, DrupalExtension $extension, string $property_name, string $name): void {
    $form_ast = $extension->getFileAST($name);

    $nodeFinder = new NodeFinder;
    $namespace = $nodeFinder->findInstanceOf($form_ast, \PhpParser\Node\Stmt\Namespace_::class);
    $classes = $nodeFinder->findInstanceOf($form_ast, \PhpParser\Node\Stmt\Class_::class);

    $methods = $extension->getASTMethods($form_ast);
    $injected_services = [];
    if (isset($methods['create'])) {
      foreach ($methods['create']->stmts[0]->expr->args as $creation_arg) {
        $injected_services[] = $creation_arg->value->args[0]->value->value;
      }
    }

    // Not all forms implement getFormId(): entity forms, for example.
    if (isset($methods['getFormId'])) {
      $form_id = $methods['getFormId']->stmts[0]->expr->value;
    }

    $relative_namespace_pieces = array_slice($namespace[0]->name->parts, 2);
    $relative_namespace = implode('\\', $relative_namespace_pieces);
    $relative_class_name = $relative_namespace . '\\' . $classes[0]->name->name;

    $value = [
      // Have to set this as well as the relative class name.
      'plain_class_name' => $classes[0]->name->name,
      // Have to set this in case the form class file is in a stupid place.
      'relative_namespace' => $relative_namespace,
      'relative_class_name' => $relative_class_name,
      'injected_services' => $injected_services,
      'form_id' => $form_id ?? NULL,
    ];

    foreach ($component_data->getItem($property_name) as $delta => $delta_item) {
      if ($delta_item->relative_class_name->value == $value['relative_class_name']) {
        $merge_delta = $delta;
        break;
      }
    }

    if (isset($merge_delta)) {
      $existing_value = $component_data->getItem($property_name)[$merge_delta]->export();
      $merged_value = NestedArray::mergeDeep($existing_value, $value);

      $component_data->getItem($property_name)[$merge_delta]->set($merged_value);
    }
    else {
      // Bit of a WTF: this requires this class to know it's being used as a
      // multi-valued item in the Module generator.
      $item_data = $component_data->getItem($property_name)->createItem();
      $item_data->set($value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $form_name = $this->component_data['form_id'];

    $components += [
      // Request the form functions.
      'getFormId' => [
        'component_type' => 'PHPFunction',
        'function_name' => 'getFormId',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function getFormId()',
        'body' => [
          "return '$form_name';",
        ],
      ],
      'buildForm' => [
        'component_type' => 'FormBuilder',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'function_name' => 'buildForm',
        'body' => [
          "// Uncomment this line if you change the base class.",
          "// £form = parent::buildForm(£form, £form_state);",
          "",
          "£form['element'] = [",
          "  '#type' => 'textfield',",
          "  '#title' => £this->t('Enter a value'),",
          "  '#description' => £this->t('Enter a description'),",
          "  '#default_value' => 'enter the default value',",
          "  '#required' => TRUE,",
          "];",
          "",
          "£form['submit'] = [",
          "  '#type' => 'submit',",
          "  '#value' => £this->t('Submit'),",
          "];",
          "",
          "return £form;",
        ],
      ],
      'validateForm' => [
        'component_type' => 'PHPFunction',
        'function_name' => 'validateForm',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)',
        'body' => '',
      ],
      'submitForm' => [
        'component_type' => 'PHPFunction',
        'function_name' => 'submitForm',
        'containing_component' => '%requester',
        'docblock_inherit' => TRUE,
        'declaration' => 'public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)',
        'body' => '',
      ],
    ];

    foreach ($this->component_data['injected_services'] as $service_id) {
      $components['service_' . $service_id] = [
        'component_type' => 'InjectedService',
        'containing_component' => '%requester',
        'service_id' => $service_id,
        'class_has_static_factory' => $this->hasStaticFactoryMethod,
        'class_has_constructor' => TRUE,
        'class_name' => $this->component_data->qualified_class_name->value,
      ];
    }

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    $this->collectSectionBlocksForDependencyInjection();
  }

}
