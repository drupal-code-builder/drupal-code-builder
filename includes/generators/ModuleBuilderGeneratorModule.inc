<?php

/**
 * Component generator: module.
 *
 * Hierarchy of generators beneath this:
 *  - hooks
 *    - code files
 *  - info
 *  - readme
 *  - api
 *  - tests
 *
 * This generator looks in $module data to determine which of these generators
 * to add. Generators can be requested by name, with various extra special
 * values which are documented in this class's __construct().
 */
class ModuleBuilderGeneratorModule extends ModuleBuilderGeneratorComponent {

  /**
   * Constructor method; sets the module data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   An associative array of data for the module, as received by
   *   module_builder_generate_component().
   *   An associative array of data for the module, [passed by reference so data
   *   on generated files can be added!?!?!?!]
   *   The keys can *mostly* be taken straight from form values. They are as
   *   follows:
   *     - 'base': The type of component: 'module'.
   *     - 'module_root_name': The machine name for the module.
   *     - 'module_readable_name': The human readable name for the module.
   *     - 'module_short_description': The module's description text.
   *     - 'module_help_text': Help text for the module. If this is given, then
   *        hook_help() is automatically added to the list of required hooks.
   *     - 'hooks': An associative array whose keys are full hook names
   *       (eg 'hook_menu'), where requested hooks have a value of TRUE.
   *       Unwanted hooks may also be included as keys provided their value is
   *       FALSE.
   *     - 'module_dependencies': A string of module dependencies, separated by
   *        spaces, e.g. 'forum views'.
   *     - 'module_package': The module package.
   *     - 'component_folder': (optional) The destination folder to write the
   *       module files to.
   *     - 'module_files': ??? OBSOLETE!? added by this function. A flat array
   *       of filenames that have been generated.
   *     - 'requested_build': An array whose keys are names of subcomponents to
   *        build. Component names are defined in subComponents(), and include:
   *        - 'all': everything we can do.
   *        - 'code': PHP code files.
   *        - 'info': the info file.
   *        - 'module': the .module file.
   *        - 'install': the .install file.
   *        - 'tests': test file.
   *        - 'api': api.php hook documentation file.
   *        - FILE ID: requests a particular code file, by the abbreviated name.
   *          This is the filename without the initial 'MODULE.' or the '.inc'
   *          extension.
   */
  function __construct($component_name, $component_data) {
    parent::__construct($component_name, $component_data);
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
  protected function subComponents() {
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
    // ModuleBuilderGeneratorHooks; therefore we must ensure 'hooks' is in the
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
