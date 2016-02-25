<?php

/**
 * @file
 * Contains ModuleBuilder\Task\Generate.
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
   * The list of components.
   *
   * This is keyed by the name of the component name. Values are the
   * instantiated component generators.
   */
  protected $component_list;

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
    $this->component_list = $this->root_generator->assembleComponentList();
    \ModuleBuilder\Factory::getEnvironment()->log(array_keys($this->component_list), "Complete component list names");

    // Now assemble them into a tree.
    // Calls containingComponent() on everything and puts it into a 2-D array
    // of parent => [children].
    $tree = $this->assembleComponentTree($this->component_list);
    \ModuleBuilder\Factory::getEnvironment()->log($tree, "Component tree");

    // Let each file component in the tree gather data from its own children.
    $this->collectFileContents($this->component_list, $tree);

    //drush_print_r($generator->components);

    // Build files.
    // Get info on files. All components that wish to provide a file should have
    // registered themselves as first-level children of the root component.
    $files = $this->collectFiles($this->component_list, $tree);

    // Allow all components to alter all the collected files.
    $this->filesAlter($files, $this->component_list);

    // Then we assemble the files into a simple array of full filename and
    // contents.
    $files_assembled = $this->assembleFiles($files);

    return $files_assembled;
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
  public function assembleComponentTree($components) {
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
    $root_component_name = $this->root_generator->name;
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
   * Allow components to alter the files prior to output.
   *
   * @param $files
   *  The array of file info, passed by reference.
   * @param $component_list
   *  The component list.
   */
  protected function filesAlter(&$files, $component_list) {
    foreach ($component_list as $name => $component) {
      $component->filesAlter($files, $component_list);
    }
  }

  /**
   * Collect file data from components.
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
    $root_component_name = $this->root_generator->name;
    foreach ($tree[$root_component_name] as $child_component_name) {
      $child_component = $component_list[$child_component_name];
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
  function assembleFiles($files) {
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
   * @throws \ModuleBuilder\Exception\InvalidInputException
   *   Throws an exception if the given component type does not correspond to
   *   a component class.
   */
  public function getGenerator($component_type, $component_name, $component_data = array()) {
    $class = $this->getGeneratorClass($component_type);

    if (!class_exists($class)) {
      throw new \ModuleBuilder\Exception\InvalidInputException(strtr("Invalid component type !type.", array(
        '!type' => htmlspecialchars($component_type, ENT_QUOTES, 'UTF-8'),
      )));
    }

    $generator = new $class($component_name, $component_data, $this, $this->root_generator);

    return $generator;
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
