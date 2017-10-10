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
   * Our root generator.
   */
  private $root_generator;

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
   * After all data has been gathered from the user, the completed data array
   * should be passed to processComponentData().
   *
   * Finally, the array should be passed to generateComponent() to generate the
   * code.
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
    // Set options.
    $this->prepareComponentDataPropertyCreateOptions($property_name, $property_info);
    if (isset($property_info['properties'])) {
      foreach ($property_info['properties'] as $child_property_name => &$child_property_info) {
        $this->prepareComponentDataPropertyCreateOptions($child_property_name, $child_property_info);
      }
    }
    // Argh, deal with PHP's wacky behaviour with refereced loop variables.
    unset($child_property_info);

    // Set a default value.
    $this->setComponentDataPropertyDefault($property_name, $property_info, $component_data);
    // Handle compound property child items if they are present.
    if (isset($property_info['properties']) && isset($component_data[$property_name]) && is_array($component_data[$property_name])) {
      foreach ($component_data[$property_name] as $delta => $delta_data) {
        foreach ($property_info['properties'] as $child_property_name => $child_property_info) {
          $this->setComponentDataPropertyDefault($child_property_name, $child_property_info, $component_data[$property_name][$delta]);
        }
      }
    }
  }

  /**
   * Helper to create the options array for a property.
   *
   * This calls the property info's options callback and replaces it with the
   * resulting array of options.
   *
   * @param $property_name
   *  The name of the property.
   * @param &$property_info
   *  The property into array, passed by reference.
   */
  protected function prepareComponentDataPropertyCreateOptions($property_name, &$property_info) {
    if (isset($property_info['options'])) {
      $options_callback = $property_info['options'];
      // We may have prepared this options array from a callback previously,
      // when preparing multiple child items of a compound property.
      if (!is_callable($options_callback)) {
        return;
      }
      $options = $options_callback($property_info);

      $property_info['options'] = $options;
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
    // During the prepare stage, we always want to provide a default, for the
    // convenience of the user in the UI.
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
      // In the prepare stage, always set the property name, even if it's
      // something basically empty.
      // (This allows UIs to rely on this and set it as their default no
      // matter what.)
      $default_value = $property_info['format'] == 'array' ? array() : NULL;
      $component_data_local[$property_name] = $default_value;
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
   */
  public function generateComponent($component_data, $existing_module_files = []) {
    // Add the top-level component to the data.
    $component_type = $this->base;
    $component_data['base'] = $component_type;

    // The component name is just the same as the type for the base generator.
    $component_name = $component_type;

    // Assemble the component list from the request data.
    $this->component_list = $this->assembleComponentList($component_data);
    // The root generator is the first component in the list.
    $this->root_generator = reset($this->component_list);

    \DrupalCodeBuilder\Factory::getEnvironment()->log(array_keys($this->component_list), "Complete component list names");

    // Let each component detect whether it already exists in the given module
    // files.
    $this->detectExistence($this->component_list, $existing_module_files);

    // Now assemble them into a tree.
    // Calls containingComponent() on everything and puts it into a 2-D array
    // of parent => [children].
    $tree = $this->assembleComponentTree($this->component_list);
    \DrupalCodeBuilder\Factory::getEnvironment()->log($tree, "Component tree");

    // Let each file component in the tree gather data from its own children.
    $this->collectFileContents($this->component_list, $tree);

    //drush_print_r($generator->components);

    // Build files.
    // Get info on files. All components that wish to provide a file should have
    // registered themselves as first-level children of the root component.
    $files = $this->collectFiles($this->component_list, $tree);

    // Filter files according to the requested build list.
    if (isset($component_data['requested_build'])) {
      $this->root_generator->applyBuildListFilter($files, $component_data['requested_build'], $component_data);
    }

    // Then we assemble the files into a simple array of full filename and
    // contents.
    $files_assembled = $this->assembleFiles($files);

    return $files_assembled;
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
  protected function assembleComponentList($root_component) {
    return $this->getHelper('ComponentCollector')->assembleComponentList($root_component);
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
   * Assemble a tree of components, grouped by what they contain.
   *
   * For example, a code file contains its functions; a form component
   * contains the handler functions.
   *
   * This iterates over the flat list of components assembled by
   * assembleComponentList(), and re-assembles it as a tree.
   *
   * The tree is an array of parentage data, where keys are the names of
   * components that are parents, and values are flat arrays of component names.
   * The top level of the tree is the root component, whose name is its type,
   * e.g. 'module'.
   * To traverse the tree:
   *  - access the base component name
   *  - iterate over its children
   *  - recursively do the same thing to each child component.
   *
   * Not all components in the component list need to place themselves into the
   * tree, but this means that they will not participate in file assembly.
   *
   * @param $components
   *  The list of components, as assembled by assembleComponentList().
   *
   * @return
   *  A tree of parentage data for components, as an array keyed by the parent
   *  component name, where each value is an array of the names of the child
   *  components. So for example, the list of children of component 'foo' is
   *  given by $tree['foo'].
   */
  protected function assembleComponentTree($components) {
    $tree = array();
    foreach ($components as $name => $component) {
      $parent_name = $component->containingComponent();
      if (!empty($parent_name)) {
        $tree[$parent_name][] = $name;
      }
    }

    return $tree;
  }

  /**
   * Allow file components to gather data from their child components.
   *
   * @param $components
   *  The array of components.
   * @param $tree
   *  The tree array.
   */
  protected function collectFileContents($components, $tree) {
    // Iterate over all file-providing components, i.e. one level below the root
    // of the tree.
    $root_component_name = $this->root_generator->getUniqueID();
    foreach ($tree[$root_component_name] as $file_component_name) {
      // Skip files with no children in the tree.
      if (empty($tree[$file_component_name])) {
        continue;
      }

      // Let the file component run over its children iteratively.
      // (Not literally ;)
      $components[$file_component_name]->buildComponentContentsIterative($components, $tree);
    }
  }

  /**
   * Collect file data from components.
   *
   * This assembles an array, keyed by an arbitrary ID for the file, whose
   * values are arrays with the following properties:
   *  - 'body': An array of lines of content for the file.
   *  - 'path': The path for the file, relative to the module folder.
   *  - 'filename': The filename for the file.
   *  - 'join_string': The string with which to join the items in the body
   *    array. (TODO: remove this!)
   *
   * @param $component_list
   *  The component list.
   * @param $tree
   *  An array of parentage data about components, as given by
   *  assembleComponentTree().
   *
   * @return
   *  An array of file info, keyed by arbitrary file ID.
   */
  protected function collectFiles($component_list, $tree) {
    $file_info = array();

    // Components which provide a file should have registered themselves as
    // children of the root component.
    $root_component_name = $this->root_generator->getUniqueID();
    foreach ($tree[$root_component_name] as $child_component_name) {
      $child_component = $component_list[$child_component_name];

      // Don't get files for existing components.
      // TODO! This is quick and dirty! It's a lot more complicated than this,
      // for instance with components that affect other files.
      // Currently the only component that will set this is Info, to make
      // adding code to existing modules look like it works!
      if ($child_component->exists) {
        continue;
      }

      $child_component_file_data = $child_component->getFileInfo();
      if (is_array($child_component_file_data)) {
        $file_info += $child_component_file_data;
      }
    }

    return $file_info;
  }

  /**
   * Assemble file info into filename and code.
   *
   * @param $files
   *  An array of file info, as compiled by collectFiles().
   *
   * @return
   *  An array of files ready for output. Keys are the filepath and filename
   *  relative to the module folder (eg, 'foo.module', 'tests/module.test');
   *  values are strings of the contents for each file.
   */
  protected function assembleFiles($files) {
    $return = array();

    foreach ($files as $file_id => $file_info) {
      if (!empty($file_info['path'])) {
        $filepath = $file_info['path'] . '/' . $file_info['filename'];
      }
      else {
        $filepath = $file_info['filename'];
      }

      $code = implode($file_info['join_string'], $file_info['body']);

      // Replace tokens in file contents and file path.
      $variables = $this->root_generator->getReplacements();
      $code = strtr($code, $variables);
      $filepath = strtr($filepath, $variables);

      $return[$filepath] = $code;
    }

    return $return;
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
    return $this->getHelper('ComponentClassHandler')->getGenerator($component_type, $component_name, $component_data, $this->root_generator);
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
