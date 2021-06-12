<?php

namespace DrupalCodeBuilder\Task\Generate;

/**
 * Task helper for getting info on data properties from components.
 *
 * This takes data that a component generator class defines in
 * componentDataDefinition() and prepares it for use by UIs.
 *
 * @see BaseGenerator::componentDataDefinition()
 * @see Generate::getRootComponentDataInfo()
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
   * @param $include_internal
   *  (optional) Boolean indicating whether to include internal properties.
   *  These are the properties marked as either 'computed' or 'internal'.
   *  Default value is FALSE, as UIs don't need to work with these.
   *  TODO: Deprecate this parameter, it is not used.
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
  public function getRootComponentDataInfo($root_component_type, $include_internal = FALSE) {
    return $this->getComponentDataInfo($root_component_type, $include_internal);
  }

  /**
   * Get a list of the properties that are required in the component data.
   *
   * This adds in default values, recurses into child components, and filters
   * out computed values so they are not available to UIs.
   *
   * @param $component_type
   *  The component type to get properties for. Compound properties are
   *  recursed into.
   * @param $include_internal
   *  (optional) Boolean indicating whether to include internal properties.
   *  These are the properties marked as either 'computed' or 'internal'.
   *  Defaults to FALSE.
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
  public function getComponentDataInfo($component_type, $include_internal = FALSE) {
    $properties = $this->classHandler->getComponentDataDefinition($component_type);

    return $properties;
  }

}
