<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\Profile.
 */

namespace ModuleBuider\Generator;

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
  public $sanity_level = 'none';

  function __construct($component_name, $component_data = array()) {
    parent::__construct($component_name, $component_data);
  }

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
  protected function componentDataDefinition() {
    $component_data_definition = array(
      'root_name' => array(
        'label' => 'Profile machine name',
        'default' => 'myprofile',
        'required' => TRUE,
      ),
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
    );
    return $component_data_definition;
  }

  /**
   * Get the Drupal name for this component, e.g. the module's name.
   */
  public function getComponentSystemName() {
    return $this->component_data['root_name'];
  }

  /**
   * Declares the subcomponents for this component.
   *
   * These are not necessarily child classes, just components this needs.
   *
   * @return
   *  An array of subcomponent types.
   */
  protected function requiredComponents() {
    $root_name = $this->component_data['root_name'];

    $components = array(
      "$root_name.install" => 'PHPFile',
      "$root_name.profile" => 'PHPFile',
    );

    return $components;
  }


}
