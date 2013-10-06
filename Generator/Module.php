<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\Module.
 */

namespace ModuleBuider\Generator;

/**
 * Component generator: module.
 *
 * This is a base generator: that is, it's one that may act as the initial
 * requested component given to Task\Generate. (In theory, this could also get
 * requested by something else, for example if we wanted Tests to be able to
 * request a testing module, but that's for another day.)
 *
 * Conceptual hierarchy of generators beneath this in the request tree:
 *  - Hooks
 *    - HookImplementation (multiple)
 *  - RouterItem
 *    - HookMenu (which is itself a HookImplementation!)
 *    - Routing (D8 only)
 *  - PHPFile (multiple)
 *  - Info
 *  - Readme
 *  - API
 *  - Tests
 *  - AdminSettingsForm
 *
 * This generator looks in $module data to determine which of these generators
 * to add. Generators can be requested by name, with various extra special
 * values which are documented in this class's __construct().
 */
class Module extends BaseGenerator {

  /**
   * The sanity level this generator requires to operate.
   */
  public $sanity_level = 'hook_data';

  /**
   * The data for the component.
   *
   * This is initially the given data for generating the module, but other
   * generators may add data to it during the generating process.
   */
  public $component_data = array();

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the module. The properties are as
   *   follows:
   *    - 'base': The type of component: 'module'.
   *    - 'module_root_name': The machine name for the module.
   *    - 'module_readable_name': The human readable name for the module.
   *    - 'module_short_description': The module's description text.
   *    - 'module_help_text': Help text for the module. If this is given, then
   *       hook_help() is automatically added to the list of required hooks.
   *    - 'hooks': An associative array whose keys are full hook names
   *      (eg 'hook_menu'), where requested hooks have a value of TRUE.
   *      Unwanted hooks may also be included as keys provided their value is
   *      FALSE.
   *    - 'module_dependencies': A string of module dependencies, separated by
   *       spaces, e.g. 'forum views'.
   *    - 'module_package': The module package.
   *    - 'component_folder': (optional) The destination folder to write the
   *      module files to.
   *    - 'module_files': ??? OBSOLETE!? added by this function. A flat array
   *      of filenames that have been generated.
   *    - 'requested_build': An array whose keys are names of subcomponents to
   *       build. Component names are defined in requiredComponents(), and include:
   *       - 'all': everything we can do.
   *       - 'code': PHP code files.
   *       - 'info': the info file.
   *       - 'module': the .module file.
   *       - 'install': the .install file.
   *       - 'tests': test file.
   *       - 'api': api.php hook documentation file.
   *       - FILE ID: requests a particular code file, by the abbreviated name.
   *         This is the filename without the initial 'MODULE.' or the '.inc'
   *         extension.
   *    - 'requested_components': An array of components to build (in addition
   *      to any that are added automatically). This should in the same form
   *      as the return from requiredComponents(), thus keyed by component name,
   *      with values either a component type or an array of data.
   *  Properties added by generators during the process:
   *    - 'hook_file_data': Added by the Hooks generator. Keyed by the component
   *      names of the ModuleCodeFile type components that Hooks adds.
   */
  function __construct($component_name, $component_data = array()) {
    // Fill in all defaults. This allows default values to be set here that
    // are not involved in the UI, such as the module class name.
    $this->getComponentDataDefaultValue($component_data);

    // This method is only here to document the component data.
    parent::__construct($component_name, $component_data);
  }

  /**
   * Set a default value for a property in the component data array.
   *
   * This should be implemented by entry components. It is for use by UIs that
   * want to present default values to the user in a progressive manner. For
   * example, the Drush interactive mode may present a default value for the
   * module human name based on the value the user has already entered for the
   * machine name.
   *
   * To get the full benefit of this, it is important to call this function
   * in the correct order, as given by the array of values in the function.
   *
   * @param $component_data
   *  The array of component data assembled so far, passed by reference. The
   *  default value will get set at $property_name, if a default is available.
   * @param $property_name
   *  (optional) The name of a property in $component_data to get a default
   *  value for. If omitted, all existing default values will be merged in.
   */
  public function getComponentDataDefaultValue(&$component_data, $property_name = NULL) {
    // The default values that this component provides.
    // This may use anonymous functions for default values that rely on other
    // values in the component data, including ones which may not be set when
    // we come here for earlier values. This may seem like over-engineering,
    // but it means that we don't need to repeat the list of property names.
    // Note that values can only depend on values earlier in the array.
    $default_values = array(
      'module_root_name' => 'mymodule',
      'module_readable_name' => function($component_data) {
        return ucfirst(str_replace('_', ' ', $component_data['module_root_name']));
      },
      'module_short_description' => 'TODO: Description of module',
      // The following defaults are for ease of developing.
      // Uncomment them to reduce the amount of typing needed for testing.
      //'hooks' => 'init',
      //'router_items' => 'path/foo path/bar',
      // The following properties shouldn't be offered as UI options.
      'module_camel_case_name' => function($component_data) {
        $pieces = explode('_', $component_data['module_root_name']);
        $pieces = array_map('ucfirst', $pieces);
        return implode('', $pieces);
      },
    );

    if (isset($property_name)) {
      // Don't clobber existing values; only set a default if there is nothing
      // already present for that property.
      if (!isset($component_data[$property_name]) && isset($default_values[$property_name])) {
        // The default value in the array is either an anonymous function, or
        // a plain value.
        if (is_callable($default_values[$property_name])) {
          $default_value = $default_values[$property_name]($component_data);
        }
        else {
          $default_value = $default_values[$property_name];
        }

        $component_data[$property_name] = $default_value;
      }
      return;
    }
    else {
      // If the property name isn't given, we're being asked to merge in all
      // defaults that we know about. Call ourselves recursively for all the
      // property names.
      $property_names = array_keys($default_values);
      foreach ($property_names as $property_name) {
        $this->getComponentDataDefaultValue($component_data, $property_name);
      }

      // This is just for visual symmetry with the other clause.
      return;
    }
  }

  /**
   * Declares the subcomponents for this component.
   *
   * These are not necessarily child classes, just components this needs.
   *
   * TODO: handle version stuff here? Or better to have it transparent in the
   * factory function?
   *
   * @return
   *  An array of subcomponent types.
   */
  protected function requiredComponents() {
    // Add in defaults. This can't be done in __construct() because root
    // generators actually don't get their component data till later. WTF!
    $this->component_data += array(
      'requested_components' => array(),
    );

    // The requested build list is a hairy old thing, so figure that out first
    // and in a helper.
    $build_list_components = $this->buildListComponents();

    $components = $build_list_components + $this->component_data['requested_components'];

    return $components;
  }

  /**
   * Helper to get a component list from the build list.
   *
   * The build list is a complex thing that is best left alone!
   */
  protected function buildListComponents() {
    $module_data = $this->component_data;

    // A module needs:
    //  - info file
    //  - hooks & callbacks: abstract component, which then produces:
    //    -- files
    //  - other abstract components which we don't do yet: form, entity type.
    //    -- (Node these will want to merge into the main module file!!!)
    //  - tests
    //    -- files
    //
    // $module_data['requested_build'] is an array of stuff. The values that
    // matter here are 'all', 'info', 'code', 'readme'.
    // For anything else, the hooks component takes care of further filtering.

    // Start by defining the subcomponents we know how to handle.
    // Keys are names, values are types (to be used to build class names).
    $components = array(
      'hooks' => 'hooks',
      // Component type case must match the filename for case-sensitive
      // filesystems (i.e. not OS X).
      'api' => 'API',
      'readme' => 'readme',
      'tests' => 'tests',
      // Info must go last, as it needs to know what files are being generated.
      'info' => 'info',
    );

    // Create a build list.
    // Take the keys, as all values are set to TRUE, and standardize to lower
    // case for comparisons.
    $build_list = array_keys($module_data['requested_build']);
    array_walk($build_list, function(&$s) {
      $s = strtolower($s);
    });
    $build_list = array_combine($build_list, $build_list);

    // Case 1: everything was requested: return everything!
    if (isset($build_list['all'])) {
      return $components;
    }

    // Make a list of component names to compare with what was requested.
    $component_list = array_keys($components);
    // Standardize to lower case for comparison.
    array_walk($component_list, function(&$s) {
      $s = strtolower($s);
    });
    //drush_print_r($component_list);
    //drush_print_r($build_list);

    // Get the components that were requested.
    $intersection_components = array_intersect($component_list, $build_list);
    // Get the requested components that we don't understand.
    $unknown_build_list = array_diff($build_list, $component_list);

    // Case 2: there are no unknown components. Return just what we were asked
    // for.
    if (empty($unknown_build_list)) {
      return $intersection_components;
    }

    // Case 3: there are some requested components we don't know anything about.
    // We assume that these are abbreviated filenames for the attention of
    // ModuleBuider\Generator\Hooks; therefore we must ensure 'hooks' is in the
    // list.
    $intersection_components['hooks'] = 'hooks';

    // TODO: if the components create files containing classes, we probably
    // need to add the 'info' component, BUT we need to add to the .info file
    // rather than rewrite it! This requires (as does template.php) a system for
    // adding to existing files.

    return $intersection_components;
  }

  // No need to declare collectFiles(): parent class will have something that
  // does nothing apart from recurse.

}
