<?php

namespace DrupalCodeBuilder\Task\Generate;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\Generator\RootComponent;

/**
 * Task helper for collecting components recursively.
 *
 * This takes a structured array of data for the root component, and produces
 * an array of components, that is, instantiated Generator objects. These are
 * added to the ComponentCollection object, which keeps track of the
 * relationships between them.
 *
 * Before being passed to instantiated components, the data is processed in
 * various ways:
 *  - data values can be acquired from the component that requested the current
 *    one.
 *  - preset values are expanded.
 *  - default values are filled in.
 *  - 'processing' callbacks are called for each property.
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
 *   info as having a component themselves, with the 'component_type' key. This
 *   will cause the value of this single property to be expanded and, upon
 *   suitable treatment, passed back in as a component data array in its own
 *   right. The nature of the expansion and treatment depends on the format of
 *   the property:
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
 * new component, if the ComponentCollection determines that the new component
 * is in fact a duplicate of one it already has. This checking is done by
 * ComponentCollection::getMatchingComponent(), which compares the match tag,
 * component type, and closest root component. If this happens, there are two
 * possible outcome:
 * - The request data array for the new component and the existing one are
 *   identical. Nothing further happens and the new component is discarded. An
 *   alias is added to the ComponentCollection.
 * - The request data arrays are different. The new request data array is merged
 *   into the existing component. The process continues with the existing
 *   component, allowing it to request data. This is done even though the
 *   component will have previously done this when it was instantiated, because
 *   with the new data, it may request further components.
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

    // Acquire data from the requesting component. We call this even if there
    // isn't a requesting component, as in that case, an exception is thrown
    // if an acquisition is attempted.
    $this->acquireDataFromRequestingComponent($component_data, $component_data_info, $requesting_component);

    // Process the component's data.
    //dump($component_data);
    $this->processComponentData($component_data, $component_data_info);

    // Instantiate the generator in question.
    // We always pass in the root component.
    // We need to ensure that we create the root generator first, before we
    // recurse, as all subsequent generators need it.
    $generator = $this->classHandler->getGenerator($component_type, $component_data);

    $this->debug($chain, "instantiated name $name; type: $component_type; ID");

    $existing_matching_component = $this->component_collection->getMatchingComponent($generator, $requesting_component);
    if ($existing_matching_component) {
      // There is a matching component already in the collection.
      // We determine whether our data needs to be merged in with it.
      $this->debug($chain, "record has matching existing component name $name; type: $component_type");

      $differences_merged = $existing_matching_component->mergeComponentData($component_data);

      if (!$differences_merged) {
        // There was nothing new in our component's data that needed to be
        // merged into the existing one. We give up on this current component,
        // as it's identical to one already in the collection.
        $this->debug($chain, "bailing on name $name; type: $component_type");
        array_pop($chain);

        $this->component_collection->addAliasedComponent($name, $existing_matching_component, $requesting_component);

        return;
      }

      // If it already exists, we merge the received data in with the existing
      // component, and use the existing generator instead. We now carry on with
      // the existing component, getting required components  from it again, as
      // it may need further components based on the data it has just been
      // given.
      $generator = $existing_matching_component;

      $this->component_collection->addAliasedComponent($name, $generator, $requesting_component);
    }
    else {
      // Add the new component to the collection of components.
      $this->component_collection->addComponent($name, $generator, $requesting_component);
    }

    // We're now ready to look at components which the current component
    // spawns.
    // Assemble a list of all the local names to guard against clashes.
    $local_names = [];

    // Pick out any data properties which are components themselves, and create the
    // child components.
    foreach ($component_data_info as $property_name => $property_info) {
      // We're only interested in component properties.
      if (!isset($property_info['component_type'])) {
        continue;
      }
      // Only work with properties for which there is data.
      if (empty($component_data[$property_name])) {
        continue;
      }

      // Get the component type.
      $item_component_type = $property_info['component_type'];

      switch ($property_info['format']) {
        case 'compound':
          // Create a component for each delta item.
          foreach ($component_data[$property_name] as $delta => $item_data) {
            $item_data['component_type'] = $item_component_type;

            // Form an item request name with the delta.
            $item_request_name = "{$item_component_type}_{$delta}";

            $local_names[$item_request_name] = TRUE;

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

          $local_names[$item_request_name] = TRUE;

          $property_component = $this->getComponentsFromData($item_request_name, $item_data, $generator);
          break;
        case 'array':
          // The value in the array is set to the component's primary property.
          // Find the primary property.
          $child_component_data_info = $this->dataInfoGatherer->getComponentDataInfo($item_component_type, TRUE);
          $primary_property = NULL;
          foreach ($child_component_data_info as $child_property_name => $child_property_info) {
            if (!empty($child_property_info['primary'])) {
              $primary_property = $child_property_name;
              break;
            }
          }
          assert(!is_null($primary_property), "No primary property found for array format property.");

          foreach ($component_data[$property_name] as $item_value) {
            $item_data = [
              'component_type' => $item_component_type,
              // Set the value from the array to the primary property.
              $primary_property => $item_value,
            ];
            // Use the value in the array as the local name.
            $item_request_name = $item_value;

            $local_names[$item_request_name] = TRUE;

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
      // Guard against a clash of required item key.
      // In other words, a key in requiredComponents() can't be the same as a
      // property name.
      if (isset($local_names[$required_item_name])) {
        throw new \Exception("$required_item_name already used as a required item key.");
      }

      $local_names[$required_item_name] = TRUE;

      $main_required_component = $this->getComponentsFromData($required_item_name, $required_item_data, $generator);
      $required_components[$required_item_name] = $main_required_component;
    }

    $this->debug($chain, "done");
    array_pop($chain);

    return $generator;
  }

  /**
   * Acquire additional data from the requesting component.
   *
   * Helper for getComponentsFromData().
   *
   * @param &$component_data
   *  The component data array. On the first call, this is the entire array; on
   *  recursive calls this is the local data subset.
   * @param $component_data_info
   *  The component data info for the data being processed.
   * @param $requesting_component
   *  The component data info for the component that is requesting the current
   *  data, or NULL if there is none.
   *
   * @throws \Exception
   *   Throws an exception if a property has the 'acquired' attribute, but
   *   there is no requesting component present.
   */
  protected function acquireDataFromRequestingComponent(&$component_data, $component_data_info, $requesting_component) {
    // Get the requesting component's data info.
    if ($requesting_component) {
      $requesting_component_data_info = $this->dataInfoGatherer->getComponentDataInfo($requesting_component->getType(), TRUE);
    }

    // Initialize a map of property acquisition aliases in the requesting
    // component. This is lazily computed if we find no other way to find the
    // acquired property.
    $requesting_component_alias_map = NULL;

    // Allow the new generator to acquire properties from the requester.
    foreach ($component_data_info as $property_name => $property_info) {
      if (empty($property_info['acquired'])) {
        continue;
      }

      if (!$requesting_component) {
        throw new \Exception("Component $name needs to acquire property '$property_name' but there is no requesting component.");
      }

      $acquired_value = NULL;
      if (isset($property_info['acquired_from'])) {
        // If the current property says it is acquired from something else,
        // use that.
        $acquired_value = $requesting_component->getComponentDataValue($property_info['acquired_from']);
      }
      elseif (array_key_exists($property_name, $requesting_component_data_info)) {
        // Get the value from the property of the same name, if one exists.
        $acquired_value = $requesting_component->getComponentDataValue($property_name);
      }
      else {
        // Finally, try to find an acquisition alias.
        if (is_null($requesting_component_alias_map)) {
          // Lazily build a map of aliases that exist in the requesting
          // component's data info, now that we need it.
          $requesting_component_alias_map = [];

          foreach ($requesting_component_data_info as $requesting_component_property_name => $requesting_component_property_info) {
            if (!isset($requesting_component_property_info['acquired_alias'])) {
              continue;
            }

            // Create a map of the current data's property name => the
            // requesting component's property name.
            $requesting_component_alias_map[$requesting_component_property_info['acquired_alias']] = $requesting_component_property_name;
          }
        }

        if (isset($requesting_component_alias_map[$property_name])) {
          $acquired_value = $requesting_component->getComponentDataValue($requesting_component_alias_map[$property_name]);
        }
      }

      if (!isset($acquired_value)) {
        throw new \Exception("Unable to acquire value for property $property_name.");
      }

      $component_data[$property_name] = $acquired_value;
    }
  }

  /**
   * Recursively process component data prior instantiating components.
   *
   * Performs final processing for the component data:
   *  - sets default values on empty properties. To prevent a default being set
   *    and keep the component a property represents absent, set it to FALSE.
   *  - sets values forced or suggested by other properties' presets.
   *  - performs additional processing that a property may require
   *
   * @param &$component_data
   *  The component data array. On the first call, this is the entire array; on
   *  recursive calls this is the local data subset.
   * @param &$component_data_info
   *  The component data info for the data being processed. Passed by reference,
   *  to allow property processing callbacks to make changes.
   */
  protected function processComponentData(&$component_data, &$component_data_info) {
    // Work over each property.
    foreach ($component_data_info as $property_name => &$property_info) {
      // Set defaults for properties that don't have a value yet.
      $this->setComponentDataPropertyDefault($property_name, $property_info, $component_data);

      // Set values from a preset.
      // We do this after defaults, so preset properties can have a default
      // value. Note this means that presets can only affect properties that
      // come after them in the property info order.
      if (isset($property_info['presets'])) {
        $this->setPresetValues($property_name, $component_data_info, $component_data);
      }

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
      if (isset($property_info['component_type'])) {
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
   * Sets values from a presets property into other properties.
   *
   * @param string $property_name
   *  The name of the preset property.
   * @param $component_data_info
   *  The component info array, or for child properties, the info array for the
   *  current data.
   * @param &$component_data_local
   *  The array of component data, or for child properties, the item array that
   *  immediately contains the property. In other words, this array would have
   *  a key $property_name if data has been supplied for this property.
   */
  protected function setPresetValues($property_name, &$component_data_info, &$component_data_local) {
    if (empty($component_data_local[$property_name])) {
      return;
    }

    // Treat the selected preset as an array, even if it's single-valued.
    if ($component_data_info[$property_name]['format'] == 'array') {
      $preset_keys = $component_data_local[$property_name];
      $multiple_presets = TRUE;
    }
    else {
      $preset_keys = [$component_data_local[$property_name]];
      $multiple_presets = FALSE;
    }

    // First, gather up the values that the selected preset items want to set.
    // We will then apply them to the data.
    $forced_values = [];
    $suggested_values = [];

    foreach ($preset_keys as $preset_key) {
      $selected_preset_info = $component_data_info[$property_name]['presets'][$preset_key];

      // Ensure these are both preset as at least empty arrays.
      // TODO: handle filling this in in ComponentDataInfoGatherer?
      // TODO: testing should cover that it's ok to have these absent.
      $selected_preset_info['data'] += [
        'force' => [],
        'suggest' => [],
      ];

      // Values which are forced by the preset.
      foreach ($selected_preset_info['data']['force'] as $forced_property_name => $forced_data) {
        // Literal value. (This is the only type we support at the moment.)
        if (isset($forced_data['value'])) {
          $this->collectPresetValue(
            $forced_values,
            $multiple_presets,
            $forced_property_name,
            $component_data_info[$forced_property_name],
            $forced_data['value']
          );
        }

        // TODO: processed value, using chain of processing instructions.
      }

      // Values which are only suggested by the preset.
      foreach ($selected_preset_info['data']['suggest'] as $suggested_property_name => $suggested_data) {
        // Literal value. (This is the only type we support at the moment.)
        if (isset($suggested_data['value'])) {
          $this->collectPresetValue(
            $suggested_values,
            $multiple_presets,
            $suggested_property_name,
            $component_data_info[$suggested_property_name],
            $suggested_data['value']
          );
        }

        // TODO: processed value, using chain of processing instructions.
      }
    }

    // Set the collected data values into the actual component data, if
    // applicable.
    foreach ($forced_values as $forced_property_name => $forced_data) {
      $component_data_local[$forced_property_name] = $forced_data;
    }

    foreach ($suggested_values as $suggested_property_name => $suggested_data) {
      // Suggested values from the preset only get set if there is nothing
      // already set there in the incoming data.
      // TODO: boolean FALSE counts as non-empty for a boolean property in
      // other places! ARGH!
      if (!empty($component_data_local[$suggested_property_name])) {
        continue;
      }

      $component_data_local[$suggested_property_name] = $suggested_data;
    }
  }

  /**
   * Collect values for presets.
   *
   * Helper for setPresetValues(). Merges values from multiple presets so that
   * they can be set into the actual component data in one go.
   *
   * @param array &$collected_data
   *   The data collected from presets so far. Further data for this call should
   *   be added to this.
   * @param bool $multiple_presets
   *   Whether the current preset property is multi-valued or not.
   * @param string $target_property_name
   *   The name of the property to set from the preset.
   * @param array $target_property_info
   *   The property info array of the target property.
   * @param mixed $preset_property_data
   *   The value from the preset for the target property.
   */
  protected function collectPresetValue(
    &$collected_data,
    $multiple_presets,
    $target_property_name,
    $target_property_info,
    $preset_property_data
  ) {
    // Check the format of the value is compatible with multiple presets.
    if ($multiple_presets) {
      if (!in_array($target_property_info['format'], ['array', 'compound'])) {
        // TODO: check cardinality is allowable as well!
        // TODO: give the preset name! ARGH another parameter :(
        throw new \Exception("Multiple presets not compatible with single-valued properties, with target property {$target_property_name}.");
      }
    }

    if (isset($collected_data[$target_property_name])) {
      // Merge the data. The check above should have covered that this is
      // allowed.
      $collected_data[$target_property_name] = array_merge($collected_data[$target_property_name], $preset_property_data);
    }
    else {
      $collected_data[$target_property_name] = $preset_property_data;
    }
  }

  /**
   * Set the default value for a property in component data.
   *
   * Helper for processComponentData().
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
    if (isset($property_info['component_type'])) {
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
   * Helper for processComponentData().
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
