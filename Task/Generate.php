<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Generate.
 */

namespace DrupalCodeBuilder\Task;

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
   * The list of components.
   *
   * This is keyed by the unique ID of the component. Values are the
   * instantiated component generators.
   */
  protected $component_list;

  /**
   *  Helper objects.
   *
   * @var array
   */
  private $helpers = [];

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

    $this->base = $component_type;
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
    $class = $this->getGeneratorClass($this->base);
    return $class::getSanityLevel();
  }

  /**
   * Get a list of the properties that the root component should be given.
   *
   * UIs may use this to present the options to the user. Each property should
   * be passed to prepareComponentDataProperty(), to set any option lists and
   * allow defaults to build up incrementally.
   *
   * The array with the complete data collected from the user should then be
   * passed to generateComponent() to generate the code.
   *
   * @param $include_computed
   *  (optional) Boolean indicating whether to include computed properties.
   *  Default value is FALSE, as UIs don't need to work with these.
   *  TODO: Deprecate this parameter, it is not used in any calls we make.
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
   *  - 'cardinality': (optional) For properties with format 'array' or
   *    'compound', specifies the maximum number of values. If omitted,
   *    unlimited values are allowed.
   *  - 'properties': If the format is 'compound', this will be an array of
   *    child properties, in the same format at the overall array.
   *  - 'required': Boolean indicating whether this property must be provided.
   *  - 'default': A default value for the property. Progressive UIs that
   *    process user input incrementally will get default values that are
   *    based on the user input so far.
   * For the full documentation for all properties, see
   * DrupalCodeBuilder\Generator\RootComponent\componentDataDefinition().
   */
  public function getRootComponentDataInfo($include_computed = FALSE) {
    return $this->getHelper('ComponentDataInfoGatherer')->getRootComponentDataInfo($this->base, $include_computed);
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
   * Compound properties can either be called as a single property, in which
   * case only the options will be set, or can be called for each child property
   * with a child item data array for $component_data. This may be repeated for
   * multiple child items.
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
    return $this->getHelper('ComponentPropertyPreparer')->prepareComponentDataProperty($property_name, $property_info, $component_data);
  }

  /**
   * Validates a value for a property.
   *
   * Validation is optional; UIs may perform it if it improves UX to do so as
   * a separate step from calling generateComponent().
   *
   * Note that this does not recurse; UIs should take care of this.
   *
   * @param $property_name
   *  The name of the property.
   * @param $property_info
   *  The definition for this property, from getRootComponentDataInfo().
   * @param $component_data
   *  The array of component data for the component that the property is a part
   *  of.
   *
   * @return array|null
   *   If validation failed, an array whose first element is a message string,
   *   and whose second element is an array of translation placeholders (which
   *   may be empty). If the validation succeeded, NULL is returned.
   */
  public function validateComponentDataValue($property_name, $property_info, $component_data) {
    if (isset($property_info['validation'])) {
      $validate_callback = $property_info['validation'];
      $result = $validate_callback($property_name, $property_info, $component_data);

      // Fill in the placeholder array if the callback returned just a string.
      if (is_string($result)) {
        $result = [$result, []];
      }

      return $result;
    }
  }

  /**
   * Generate the files for a component.
   *
   * @param $component_data
   *  An associative array of data for the component. Values depend on the
   *  component class. For details, see the constructor of the generator, of the
   *  form DrupalCodeBuilder\Generator\COMPONENT, e.g.
   *  DrupalCodeBuilder\Generator\Module::__construct().
   * @param $existing_module_files
   *  (optional) An array of existing files for this module. Keys should be
   *  file paths relative to the module, values absolute paths.
   *
   * @return
   *  A files array whose keys are filepaths (relative to the module folder) and
   *  values are the code destined for each file.
   *
   * @throws \DrupalCodeBuilder\Exception\InvalidInputException
   *   Throws an exception if the given data is invalid.
   */
  public function generateComponent($component_data, $existing_module_files = []) {
    // Add the top-level component to the data.
    $component_type = $this->base;
    $component_data['base'] = $component_type;

    // The component name is just the same as the type for the base generator.
    $component_name = $component_type;

    // Assemble the component list from the request data.
    $component_collection = $this->getHelper('ComponentCollector')->assembleComponentList($component_data);
    // Backward-compatiblity.
    // TODO: replace this.
    $this->component_list = $component_collection->getComponents();

    \DrupalCodeBuilder\Factory::getEnvironment()->log(array_keys($this->component_list), "Complete component list names");

    // Let each component detect whether it already exists in the given module
    // files.
    $this->detectExistence($this->component_list, $existing_module_files);

    // Now assemble them into a tree.
    // Calls containingComponent() on everything and puts it into a 2-D array
    // of parent => [children].
    // TODO: replace use of $tree with accessor on the collection.
    $tree = $component_collection->assembleContainmentTree();

    \DrupalCodeBuilder\Factory::getEnvironment()->log($tree, "Component tree");

    $files_assembled = $this->component_list = $this->getHelper('FileAssembler')->generateFiles(
      $component_data,
      $component_collection
    );

    return $files_assembled;
  }

  /**
   * Lets each component determine whether it is already in existing files.
   *
   * Existence is determined at the component level, rather than the file level,
   * because one component may want to add to several files, and several
   * components may want to add to the same file. For example, a service may
   * exist, but other components might want to add services and therefore add
   * code to the services.yml file.
   *
   * @param $component_list
   *  The component list.
   * @param $existing_module_files
   *  The array of existing file names.
   */
  protected function detectExistence($component_list, $existing_module_files) {
    foreach ($component_list as $name => $component) {
      $component->detectExistence($existing_module_files);
    }
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
   * @throws \DrupalCodeBuilder\Exception\InvalidInputException
   *   Throws an exception if the given component type does not correspond to
   *   a component class.
   *
   * @deprecated
   */
  public function getGenerator($component_type, $component_name, $component_data = array()) {
    return $this->getHelper('ComponentClassHandler')->getGenerator($component_type, $component_data);
  }

  /**
   * Helper function to get the desired Generator class.
   *
   * @param $type
   *  The type of the component. This is the name of the class, without the
   *  version suffix. For classes in camel case, the string given here may be
   *  all in lower case.
   *
   * @return
   *  A fully qualified class name for the type and, if it exists, version, e.g.
   *  'DrupalCodeBuilder\Generator\Info6'.
   *
   * @deprecated
   */
  public function getGeneratorClass($type) {
    return $this->getHelper('ComponentClassHandler')->getGeneratorClass($type);
  }

  /**
   * Returns the helper for the given short class name.
   *
   * @param $class
   *   The short class name.
   *
   * @return
   *   The helper object.
   */
  protected function getHelper($class) {
    if (!isset($this->helpers[$class])) {
      $qualified_class = '\DrupalCodeBuilder\Task\Generate\\' . $class;

      switch ($class) {
        case 'ComponentDataInfoGatherer':
          $helper = new $qualified_class($this->getHelper('ComponentClassHandler'));
          break;
        case 'ComponentCollector':
          $helper = new $qualified_class(
            $this->environment,
            $this->getHelper('ComponentClassHandler'),
            $this->getHelper('ComponentDataInfoGatherer')
          );
          break;
        default:
          $helper = new $qualified_class();
          break;
      }

      $this->helpers[$class] = $helper;
    }

    return $this->helpers[$class];
  }

}
