<?php

namespace DrupalCodeBuilder\Task\Generate;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Task helper for collecting components recursively.
 */
class ComponentCollector {

  protected $component_list = [];

  /**
   * Constructs a new ComponentCollector.
   *
   * @param EnvironmentInterface $environment
   *   The environment object.
   * @param ComponentClassHandler $class_handler
   *   The class handler helper.
   * @param ComponentDataInfoGatherer $data_info_gatherer
   *   The data info gatherer helper.
   */
  public function __construct(
    EnvironmentInterface $environment,
    ComponentClassHandler $class_handler,
    ComponentDataInfoGatherer $data_info_gatherer
  ) {
    $this->environment = $environment;
    $this->classHandler = $class_handler;
    $this->dataInfoGatherer = $data_info_gatherer;
  }

  /**
   * Get the list of required components for the root generator.
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
   * @param $root_component
   *  The root generator.
   *
   * @return
   *  The list of components.
   */
  public function assembleComponentList($root_component) {
    // Keep track of all requests to prevent duplicates.
    $requested_info_record = array();

    // The complete list we'll assemble. Start with the root component.
    $component_list = array(
      $root_component->getUniqueID() => $root_component,
    );

    // Prep the current level with the root component for the first iteration.
    $current_level = array(
      $root_component->getUniqueID() => $root_component,
    );

    $level_index = 0;

    // Do a breadth-first tree traversal, working over the current level to
    // create the next level, until there are no further items.
    do {
      $next_level = array();

      // Log the current level.
      $this->environment->log(array_keys($current_level), "starting level $level_index");

      // Work over the current level, assembling a temporary array for the next
      // level.
      foreach ($current_level as $current_level_component_name => $item) {
        // Each item of the current level gives us some children.
        $item_required_subcomponent_list = $item->requiredComponents();

        // Instantiate each one (if not already done), and add it to the next
        // level.
        foreach ($item_required_subcomponent_list as $request_name => $data) {
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

          // Add the root component name to the data.
          $component_data['root_component_name'] = $root_component->component_data['root_name'];

          // Instantiate the component so we can get its unique ID.
          // This may turn out to not be needed and get thrown away!
          // Fill in defaults for the component data.
          // (This is the equivalent of handling compound properties when
          // processing the original input data.)

          $this->processComponentData($component_data, $component_type);

          // Instantiate the generator.
          $generator = $this->classHandler->getGenerator($component_type, $request_name, $component_data, $root_component);

          $component_unique_id = $generator->getUniqueID();

          // Prevent re-requesting an identical previous request.
          // TODO: use requestedComponentHandling() here?
          if (isset($requested_info_record[$component_unique_id]) && $requested_info_record[$component_unique_id] == $data) {
            continue;
          }
          $requested_info_record[$component_unique_id] = $data;

          // A requested subcomponent may already exist in our tree.
          if (isset($component_list[$component_unique_id])) {
            // If it already exists, we merge the received data in with the
            // existing component, and use the existing generator instead.
            $generator = $component_list[$component_unique_id];
            $generator->mergeComponentData($component_data);
          }
          else {
            // Add the new component to the complete array of components.
            $component_list[$component_unique_id] = $generator;
          }

          // Add the new component to the next level, whether it's new to us or
          // not: if it's a repeat, we still need to ask it again for requests
          // based on the new data it's just been given.
          $next_level[$component_unique_id] = $generator;
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
   * Process component data prior to passing it to generateComponent().
   *
   * Performs final processing for the component data:
   *  - sets default values on empty properties. To prevent a default being set
   *    and keep the component a property represents absent, set it to FALSE.
   *  - performs additional processing that a property may require
   *  - expand properties that represent child components.
   *
   * @todo Change this to protected once Generate no longer needs to call it.
   *
   * @param &$component_data
   *  The component data array.
   * @param $component_type
   *  The component type for the data being processed.
   */
  public function processComponentData(&$component_data, $component_type) {
    // Set defaults for properties that don't have a value yet.
    // First, get the component data info again, with the computed properties
    // this time, so we can add them in.
    $class = $this->classHandler->getGeneratorClass($component_type);

    $component_data_info = $this->dataInfoGatherer->getComponentDataInfo($class, TRUE);

    // TODO: refactor this with code in prepareComponentDataProperty().
    foreach ($component_data_info as $property_name => $property_info) {
      // No need to set defaults on components here; the defaults will be filled
      // in when the component is instantiated in assembleComponentList() and
      // and this is called with the component's own data.
      if (isset($property_info['component'])) {
        continue;
      }

      $this->setComponentDataPropertyDefault($property_name, $property_info, $component_data, 'process');

      if (isset($property_info['properties']) && isset($component_data[$property_name]) && is_array($component_data[$property_name])) {
        foreach ($component_data[$property_name] as $delta => $delta_data) {
          foreach ($property_info['properties'] as $child_property_name => $child_property_info) {
            $this->setComponentDataPropertyDefault($child_property_name, $child_property_info, $component_data[$property_name][$delta], 'process');
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

        // If the format is 'compound', handling type is irrelevant.
        if ($property_info['format'] == 'compound') {
          foreach ($component_data[$property_name] as $delta => $item_data) {
            $item_data['component_type'] = $component_type;

            $component_data['requested_components']["{$component_type}_{$delta}"] = $item_data;
          }

          continue;
        }

        // Ask the component type class how to handle this.
        $class = $this->classHandler->getGeneratorClass($component_type);
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
        }
      }
    } // expand components
  }

  /**
   * Set the default value for a property in component data.
   *
   * TODO remove $stage and code for 'prepare' stage.
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
   * @param $stage
   *  Indicates the stage at which this has been called:
   *    - 'prepare': Called by prepareComponentData().
   *    - 'process': Called by processComponentData().
   */
  protected function setComponentDataPropertyDefault($property_name, $property_info, &$component_data_local, $stage) {
    // In the 'prepare' stage, we always want to provide a default, for the
    // convenience of the user in the UI.
    // In the 'process' stage, it's not as clear-cut.
    if ($stage == 'process') {
      if (!empty($component_data_local[$property_name])) {
        // User has provided a default: don't clobber that.
        return;
      }
      if (empty($property_info['process_default']) &&
        empty($property_info['computed']) &&
        empty($property_info['internal'])
      ) {
        // Allow an empty value to remain empty if the property is neither:
        //  - computed: this never gets shown to the user, so we must provide a
        //    default always.
        //  - internal: we always want our own defaults to be processed.
        //  - process_default: this forces a default value, effectively
        //    preventing a property from being left empty.
        return;
      }
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
    else {
      if ($stage == 'prepare') {
        // In the prepare stage, always set the property name, even if it's
        // something basically empty.
        // (This allows UIs to rely on this and set it as their default no
        // matter what.)
        $default_value = $property_info['format'] == 'array' ? array() : NULL;
        $component_data_local[$property_name] = $default_value;
      }
    }
  }

}
