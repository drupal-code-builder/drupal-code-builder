<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;
use MutableTypedData\Definition\DefaultDefinition;

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
  public static function setProperties(PropertyDefinition $definition): void {
    parent::setProperties($definition);

    $definition
      ->setLabel('Profile')
      ->setName('profile');

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
   * Declares the subcomponents for this component.
   *
   * These are not necessarily child classes, just components this needs.
   *
   * @return
   *  An array of subcomponent types.
   */
  public function requiredComponents(): array {
    $root_name = $this->component_data['root_name'];

    $components = [
      "$root_name.install" => 'PHPFile',
      "$root_name.profile" => 'PHPFile',
    ];

    return $components;
  }


}
