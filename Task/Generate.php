<?php

/**
 * @file
 * Definition of ModuleBuilder\Task\Generate.
 */

namespace ModuleBuilder\Task;

/**
 * Task handler for generating a component.
 */
class Generate extends Base {

  /**
   * The sanity level this task requires to operate.
   *
   * We have no sanity level; it's obtained from our base component generator.
   */
  protected $sanity_level = NULL;

  /**
   * Our base component type, i.e. either 'module' or 'theme'.
   */
  private $base;

  /**
   * Our root generator.
   */
  private $root_generator;

  /**
   * Override the base constructor.
   *
   * @param $environment
   *  The current environment handler.
   * @param $component_type
   *  A component type. Currently supports 'module' and 'theme'.
   *  (We need this early on so we can use it to determine our sanity level.)
   */
  public function __construct($environment, $component_type) {
    $this->environment = $environment;

    // The component name is just the same as the type for the base generator.
    $component_name = $component_type;

    $this->base = $component_type;
    // We don't have any component data to pass in at this point.
    $this->root_generator = $this->getGenerator($component_type, $component_name);
  }

  /**
   * Get the sanity level this task requires.
   *
   * We override this to hand over to the base generator, as different bases
   * may have different requirements.
   *
   * @return
   *  A sanity level string to pass to the environment's verifyEnvironment().
   */
  public function getSanityLevel() {
    return $this->root_generator->sanity_level;
  }

  /**
   * Get the root generator.
   *
   * This may be used by UIs that want to provide interactive building up of
   * component parameters.
   *
   * @see ModuleBuilder\Generator\BaseGenerator::getComponentDataDefaultValue().
   */
  public function getRootGenerator() {
    return $this->root_generator;
  }

  /**
   * Get a list of the properties that the root component should be given.
   *
   * UIs may use this to present the options to the user. Each property should
   * be passed to prepareComponentDataProperty(), to set any option lists and
   * allow defaults to build up incrementally.
   *
   * After all data has been gathered from the user, the completed data array
   * should be passed to processComponentData().
   *
   * Finally, the array should be passed to generateComponent() to generate the
   * code.
   *
   * @return
   *  An array containing information about the properties our root component
   *  needs in the $component_data array to pass to generateComponent(). Keys
   *  are the names of properties. Each value is an array of information for the
   *  property. Of interest to UIs calling this are:
   *  - 'label': A human-readable label for the property.
   *  - 'format': Specifies the expected format for the property. One of
   *    'string' or 'array'.
   *  - 'required': Boolean indicating whether this property must be provided.
   * For the full documentation for all properties, see
   * ModuleBuilder\Generator\RootComponent\componentDataDefinition().
   */
  public function getRootComponentDataInfo() {
    return $this->root_generator->getComponentDataInfo();
  }

  /**
   * Prepares a property in the component data with default value and options.
   *
   * This should be called for each property in the component data info that is
   * obtained from getRootComponentDataInfo(), in the order given in that array.
   * This allows UIs to present default values to the user in a progressive
   * manner. For example, the Drush interactive mode may present a default value
   * for the module human name based on the value the user has already entered
   * for the machine name.
   *
   * The default value is placed into $component_data[$property_name]; the
   * options if any are placed into $property_info['options'].
   *
   * @param $property_name
   *  The name of the property.
   * @param &$property_info
   *  The definition for this property, from getRootComponentDataInfo().
   *  If the property has options, this will get its 'options' key set, as an
   *  array of the format VALUE => LABEL.
   * @param &$component_data
   *  An array of component data that is being assembled to eventually pass to
   *  generateComponent(). This should contain property data that has been
   *  obtained from the user so far, as a property may depend on input for
   *  earlier properties. This will get its $property_name key set with the
   *  default value for the property, which may be calculated based on the
   *  existing user data.
   */
  public function prepareComponentDataProperty($property_name, &$property_info, &$component_data) {
    $this->root_generator->prepareComponentDataProperty($property_name, $property_info, $component_data);
  }

  /**
   * Process component data prior to passing it to generateComponent().
   *
   * Performs final processing for the component data:
   *  - sets default values on empty properties
   *  - performs additional processing that a property may require
   *  - expand properties that represent child components.
   *
   * @param $component_data_info
   *  The complete component data info.
   * @param &$component_data
   *  The component data array.
   */
  public function processComponentData($component_data_info, &$component_data) {
    $this->root_generator->processComponentData($component_data_info, $component_data);
  }

  /**
   * Generate the files for a component.
   *
   * @param $component_data
   *  An associative array of data for the component. Values depend on the
   *  component class. For details, see the constructor of the generator, of the
   *  form ModuleBuilder\Generator\COMPONENT, e.g.
   *  ModuleBuilder\Generator\Module::__construct().
   *
   * @return
   *  A files array whose keys are filepaths (relative to the module folder) and
   *  values are the code destined for each file.
   */
  public function generateComponent($component_data) {
    // The dummy generator that was made by __construct() should now be removed;
    // it is not fully set up, and its presence would cause a tangled mess in
    // getGenerator().
    $this->root_generator = NULL;

    // Add the top-level component to the data.
    $component_type = $this->base;
    $component_data['base'] = $component_type;

    // The component name is just the same as the type for the base generator.
    $component_name = $component_type;

    // Repeat the steps from __construct() now we have proper component data.
    // The component name is just the same as the type for the base generator.
    $root_generator = $this->getGenerator($component_type, $component_name, $component_data);

    // Set the root generator on ourselves now we actually have it.
    $this->root_generator = $root_generator;

    // Recursively assemble all the components that are needed.
    $this->root_generator->assembleComponentList();

    // Now assemble them into a tree.
    $this->root_generator->assembleComponentTree();

    // Let each component that is a parent in the tree collect data from its
    // child components.
    $this->root_generator->assembleContainedComponents();

    //drush_print_r($generator->components);

    // Build files.
    // First we recurse into the tree to collect data on the files needed. Each
    // component gets to add to the files array.
    $files = array();
    $this->root_generator->collectFiles($files);
    //drush_print_r($files);

    // Then we assemble the files into a simple array of full filename and
    // contents.
    // TODO: rename this to buildFiles().
    $files_assembled = $this->root_generator->assembleFiles($files);

    return $files_assembled;
  }

  /**
   * Generator factory.
   *
   * @param $component_type
   *   The type of the component. This is use to build the class name: see
   *   getGeneratorClass().
   * @param $component_name
   *   The identifier for the component. This is often the same as the type
   *   (e.g., 'module', 'hooks') but in the case of types used multiple times
   *   this will be a unique identifier.
   * @param $component_data
   *   An array of data for the component. This is passed to the generator's
   *   __construct().
   *
   * @return
   *   A generator object, with the component name and data set on it, as well
   *   as a reference to this task handler.
   *
   * @throws \ModuleBuilder\Exception
   *   Throws an exception if the given component type does not correspond to
   *   a component class.
   */
  public function getGenerator($component_type, $component_name, $component_data = array()) {
    $class = $this->getGeneratorClass($component_type);

    if (!class_exists($class)) {
      throw new \ModuleBuilder\Exception("Invalid component type $component_type.");
    }

    $generator = new $class($component_name, $component_data);

    // Each generator needs a link back to the factory to be able to make more
    // generators, and also so it can access the environment.
    $generator->task = $this;
    $generator->base_component = $this->root_generator;

    return $generator;
  }

  /**
   * Helper function to get the desired Generator class.
   *
   * @param $type
   *  The type of the component. This is used to determine the class.
   *
   * @return
   *  A fully qualified class name for the type and, if it exists, version, e.g.
   *  'ModuleBuilder\Generator\Info6'.
   */
  public function getGeneratorClass($type) {
    $type     = ucfirst($type);
    $version  = $this->environment->getCoreMajorVersion();
    $class    = 'ModuleBuilder\\Generator\\' . $type . $version;

    // If there is no version-specific class, use the base class.
    if (!class_exists($class)) {
      $class  = 'ModuleBuilder\\Generator\\' . $type;
    }
    return $class;
  }

}
