<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\RootComponent.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Abstract Generator for root components.
 *
 * Root components are those with which the generating process may begin, such
 * as Module and Theme.
 */
abstract class RootComponent extends BaseGenerator {

  /**
   * The sanity level this generator requires to operate.
   */
  public static $sanity_level = 'none';

  /**
   * Prepares a property in the component data with default value and options.
   *
   * This should be called for each property in the component data info that is
   * obtained from getComponentDataInfo(), in the order given in that array.
   * This allows UIs to present default values to the user in a progressive
   * manner. For example, the Drush interactive mode may present a default value
   * for the module human name based on the value the user has already entered
   * for the machine name.
   *
   * The default value is placed into the $component_data array; the options are
   * placed into $property_info['options'].
   *
   * @param $property_name
   *  The name of the property. A string if this is a top-level property, or an
   *  array if this is a child property, of the form [parent, child].
   *  TODO: make this less hacky!
   * @param &$property_info
   *  The definition for this property, from getComponentDataInfo().
   *  If the property has options, this will have its 'options' key set, in the
   *  the format VALUE => LABEL.
   * @param &$component_data
   *  An array of component data that is being assembled. This should contain
   *  property data that has been obtained from the user so far. This will have
   *  its $property_name key set with the default value for the property,
   *  which may be calculated based on the existing user data.
   *
   * @see getComponentDataInfo()
   */
  public static function prepareComponentDataProperty($property_name, &$property_info, &$component_data) {
    // Recurse compound properties.
    if ($property_info['format'] == 'compound') {
      foreach ($property_info['properties'] as $child_property_name => $child_property_info) {
        $child_property_address = array($property_name, $child_property_name);
        static::prepareComponentDataProperty($child_property_address, $property_info['properties'][$child_property_name], $component_data);
      }

      return;
    }

    // Set options.
    // This is always a callable if set.
    if (isset($property_info['options'])) {
      $options_callback = $property_info['options'];
      $options = $options_callback($property_info);

      $property_info['options'] = $options;
    }

    // Set a default value, if one is available.
    if (isset($property_info['default'])) {
      // The default property is either an anonymous function, or
      // a plain value.
      if (is_callable($property_info['default'])) {
        $default_callback = $property_info['default'];
        $default_value = $default_callback($component_data);
      }
      else {
        $default_value = $property_info['default'];
      }
    }
    else {
      // Always set the property name, even if it's something basically empty.
      $default_value = $property_info['format'] == 'array' ? array() : NULL;
    }

    if (is_array($property_name)) {
      $component_data[$property_name[0]][$property_name[1]] = $default_value;
    }
    else {
      $component_data[$property_name] = $default_value;
    }
  }

  /**
   * Process component data prior to passing it to the generator.
   *
   * Performs final processing for the component data:
   *  - sets default values on empty properties. To prevent a default being set
   *    and keep the component a property represents absent, set it to FALSE.
   *  - performs additional processing that a property may require
   *  - expand properties that represent child components.
   *
   * @param $component_data_info
   *  The complete component data info.
   * @param &$component_data
   *  The component data array.
   */
  public static function processComponentData($component_data_info, &$component_data) {
    // Set defaults for properties that don't have a value yet.
    // First, get the component data info again, with the computed properties
    // this time, so we can add them in.
    $component_data_info_original = static::getComponentDataInfo(TRUE);
    foreach ($component_data_info_original as $property_name => $property_info) {
      if (!empty($property_info['computed'])) {
        $component_data_info[$property_name] = $property_info;
      }
    }

    // TODO: refactor this with code in prepareComponentDataProperty().
    foreach ($component_data_info as $property_name => $property_info) {
      static::setComponentDataPropertyDefault($property_name, $property_info, $component_data);

      if (isset($property_info['properties']) && is_array($component_data[$property_name])) {
        foreach ($component_data[$property_name] as $delta => &$delta_data) {
          foreach ($property_info['properties'] as $child_property_name => $child_property_info) {
            static::setComponentDataPropertyDefault($child_property_name, $child_property_info, $delta_data);
          }
        }
      }
    }

    // Allow each property to apply its processing callback. Note that this may
    // set or alter other properties in the component data array.
    foreach ($component_data_info as $property_name => $property_info) {
      if (isset($property_info['processing']) && !empty($component_data[$property_name])) {
        $processing_callback = $property_info['processing'];

        $processing_callback($component_data[$property_name], $component_data, $property_info);
      }
    } // processing callback

    // Expand any properties that represent child components to add.
    // TODO: This is a fairly rough piece of functionality that needs more
    // thought.
    foreach ($component_data_info as $property_name => $property_info) {
      if (isset($property_info['component']) && !empty($component_data[$property_name])) {
        // Get the component type.
        $component_type = $property_info['component'];

        // Ask the component type class how to handle this.
        $class = \DrupalCodeBuilder\Task\Generate::getGeneratorClass($component_type);
        $handling_type = $class::requestedComponentHandling();

        switch ($handling_type) {
          case 'singleton':
            // The component type can only occur once and therefore the name is
            // the same as the type.
            $component_data['requested_components'][$component_type] = $component_type;
            break;
          case 'repeat':
            // Each value in the array is the name of a component.
            foreach ($component_data[$property_name] as $requested_component_name) {
              $component_data['requested_components'][$requested_component_name] = $component_type;
            }
            break;
          case 'group':
            // Request a single component with the list of data.
            $component_data['requested_components'][$component_type] = array(
              'request_data' => $component_data[$property_name],
              'component_type' => $component_type,
            );
            break;
        }
      }
    } // expand components
  }

  /**
   * Set the default value for a property in component data.
   *
   * @param $property_name
   *  The name of the property. For child properties, this is the name of just
   *  the child property.
   * @param $property_info
   *  The property info array for the property.
   * @param &$component_data_local
   *  The array of component data, or for child properties, the item array that
   *  immediately contains the property. In other words, this array would have
   *  a key $property_name if data has been supplied for this property.
   */
  private static function setComponentDataPropertyDefault($property_name, $property_info, &$component_data_local) {
    // Skip a property that has a set value.
    if (!empty($component_data_local[$property_name])) {
      return;
    }

    // Remove a property whose value is FALSE. This allows a property that has
    // a default value to be removed completely.
    if (isset($component_data_local[$property_name]) && $component_data_local[$property_name] === FALSE) {
      unset($component_data_local[$property_name]);
      return;
    }

    if (isset($property_info['default'])) {
      if (is_callable($property_info['default'])) {
        $default_callback = $property_info['default'];
        $default_value = $default_callback($component_data_local);
      }
      else {
        $default_value = $property_info['default'];
      }
      $component_data_local[$property_name] = $default_value;
    }
  }

  /**
   * Filter the file info array to just the requested build list.
   *
   * @param &$files
   *  The array of built file info.
   * @param $build_list
   *  The build list parameter from the original Generate component data.
   * @param $component_data
   *  The original component data.
   */
  public function applyBuildListFilter(&$files, $build_list, $component_data) {
  }

  /**
   * Provides replacement strings for tokens in code body.
   *
   * @return
   *  An array of tokens to replacements, suitable for use by strtr().
   */
  function getReplacements() {
    // Root components should override this.
    return array();
  }

}
