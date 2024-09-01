<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Hooks component for Drupal 11.
 *
 * This is a bit of a special case, as normally class inheritance is higher
 * versions as the parent class. But here 11 is a weird case as it needs to
 * potentially switch the HookImplementation component type.
 */
class Hooks11 extends Hooks {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    // Acquire the hook implementation type configuration from the requesting
    // root component.
    // TODO: This is ugly but is needed because Hooks is standalone data.
    // TODO: This will fail for profiles because they don't have this
    // configuration!
    // TODO: It would be silly for Profiles to have to repeat this configuration
    // for hooks?!
    // $definition->addProperty(PropertyDefinition::create('string')
    //   ->setName('hook_implementation_type')
    //   // AAARGH won't work when Hooks is requested by a single hook!
    //   // e.g with a render element.
    //   ->setAcquiringExpression('requester.configuration.hook_implementation_type.value')
    // );
  }

  /**
   * {@inheritdoc}
   */
  protected function getHookImplementationComponentType(string $hook_name): string {
    $hook_class_name = parent::getHookImplementationComponentType($hook_name);

    // YAY!
    // dump($this->component_data->getItem('module:configuration:entity_handler_namespace')->value);
    // $hook_implementation_type = $this->component_data->getItem('module:configuration:hook_implementation_type')->value;

    // dump(Hooks::$hook_implementation_type);

    // TODO: Specialised hook generators.
    if ($hook_class_name == 'HookImplementation' && Hooks::$hook_implementation_type == 'oo') {
      $hook_class_name = 'HookImplementationClassMethod';
    }
    // dump($hook_class_name);
    // $hook_class_name = 'HookImplementationClassMethod';

    return $hook_class_name;
  }

}


