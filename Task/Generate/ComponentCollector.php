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
  protected $component_collection;

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
   * @return \DrupalCodeBuilder\Generator\Collection\ComponentCollection
   *  The collection of components.
   */
  public function assembleComponentList($component_data) {
    // Reset all class properties. We don't normally run this twice, but
    // probably needed for tests.
    $this->root_component = NULL;
    $this->requested_data_record = [];

    $this->component_collection = new \DrupalCodeBuilder\Generator\Collection\ComponentCollection;

    // Fiddly different handling for the type in the root component...
    $component_type = $component_data['base'];
    $component_data['component_type'] = $component_type;

    // The name for the root component is its root name, which will be the
    // name of the Drupal extension.
    $this->getComponentsFromData($component_data['root_name'], $component_data, NULL);

    return $this->component_collection;
  }

  /**
   * Create components from a data array.
   *
   * Provided this data does not duplicate already created components, the
   * populates the $this->component_collection property with:
   * - The component itself given by the component_type property.
   * - Components that are specified by properties which themselves have
   *   component_type set.
   * - Components which the component itself requests in its
   *   requiredComponents() method.
   *
   * @param $name
   *   The name of the component in a containing array.
   * @param $component_data
   *   The data array. This must contain at least a 'component_type' property
   *   the gives the type of the component.
   * @param $requesting_component
   *   The generator that is in scope when the components are requested, or
   *   NULL if this is the first iteration and we are building the root
   *   component.
   *
   * @return
   *   The new generator that the top level of the data array is requesting, or
   *   NULL if the data is a duplicate set. Note that nothing needs to be done
   *   with the return; the generator gets added to $this->component_collection.
   */
  protected function getComponentsFromData($name, $component_data, $requesting_component) {
    // Debugging: record the chain of how we get here each time.
    static $chain;
    $chain[] = $name;
    $this->debug($chain, "collecting {$component_data['component_type']} $name", '-');

    if (empty($component_data['component_type'])) {
      throw new \Exception("Data for $name missing the 'component_type' property");
    }

    // Keep the original component data before we add things to it.
    // This will be used to track duplicate requests.
    $original_component_data = $component_data;

    $component_type = $component_data['component_type'];
    $component_data_info = $this->dataInfoGatherer->getComponentDataInfo($component_type, TRUE);

    // Allow the new generator to acquire properties from the requester.
    $property_name_conversion_map = NULL;
    foreach ($component_data_info as $property_name => $property_info) {
      if (!empty($property_info['acquired'])) {
        if (!$requesting_component) {
          throw new \Exception("Component $name needs to acquire property '$property_name' but there is no requesting component.");
        }

        // Get the mapping from the requesting component, but only once.
        if (is_null($property_name_conversion_map)) {
          $property_name_conversion_map = array_flip($requesting_component->providedPropertiesMapping());
        }

        if (isset($property_name_conversion_map[$property_name])) {
          $provider_property_name = $property_name_conversion_map[$property_name];
          $component_data[$property_name] = $requesting_component->getComponentDataValue($provider_property_name);
        }
        else {
          $component_data[$property_name] = $requesting_component->getComponentDataValue($property_name);
        }
      }
    }

    // Process the component's data.
    //dump($component_data);
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
    // We use the original component data this method received, without any
    // of the additions we've made.
    if (isset($this->requested_data_record[$component_unique_id])) {
      $this->debug($chain, "record has existing ID for name $name; type: $component_type; ID: $component_unique_id");

      if ($this->requested_data_record[$component_unique_id] == $original_component_data) {
        $this->debug($chain, "bailing on name $name; type: $component_type; ID: $component_unique_id");
        array_pop($chain);

        return;
      }
    }
    $this->requested_data_record[$component_unique_id] = $original_component_data;

    //dump($this->requested_data_record);

    // A requested subcomponent may already exist in our tree.
    if ($this->component_collection->hasComponent($component_unique_id)) {
      // If it already exists, we merge the received data in with the
      // existing component, and use the existing generator instead.
      $generator = $this->component_collection->getComponent($component_unique_id);
      //dump("merging $component_unique_id");
      $generator->mergeComponentData($component_data);

      // We get required components from this even though we've already seen
      // it, as it may need further components based on the data its just
      // been given.
    }
    else {
      // Add the new component to the collection of components.
      $this->component_collection->addComponent($generator, $requesting_component);
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
            $property_component = $this->getComponentsFromData($item_request_name, $item_data, $generator);
          }
          break;
        case 'boolean':
          // We use the type as the request name; would it make more sense to
          // use the property name?
          $item_request_name = $item_component_type;
          $item_data = [
            'component_type' => $item_component_type,
          ];
          $property_component = $this->getComponentsFromData($item_request_name, $item_data, $generator);
          break;
        case 'array':
          foreach ($component_data[$property_name] as $item_value) {
            $item_data = [
              'component_type' => $item_component_type,
            ];
            // Each value in the array is the name of the component.
            $item_request_name = $item_value;

            $property_component = $this->getComponentsFromData($item_request_name, $item_data, $generator);
          }
          break;
      }
    }

    // Ask the generator for its required components.
    $item_required_subcomponent_list = $generator->requiredComponents();

    // Collect the resulting components so we can set IDs for containment.
    $required_components = [];

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

      // Convert tokens in the containing_component property.
      if (isset($required_item_data['containing_component'])) {
        if ($required_item_data['containing_component'] == '%requester') {
          $required_item_data['containing_component'] = $generator->getUniqueID();
        }
        elseif (substr($required_item_data['containing_component'], 0, strlen('%sibling:')) == '%sibling:') {
          $sibling_name = substr($required_item_data['containing_component'], strlen('%sibling:'));

          assert(isset($required_components[$sibling_name]));

          $required_item_data['containing_component'] = $required_components[$sibling_name]->getUniqueID();
        }
      }

      $main_required_component = $this->getComponentsFromData($required_item_name, $required_item_data, $generator);
      $required_components[$required_item_name] = $main_required_component;
    }

    $this->debug($chain, "done");
    array_pop($chain);

    return $generator;
  }

  /**
   * Process component data prior to passing it to generateComponent().
   *
   * Performs final processing for the component data:
   *  - sets default values on empty properties. To prevent a default being set
   *    and keep the component a property represents absent, set it to FALSE.
   *  - sets values forced by other properties' presets.
   *  - performs additional processing that a property may require
   *
   * @param &$component_data
   *  The component data array.
   * @param &$component_data_info
   *  The component data info for the data being processed. Passed by reference,
   *  to allow property processing callbacks to make changes.
   */
  protected function processComponentData(&$component_data, &$component_data_info) {
    // Set values forced by a preset.
    foreach ($component_data_info as $property_name => $property_info) {
      if (!isset($property_info['presets'])) {
        continue;
      }

      if (empty($component_data[$property_name])) {
        continue;
      }

      $preset_key = $component_data[$property_name];
      $selected_preset_info = $property_info['presets'][$preset_key];

      // Set values which are forced by the preset.
      foreach ($selected_preset_info['data']['force'] as $forced_property_name => $forced_data) {
        // Plain value.
        if (isset($forced_data['value'])) {
          $component_data[$forced_property_name] = $forced_data['value'];
        }

        // TODO: processed value, using chain of processing instructions.
      }

      // Set values which are suggested by the preset, if the value is empty.
      if (isset($selected_preset_info['data']['suggest'])) {
        foreach ($selected_preset_info['data']['suggest'] as $suggested_property_name => $suggested_data) {
          // Only set this if no incoming value is set.
          if (!empty($component_data[$suggested_property_name])) {
            continue;
          }

          // Plain value.
          if (isset($suggested_data['value'])) {
            $component_data[$suggested_property_name] = $suggested_data['value'];
          }

          // TODO: processed value, using chain of processing instructions.
        }
      }

    }

    // Set defaults and apply processing callbacks.
    foreach ($component_data_info as $property_name => &$property_info) {
      // Set defaults for properties that don't have a value yet.
      $this->setComponentDataPropertyDefault($property_name, $property_info, $component_data);

      // Allow each property to apply its processing callback. Note that this
      // may set or alter other properties in the component data array, and may
      // also make changes to the property info.
      $this->applyComponentDataPropertyProcessing($property_name, $property_info, $component_data);
    }
    // Clear the loop reference, otherwise PHP does Bad Things.
    unset($property_info);

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
    if (isset($property_info['component'])) {
      // No need to set defaults on components here; the defaults will be filled
      // in when the component is instantiated in assembleComponentList() and
      // and this is called with the component's own data.
      // TODO? Still true?
      return;
    }

    // If the user has provided a default, don't clobber that.
    if (!empty($component_data_local[$property_name])) {
      return;
    }
    // For boolean properties, the value might be FALSE, and this counts as
    // being specified.
    if ($property_info['format'] == 'boolean' && isset($component_data_local[$property_name])) {
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
      //  For array properties, set an empty array for the benefit of iterators.
      //  (This is mostly for tests, as UIs will bring in the empty array that
      //  is set in by ComponentPropertyPreparer.)
      if ($property_info['format'] == 'array' || $property_info['format'] == 'compound') {
        $component_data_local[$property_name] = [];
      }
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
   * Applies the processing callback for a property in component data.
   *
   * @param $property_name
   *  The name of the property. For child properties, this is the name of just
   *  the child property.
   * @param &$property_info
   *  The property info array for the property. Passed by reference, as
   *  the processing callback takes this by reference and may make changes.
   * @param &$component_data_local
   *  The array of component data, or for child properties, the item array that
   *  immediately contains the property. In other words, this array would have
   *  a key $property_name if data has been supplied for this property.
   */
  protected function applyComponentDataPropertyProcessing($property_name, &$property_info, &$component_data_local) {
    if (!isset($property_info['processing'])) {
      // No processing: nothing to do.
      return;
    }

    if (empty($component_data_local[$property_name]) && empty($property_info['process_empty'])) {
      // Don't apply to an empty property, unless forced to.
      return;
    }

    $processing_callback = $property_info['processing'];

    $processing_callback($component_data_local[$property_name], $component_data_local, $property_name, $property_info);
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
