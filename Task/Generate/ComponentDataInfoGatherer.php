<?php

namespace DrupalCodeBuilder\Task\Generate;

/**
 *  Task helper for getting info on data properties from components.
 */
class ComponentDataInfoGatherer {

  /**
   * The class handler helper.
   */
  protected $classHandler;

  /**
   * Creates a new ComponentDataInfoGatherer.
   *
   * @param ComponentClassHandler $class_handler
   *  The class handler helper.
   */
  public function __construct(ComponentClassHandler $class_handler) {
    $this->classHandler = $class_handler;
  }

  /**
   * Get a list of the properties that the root component should be given.
   *
   * @param $root_component_type
   *   The type of the root component, e.g. 'module'.
   * @param $include_computed
   *  (optional) Boolean indicating whether to include computed properties.
   *  Default value is FALSE, as UIs don't need to work with these.
   *
   * @return
   *  An array containing information about the properties our root component
   *  needs in the $component_data array to pass to generateComponent(). Keys
   *  are the names of properties. Each value is an array of information for the
   *  property. Of interest to UIs calling this are:
   *  - 'label': A human-readable label for the property.
   *  - 'description': (optional) A longer description.
   *  - 'format': Specifies the expected format for the property. One of
   *    'string', 'array', 'boolean', or 'compound'.
   *  - 'properties': If the format is 'compound', this will be an array of
   *    child properties, in the same format at the overall array.
   *  - 'required': Boolean indicating whether this property must be provided.
   *  - 'default': A default value for the property. Progressive UIs that
   *    process user input incrementally will get default values that are
   *    based on the user input so far.
   * For the full documentation for all properties, see
   * DrupalCodeBuilder\Generator\RootComponent\componentDataDefinition().
   */
  public function getRootComponentDataInfo($root_component_type, $include_computed = FALSE) {
    $class = $this->classHandler->getGeneratorClass($root_component_type);

    return $this->getComponentDataInfo($class, $include_computed);
  }

  /**
   * Get a list of the properties that are required in the component data.
   *
   * This adds in default values, recurses into child components, and filters
   * out computed values so they are not available to UIs.
   *
   * @param $class
   *  The class to get properties for. Compound properties are recursed into.
   * @param $include_computed
   *  (optional) Boolean indicating whether to include computed properties.
   *  Default value is FALSE, as UIs don't need to work with these.
   *
   * @return
   *  An array containing information about the properties this component needs
   *  in its $component_data array. Keys are the names of properties. Each value
   *  is an array of information for the property.
   *
   * @see BaseGenerator::componentDataDefinition()
   * @see BaseGenerator::prepareComponentDataProperty()
   * @see BaseGenerator::processComponentData()
   */
  public function getComponentDataInfo($class, $include_computed = FALSE) {
    $return = array();
    foreach ($class::componentDataDefinition() as $property_name => $property_info) {
      // Skip computed and internal if not requested.
      if (!$include_computed) {
        if (!empty($property_info['computed']) || !empty($property_info['internal'])) {
          continue;
        }
      }

      // Add defaults for a non-computed property.
      if (empty($property_info['computed'])) {
        $this->componentDataInfoAddDefaults($property_info);
      }

      // Expand compound properties.
      if (isset($property_info['format']) && $property_info['format'] == 'compound') {
        $component_class = $this->classHandler->getGeneratorClass($property_info['component']);

        // Recurse to get the child properties.
        $child_properties = $this->getComponentDataInfo($component_class, $include_computed);

        $property_info['properties'] = $child_properties;
      }

      $return[$property_name] = $property_info;
    }

    return $return;
  }

  /**
   * Fill in defaults in a component property info array.
   *
   * @param &$property_info
   *  A single value array from a component property info array. In other words,
   *  the array that describes a single property that would be passed to a
   *  generator, such as the 'hooks' property.
   */
  protected function componentDataInfoAddDefaults(&$property_info) {
    $property_info += array(
      'required' => FALSE,
      'format' => 'string',
    );
  }

}
