<?php

/**
 * Component generator: module.
 *
 * Hierarchy of generators beneath this:
 *  - hooks
 *    - code files
 *  - info
 *  - readme
 *
 * This generator looks in $module data to determine which of these generators
 * to add. Generators can be requested by name, with the following extra special
 * values:
 *  - 'all': requests all generators.
 *  - 'code': equivalent to 'hooks' (at least for now).
 *  - FILE ID: requests a particular code file, by the abbreviated name. This
 *    is the filename without the initial 'MODULE.' or the '.inc' extension.
 */
class ModuleBuilderGeneratorModule extends ModuleBuilderGeneratorComponent {

  /**
   * Constructor method; sets the module data.
   *
   * @param $component_name
   *   The identifier for the component. This is often the same as the type
   *   (e.g., 'module', 'hooks') but in the case of types used multiple times
   *   this will be a unique identifier.
   * @param $module_data
   *   An associative array of data for the module, as received by
   *   module_builder_generate_component().
   *   An associative array of data for the module, [passed by reference so data
   *   on generated files can be added!?!?!?!]
   *   The keys can *mostly* be taken straight from form values. They are as
   *   follows:
   *     - 'module_root_name'
   *     - 'module_readable_name'
   *     - 'module_short_description'
   *     - 'module_help_text'
   *     - 'hooks': An associative array whose keys are full hook names
   *       (eg 'hook_menu'), where requested hooks have a value of TRUE.
   *       Unwanted hooks may also be included as keys provided their value is
   *       FALSE.
   *     - 'module_dependencies': a string of dependencies, eg 'forum views'.
   *     - 'module_package': the module package.
   *     - 'module_folder': (optional) The destination folder to write the
   *       module files to.
   *     - 'module_files': ??? OBSOLETE!? added by this function. A flat array
   *       of filenames that have been generated.
   *     - 'requested_build': An array whose keys are requested things to build.
   *        This can be one of:
   *        - 'all': everything we can do.
   *        - 'code': PHP code files.
   *        - 'info': only info file.
   *        - 'module': only module file.
   *        - 'install': only install file.
   *        - ???: any short filename.
   */
  function __construct($component_name, $module_data) {
    parent::__construct($component_name, $module_data);
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
  function subComponents() {
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
    $components = array(
      'info' => 'info',
      'hooks' => 'hooks',
      'readme' => 'readme',
    );

    // Fill the build list, as this comes in with values all set to TRUE.
    $build_list = array_combine(array_keys($module_data['requested_build']), array_keys($module_data['requested_build']));

    // Case 1: everything was requested: return everything!
    if (isset($build_list['all'])) {
      return $components;
    }

    // Get the components that were requested.
    $intersection_components = array_intersect($components, $build_list);
    // Get the requested components that we don't understand.
    $unknown_build_list = array_diff($build_list, $components);

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

    return $intersection_components;
  }

  // No need to declare collectFiles(): parent class will have something that
  // does nothing apart from recurse.

}
