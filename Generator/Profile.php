<?php

namespace DrupalCodeBuilder\Generator;

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
   * This can't be a class property due to use of closures.
   *
   * @return
   *  An array that defines the data this component needs to operate. This
   *  includes:
   *  - data that must be specified by the user
   *  - data that may be specified by the user, but can be computed or take from
   *    defaults
   *  - data that should not be specified by the user, as it is computed from
   *    other input.
   */
  public static function componentDataDefinition() {
    $component_data_definition = parent::componentDataDefinition();

    $component_data_definition['base'] = [
      'internal' => TRUE,
      'default' => 'profile',
      'process_default' => TRUE,
    ];

    $component_data_definition['root_name'] = [
      'label' => 'Profile machine name',
      'default' => 'myprofile',
    ] + $component_data_definition['root_name'];

    $component_data_definition += [
      'readable_name' => array(
        'label' => 'Profile readable name',
        'default' => function($component_data) {
          return ucfirst(str_replace('_', ' ', $component_data['root_name']));
        },
        'required' => FALSE,
      ),
      'short_description' => array(
        'label' => 'Profile .info file description',
        'default' => 'TODO: Description of profile',
        'required' => FALSE,
      ),
    ];
    return $component_data_definition;
  }

  /**
   * Declares the subcomponents for this component.
   *
   * These are not necessarily child classes, just components this needs.
   *
   * @return
   *  An array of subcomponent types.
   */
  public function requiredComponents() {
    $root_name = $this->component_data['root_name'];

    $components = array(
      "$root_name.install" => 'PHPFile',
      "$root_name.profile" => 'PHPFile',
    );

    return $components;
  }


}
