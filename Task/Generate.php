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
    return $class::$sanity_level;
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
    $class = $this->getGeneratorClass($this->base);
    return $class::getComponentDataInfo($include_computed);
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
    $this->setComponentDataPropertyDefault($property_name, $property_info, $component_data, 'prepare');
    // Handle compound property child items if they are present.
    if (isset($property_info['properties']) && isset($component_data[$property_name]) && is_array($component_data[$property_name])) {
      foreach ($component_data[$property_name] as $delta => $delta_data) {
        foreach ($property_info['properties'] as $child_property_name => $child_property_info) {
          $this->setComponentDataPropertyDefault($child_property_name, $child_property_info, $component_data[$property_name][$delta], 'prepare');
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
   * Process component data prior to passing it to generateComponent().
   *
   * Performs final processing for the component data:
   *  - sets default values on empty properties. To prevent a default being set
   *    and keep the component a property represents absent, set it to FALSE.
   *  - performs additional processing that a property may require
   *  - expand properties that represent child components.
   *
   * @param &$component_data
   *  The component data array.
   * @param $component_type
   *  The component type for the data being processed.
   */
  protected function processComponentData(&$component_data, $component_type) {
    // Set defaults for properties that don't have a value yet.
    // First, get the component data info again, with the computed properties
    // this time, so we can add them in.
    if (empty($component_type)) {
      $component_type = $this->base;
    }
    $class = $this->getGeneratorClass($component_type);

    $component_data_info = $class::getComponentDataInfo(TRUE);

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
        $class = $this->getGeneratorClass($component_type);
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
      if (empty($property_info['process_default']) && empty($property_info['computed'])) {
        // Allow an empty value to remain empty if the property is neither:
        //  - computed: this never gets shown to the user, so we must provide a
        //    default always.
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

  /**
   * Generate the files for a component.
   *
   * @param $component_data
   *  An associative array of data for the component. Values depend on the
   *  component class. For details, see the constructor of the generator, of the
   *  form DrupalCodeBuilder\Generator\COMPONENT, e.g.
   *  DrupalCodeBuilder\Generator\Module::__construct().
   *
   * @return
   *  A files array whose keys are filepaths (relative to the module folder) and
   *  values are the code destined for each file.
   */
  public function generateComponent($component_data) {
    // Add the top-level component to the data.
    $component_type = $this->base;
    $component_data['base'] = $component_type;

    // The component name is just the same as the type for the base generator.
    $component_name = $component_type;

    // Process the root component's data.
    $this->processComponentData($component_data, $component_type);

    // Get the root component generator.
    // The component name is just the same as the type for the base generator.
    $root_generator = $this->getGenerator($component_type, $component_name, $component_data);

    // Set the root generator on ourselves now we actually have it.
    $this->root_generator = $root_generator;

    // Recursively assemble all the components that are needed.
    $this->component_list = $this->assembleComponentList($root_generator);
    \DrupalCodeBuilder\Factory::getEnvironment()->log(array_keys($this->component_list), "Complete component list names");

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
      \DrupalCodeBuilder\Factory::getEnvironment()->log(array_keys($current_level), "starting level $level_index");

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
          $generator = $this->getGenerator($component_type, $request_name, $component_data);

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
   */
  public function getGenerator($component_type, $component_name, $component_data = array()) {
    $class = $this->getGeneratorClass($component_type);

    if (!class_exists($class)) {
      throw new \DrupalCodeBuilder\Exception\InvalidInputException(strtr("Invalid component type !type.", array(
        '!type' => htmlspecialchars($component_type, ENT_QUOTES, 'UTF-8'),
      )));
    }

    $generator = new $class($component_name, $component_data, $this->root_generator);

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
   *  'DrupalCodeBuilder\Generator\Info6'.
   */
  public static function getGeneratorClass($type) {
    $type     = ucfirst($type);
    $version  = \DrupalCodeBuilder\Factory::getEnvironment()->getCoreMajorVersion();
    $class    = 'DrupalCodeBuilder\\Generator\\' . $type . $version;

    // If there is no version-specific class, use the base class.
    if (!class_exists($class)) {
      $class  = 'DrupalCodeBuilder\\Generator\\' . $type;
    }
    return $class;
  }

}
