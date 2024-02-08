<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\OptionDefinition;

/**
 * Component generator: profile.
 *
 * This is a root generator: that is, it's one that may act as the initial
 * requested component given to Task\Generate. (In theory, this could also get
 * requested by something else, for example if we wanted Tests to be able to
 * request a testing module, but that's for another day.)
 *
 * Conceptual hierarchy of generators beneath this in the request tree:
 */
class Profile extends RootComponent {

  /**
   * The sanity level this generator requires to operate.
   */
  public static $sanity_level = 'none';

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition
      ->setLabel('Profile');

    $definition->addProperties([
      'base' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault('profile'),
      'readable_name' => PropertyDefinition::create('string')
        ->setLabel('Profile readable name')
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("machineToLabel(get('..:root_name'))")
            ->setDependencies('..:root_name')
        ),
      'short_description' => PropertyDefinition::create('string')
        ->setLabel('Profile .info file description')
        ->setLiteralDefault('TODO: Description of profile'),
      'dependencies' => PropertyDefinition::create('string')
        ->setLabel('Profile dependencies')
        ->setDescription('The machine names of the modules this profile has as dependencies.')
        ->setMultiple(TRUE)
        // We need a value for this, as other generators acquire it.
        ->setLiteralDefault([]),
      'install' => PropertyDefinition::create('string')
        ->setLabel('Profile installs')
        ->setDescription('The machine names of the modules this profile installs but does not depend on.')
        ->setMultiple(TRUE)
        // We need a value for this, as other generators acquire it.
        ->setLiteralDefault([]),
      'hooks' => PropertyDefinition::create('string')
        ->setLabel('Hook implementations')
        ->setMultiple(TRUE)
        // TODO: Make ReportHookData into an options provider.
        ->setOptions(...array_map(
          function($hook_data_item) {
            return OptionDefinition::create(
              $hook_data_item['name'],
              $hook_data_item['name'],
              $hook_data_item['description'] ?? ''
            );
          },
          array_values(\DrupalCodeBuilder\Factory::getTask('ReportHookData')->getHookDeclarations())
        )),
    ]);

    $definition->getProperty('root_name')
      ->setLabel('Profile machine name')
      ->setLiteralDefault('my_profile');
  }

 /**
  * {@inheritdoc}
  */
 public static function rootComponentPropertyDefinitionAlter(PropertyDefinition $definition): void {
 }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $root_name = $this->component_data['root_name'];

    $components['info'] = [
      'component_type' => 'InfoProfile',
    ];

    $components['install'] = [
      'component_type' => 'ExtensionCodeFile',
      'filename' => '%extension.install',
    ];

    $components['profile'] = [
      'component_type' => 'ExtensionCodeFile',
      'filename' => '%extension.profile',
    ];

    return $components;
  }

  /**
   * {@inheritdoc}
   */
  function getReplacements() {
    return [
      '%base'         => $this->component_data->base->value,
      '%extension'    => $this->component_data->root_name->value,
      '%readable'     => str_replace("'", "\'", $this->component_data->readable_name->value),
      '%sentence'     => CaseString::title($this->component_data->readable_name->value)->sentence(),
      '%lower'        => strtolower($this->component_data->readable_name->value),
      '%description'  => str_replace("'", "\'", $this->component_data->short_description->value),
    ];
  }

}
