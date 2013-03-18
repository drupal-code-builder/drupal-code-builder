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
    $module_data = $this->module_data;

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
