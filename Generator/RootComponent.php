<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\RootComponent.
 */

namespace ModuleBuilder\Generator;

/**
 * Abstract Generator for root components.
 *
 * Root components are those with which the generating process may begin, such
 * as Module and Theme.
 */
abstract class RootComponent extends BaseGenerator {

  /**
   * Define the component data this component needs to function.
   *
   * This returns an array of data that defines the component data that
   * this component should be given to perform its work. This includes:
   *  - data that must be specified by the user
   *  - data that may be specified by the user, but can be computed or take from
   *    defaults
   *  - data that should not be specified by the user, as it is computed from
   *    other input.
   *
   * This array must be processed in the order in which the properties are
   * given, so that the callables for defaults and options work properly.
   *
   * Note this can't be a class property due to use of closures.
   *
   * @return
   *  An array that defines the data this component needs to operate. The order
   *  is important, as callbacks may depend on component data that has been
   *  assembled so far, i.e., on data properties that are earlier in the array.
   *  Each key corresponds to a key for a property in the $component_data that
   *  should be passed to this class's __construct(). Each value is an array,
   *  with the following keys:
   *  - 'label': A human-readable label for the property.
   *  - 'description': (optional) A longer description of the property.
   *  - 'format': (optional) Specifies the expected format for the property.
   *    One of 'string', 'array', or 'boolean'. Defaults to 'string'.
   *  - 'default': (optional) The default value for the property. This is either
   *    a static value, or a callable, in which case it must be called with the
   *    array of component data assembled so far. Depending on the value of
   *    'required', this represents either the value that may be presented as a
   *    default to the user in a UI for convenience, or the value that will be
   *    set if nothing is provided when instatiating the component. Note that
   *    this is required if a later property makes use of this property in a
   *    callback, as non-progressive UIs can only rely on hardcoded default
   *    values.
   *  - 'required': (optional) Boolean indicating whether this property must be
   *    provided. Defaults to FALSE.
   *  - 'options': (optional) A callable which returns a list of options for the
   *    property. This receives the component data assembled so far.
   *  - 'options_structured': (optional) A callable which returns data about the
   *    possible options for the property. Use this as an alternative to the
   *    'options' property if you want more information. This returns an array
   *    keyed by group name (where the group is possibly the module that
   *    defines the option), whose values are arrays keyed by the options. Each
   *    value is a further array with these properties:
   *      - 'description': A longer description of the item.
   *  - 'processing': (optional) A callback to processComponentData() to use to
   *    process input values into the final format for the component data array.
   *  - 'component': (optional) The name of a generator class, relative to the
   *    namespace. If present, this results in child components of this class
   *    being added to the component tree. The handling of this is determined
   *    by the component class's requestedComponentHandling() method.
   *  - 'computed': (optional) If TRUE, indicates that this property is computed
   *    by the component, and should not be obtained from the user.
   *
   * @see getComponentDataInfo()
   */
  abstract protected function componentDataDefinition();

  /**
   * Get a list of the properties that are required in the component data.
   *
   * UIs should use ModuleBuilder\Task\Generate\getRootComponentDataInfo() rather
   * than this method.
   *
   * @param $include_computed
   *  (optional) Boolean indicating whether to include computed properties.
   *  Default value is FALSE, as UIs don't need to work with these.
   *
   * @return
   *  An array containing information about the properties this component needs
   *  in its $component_data array. Keys are the names of properties. Each value
   *  is an array of information for the property.
   *
   * @see componentDataDefinition()
   * @see prepareComponentDataProperty()
   * @see processComponentData()
   */
  public function getComponentDataInfo($include_computed = FALSE) {
    $return = array();
    foreach ($this->componentDataDefinition() as $property_name => $property_info) {
      if (empty($property_info['computed'])) {
        $property_info += array(
          'required' => FALSE,
          'format' => 'string',
        );
      }
      else {
        if (!$include_computed) {
          continue;
        }
      }

      $return[$property_name] = $property_info;
    }

    return $return;
  }

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
   *  The name of the property.
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
  public function prepareComponentDataProperty($property_name, &$property_info, &$component_data) {
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
      $component_data[$property_name] = $default_value;
    }
    else {
      // Always set the property name, even if it's something basically empty.
      $component_data[$property_name] = $property_info['format'] == 'array' ? array() : NULL;
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
  public function processComponentData($component_data_info, &$component_data) {
    // Set defaults for properties that don't have a value yet.
    // First, get the component data info again, with the computed properties
    // this time, so we can add them in.
    $component_data_info_original = $this->getComponentDataInfo(TRUE);
    foreach ($component_data_info_original as $property_name => $property_info) {
      if (!empty($property_info['computed'])) {
        $component_data_info[$property_name] = $property_info;
      }
    }

    // TODO: refactor this with code in prepareComponentDataProperty().
    foreach ($component_data_info as $property_name => $property_info) {
      // Skip a property that has a set value.
      if (!empty($component_data[$property_name])) {
        continue;
      }

      // Remove a property whose value is FALSE. This allows a property that has
      // a default value to be removed completely.
      if (isset($component_data[$property_name]) && $component_data[$property_name] === FALSE) {
        unset($component_data[$property_name]);
        continue;
      }

      if (isset($property_info['default'])) {
        if (is_callable($property_info['default'])) {
          $default_callback = $property_info['default'];
          $default_value = $default_callback($component_data);
        }
        else {
          $default_value = $property_info['default'];
        }
        $component_data[$property_name] = $default_value;
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
        $class = $this->task->getGeneratorClass($component_type);
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
   * Get the list of required components for this generator.
   *
   * This iterates down the tree of component requests: starting with the root
   * component, each component may request further components, and then those
   * components may request more, and so on.
   *
   * Generator classes should implement requiredComponents() to return the list
   * of component types they require, possibly depending on incoming data.
   *
   * Obviously, it's important that eventually this process terminate with
   * generators that return an empty array for requiredComponents().
   *
   * @return
   *  The list of components.
   */
  public function assembleComponentList() {
    // Get the base component to add the generators to it.
    $base_component = $this->task->getRootGenerator();

    // Keep track of all requests to prevent duplicates.
    $requested_info_record = array();

    // The complete list we'll assemble. Start with the root component.
    $component_list = array(
      $this->name => $this,
    );

    // Prep the current level with the root component for the first iteration.
    $current_level = array(
      $this->name => $this
    );

    $level_index = 0;

    // Do a breadth-first tree traversal, working over the current level to
    // create the next level, until there are no further items.
    do {
      $next_level = array();

      // Log the current level.
      \ModuleBuilder\Factory::getEnvironment()->log(array_keys($current_level), "starting level $level_index");

      // Work over the current level, assembling a temporary array for the next
      // level.
      foreach ($current_level as $current_level_component_name => $item) {
        // Each item of the current level gives us some children.
        $item_subcomponent_info = $item->requiredComponents();

        // Instantiate each one (if not already done), and add it to the next
        // level.
        foreach ($item_subcomponent_info as $component_name => $data) {
          // Prevent re-requesting an identical previous request.
          // TODO: use requestedComponentHandling() here?
          if (isset($requested_info_record[$component_name]) && $requested_info_record[$component_name] == $data) {
            continue;
          }
          $requested_info_record[$component_name] = $data;

          // The $data may either be a string giving a class name, or an array.
          if (is_string($data)) {
            $component_type = $data;
            $component_data = array();

            // Set the type in the array for consistency in debugging.
            $component_data['component_type'] = $component_type;
          }
          else {
            $component_type = $data['component_type'];
            $component_data = $data;
          }

          // A requested subcomponent may already exist in our tree.
          if (isset($component_list[$component_name])) {
            // If it already exists, we merge the received data in with the
            // existing component.
            $generator = $component_list[$component_name];
            $generator->mergeComponentData($component_data);
          }
          else {
            // Instantiate the generator.
            $generator = $this->task->getGenerator($component_type, $component_name, $component_data);

            // Add the new component to the complete array of components.
            $component_list[$component_name] = $generator;
          }

          // Add the new component to the next level, whether it's new to us or
          // not: if it's a repeat, we still need to ask it again for requests
          // based on the new data it's just been given.
          $next_level[$component_name] = $generator;
        } // each requested subcomponent from a component in the current level.
      } // each component in the current level

      // Now have all the next level.

      // Set the next level to be current for the next loop.
      $current_level = $next_level;
      $level_index++;

    } while (!empty($next_level));

    return $component_list;
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
