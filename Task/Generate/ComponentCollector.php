<?php

namespace DrupalCodeBuilder\Task\Generate;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\Generator\RootComponent;

/**
 * Task helper for collecting components recursively.
 *
 * This takes a structured array of data for the root component, and produces
 * an array of components, that is, instantiated Generator objects.
 *
 * Component data arrays have their structure defined by data property info
 * arrays, which each Generator class defines in its componentDataDefinition()
 * static method.
 *
 * The component data array for the root component, and any subsequent component
 * data arrays, can produce generators in three ways:
 * - The actual generator class for the data array in question; in other words,
 *   a data array for a Module or a Permission will try to get a generator of
 *   that type. ('Try' because of duplicates and merging: see below.)
 * - Properties in the structured component data can be declared in the property
 *   info as having a component themselves, with the 'component' key. This will
 *   cause the value of this single property to be expanded and, upon suitable
 *   treatment, passed back in as a component data array in its own right. The
 *   nature of the expansion and treatment depends on the format of the
 *   property:
 *   - boolean: This simply represents whether the subcomponent exists or not.
 *   - array: Each value in the array produces a component.
 *   - compound: The data is an array keyed by delta, where each value is itself
 *     a component data array.
 * - Once a component is instantiated, its requiredComponents() method is
 *   called, which returns an array where each value is a component data array.
 *   Here, the component type is given in the data itself, in the
 *   'component_type' property.
 *
 * It is possible for the attempted creation of a component to not produce a
 * new component, if a component with the same unique ID has already been
 * created. There are two cases where this happens:
 * - The request data array for the new component and the existing one are
 *   identical. Nothing further happens and the new component is discarded.
 * - The request data arrays are different. The new request data array is merged
 *   into the existing component.
 */
class ComponentCollector {

  /**
   * Whether debug mode is enabled.
   *
   * TODO: Make this use an environment settings so UIs can pass it in.
   *
   * @var boolean
   */
  const DEBUG = FALSE;

  /**
   * The components collected by the process.
   */
  protected $component_list = [];

  /**
   * The root component generator.
   *
   * @var \DrupalCodeBuilder\Generator\RootComponent
   */
  protected $root_component = NULL;

  /**
   * The record of requested data, keyed by generator ID.
   *
   * This allows us to prevent creation of duplicate generators which may be
   * requested by different things.
   */
  protected $requested_data_record = [];

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
   * Get the list of required components for an initial request.
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
   * @param $component_data
   *  The requested component data.
   *
   * @return
   *  The list of components.
   */
  public function assembleComponentList($component_data) {
    // Reset all class properties. We don't normally run this twice, but
    // probably needed for tests.
    $this->root_component = NULL;
    $this->requested_data_record = [];
    $this->component_list = [];

    // Fiddly different handling for the type in the root component...
    $component_type = $component_data['base'];
    $component_data['component_type'] = $component_type;

    // All generators assume that the root component has this property and
    // that it's required.
    // TODO: clean this up? Allow root generators to specify which properties
    // are passed along to requested components?
    // Or remove this when we are able to specify containment in requests
    // without needing to repeat the root name all the time?
    $this->root_component_name = $component_data['root_name'];

    $this->getComponentsFromData($component_type, $component_data);

    return $this->component_list;
  }

  /**
   * Create components from a data array.
   *
   * Provided this data does not duplicate already created components, the
   * populates the $this->component_list property with:
   * - The component itself given by the component_type property.
   *
   * @param $name
   *   The name of the component in a containing array.
   * @param $component_data
   *   The data array. This must contain at least a 'component_type' property
   *   the gives the type of the component.
   */
  protected function getComponentsFromData($name, $component_data) {
    // Debugging: record the chain of how we get here each time.
    static $chain;
    $chain[] = $name;
    $this->debug($chain, "collecting {$component_data['component_type']} $name", '-');

    if (empty($component_data['component_type'])) {
      throw new \Exception("Data for $name missing the 'component_type' property");
    }

    // Add the root component name to the data, except for the root component
    // itself.
    if (isset($this->root_component)) {
      $component_data['root_component_name'] = $this->root_component_name;
    }

    // Process the component's data.
    //dump($component_data);
    $component_type = $component_data['component_type'];
    $component_data_info = $this->dataInfoGatherer->getComponentDataInfo($component_type, TRUE);
    $this->processComponentData($component_data, $component_data_info);

    // Instantiate the generator in question.
    // We always pass in the root component.
    // We need to ensure that we create the root generator first, before we
    // recurse, as all subsequent generators need it.
    $generator = $this->classHandler->getGenerator($component_type, $name, $component_data, $this->root_component);

    // If we've not yet set the root component, then this is the first
    // generator we've created, and thus is the root component. Set it on the
    // helper so we pass it to all subsequent generators.
    // NOTE: For this to work properly, we have to instantiate the generator
    // for this call before we recurse into properties or requirements!
    if (is_null($this->root_component)) {
      $this->root_component = $generator;
    }

    $component_unique_id = $generator->getUniqueID();
    $this->debug($chain, "instantiated name $name; type: $component_type; ID: $component_unique_id");

    // Prevent re-requesting an identical previous request.
    // TODO: use requestedComponentHandling() here?
    if (isset($this->requested_data_record[$component_unique_id]) && $this->requested_data_record[$component_unique_id] == $component_data) {
      $this->debug($chain, "bailing on name $name; type: $component_type; ID: $component_unique_id");
      array_pop($chain);

      return;
    }
    $this->requested_data_record[$component_unique_id] = $component_data;

    //dump($this->requested_data_record);

    // A requested subcomponent may already exist in our tree.
    if (isset($this->component_list[$component_unique_id])) {
      // If it already exists, we merge the received data in with the
      // existing component, and use the existing generator instead.
      $generator = $this->component_list[$component_unique_id];
      //dump("merging $component_unique_id");
      $generator->mergeComponentData($component_data);

      // We get required components from this even though we've already seen
      // it, as it may need further components based on the data its just
      // been given.
    }
    else {
      // Add the new component to the complete array of components.
      $this->component_list[$component_unique_id] = $generator;
    }

    // Pick out any data properties which are components themselves, and create the
    // child components.
    foreach ($component_data_info as $property_name => $property_info) {
      // We're only interested in component properties.
      if (!isset($property_info['component'])) {
        continue;
      }
      // Only work with properties for which there is data.
      if (empty($component_data[$property_name])) {
        continue;
      }

      // Get the component type.
      $item_component_type = $property_info['component'];

      switch ($property_info['format']) {
        case 'compound':
          // Create a component for each delta item.
          foreach ($component_data[$property_name] as $delta => $item_data) {
            $item_data['component_type'] = $item_component_type;

            // Form an item request name with the delta.
            $item_request_name = "{$item_component_type}_{$delta}";

            // Recurse to create the component (and any child components, and
            // any requests).
            $this->getComponentsFromData($item_request_name, $item_data);
          }
          break;
        case 'boolean':
          // We use the type as the request name; would it make more sense to
          // use the property name?
          $item_request_name = $item_component_type;
          $item_data = [
            'component_type' => $item_component_type,
          ];
          $this->getComponentsFromData($item_request_name, $item_data);
          break;
        case 'array':
          foreach ($component_data[$property_name] as $item_value) {
            $item_data = [
              'component_type' => $item_component_type,
            ];
            // Each value in the array is the name of the component.
            $item_request_name = $item_value;

            $this->getComponentsFromData($item_request_name, $item_data);
          }
          break;
      }
    }

    // Ask the generator for its required components.
    $item_required_subcomponent_list = $generator->requiredComponents();

    // Each item in the list is itself a component data array. Recurse for each
    // one to get generators.
    foreach ($item_required_subcomponent_list as $required_item_name => $required_item_data) {
      // The data may either be a string giving a class name, or an array.
      if (is_string($required_item_data)) {
        $component_type = $required_item_data;

        $required_item_data = [
          'component_type' => $required_item_data,
        ];
      }

      $this->getComponentsFromData($required_item_name, $required_item_data);
    }

    $this->debug($chain, "done");
    array_pop($chain);
  }

  /**
   * Process component data prior to passing it to generateComponent().
   *
   * Performs final processing for the component data:
   *  - sets default values on empty properties. To prevent a default being set
   *    and keep the component a property represents absent, set it to FALSE.
   *  - performs additional processing that a property may require
   *
   * @param &$component_data
   *  The component data array.
   * @param $component_data_info
   *  The component data info for the data being processed.
   */
  protected function processComponentData(&$component_data, $component_data_info) {
    // Set defaults for properties that don't have a value yet.
    foreach ($component_data_info as $property_name => $property_info) {
      // No need to set defaults on components here; the defaults will be filled
      // in when the component is instantiated in assembleComponentList() and
      // and this is called with the component's own data.
      // TODO? Still true?
      if (isset($property_info['component'])) {
        continue;
      }

      $this->setComponentDataPropertyDefault($property_name, $property_info, $component_data);
    }

    // Allow each property to apply its processing callback. Note that this may
    // set or alter other properties in the component data array.
    foreach ($component_data_info as $property_name => $property_info) {
      if (isset($property_info['processing']) && !empty($component_data[$property_name])) {
        $processing_callback = $property_info['processing'];

        $processing_callback($component_data[$property_name], $component_data, $property_info);
      }
    } // processing callback

    // Recurse into compound properties.
    // We do this last to allow the parent property to have default and
    // processing applied to the child data as a whole.
    // (TODO: test this!)
    foreach ($component_data_info as $property_name => $property_info) {
      // Only work with compound properties.
      if ($property_info['format'] != 'compound') {
        continue;
      }

      // Don't work with component child properties, as the generator will
      // handle this.
      if (isset($property_info['component'])) {
        continue;
      }

      if (!isset($component_data[$property_name])) {
        // Skip if no data for this property.
        continue;
      }

      foreach ($component_data[$property_name] as $delta => &$item_data) {
        $this->processComponentData($item_data, $property_info['properties']);
      }
    }
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
  protected function setComponentDataPropertyDefault($property_name, $property_info, &$component_data_local) {
    // Determine whether we should fill in a default value.
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
   * Output debug message, with indentation for the current iteration.
   *
   * @param string[] $chain
   *   The current chain of component names.
   * @param string $message
   *   A message to output.
   */
  protected function debug($chain, $message, $indent_char = ' ') {
    if (!self::DEBUG) {
      return;
    }

    dump(str_repeat($indent_char, count($chain)) . ' ' . implode(':', $chain) . ': ' . $message);
  }

}
