<?php

/**
 * Component generator: module.
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
    // WIP!!!! make this react to the drush --build param to know what to output!

    $components = array();

    // Sigh... lots of complexity here, especially once we add the UI into the
    // mix!
    // $module_data['requested_build'] is an array of stuff. The values that
    // matter here are 'all', 'info', 'code', 'readme'.
    // For anything else, the hooks component takes care of further filtering.
    $build_list = $module_data['requested_build'];
    //drush_print_r($build_list);
    if (isset($build_list['info'])) {
      $components['info'] = 'info';
    }

    if (isset($build_list['readme'])) {
      $components['readme'] = 'readme';
    }

    if (isset($build_list['code'])) {
      $components['hooks'] = 'hooks';
    }

    // If that's the only thing requested, and we recognized it, we're done.
    if (count($build_list) == 1 && count($components) == 1) {
      // RETURN!
      return $components;
    }

    // The special token 'all' means info, hooks, and readme.
    if (isset($build_list['all'])) {
      $components['info'] = 'info';
      $components['hooks'] = 'hooks';
      $components['readme'] = 'readme';
      // TODO: this is messy, as things further down need this.
      // The build list should be cleaned up, so 'all' is removed, or something!?!?
    }

    // If we get here, we always add hooks, because at this point we can't tell
    // if there are specific files being requested.
    $components['hooks'] = 'hooks';

    // TODO: refactor this.
    // 1. declare the complete array we want to return: info, hooks, readme.
    // 2. a. if 'all', just return that.
    //    b. if an array, and all items intersect our definition, return the
    //      intersection
    //    c. if there are things in the request that don't intersect, assume
    //      they are subcomponents of 'hooks': return the intersection AND
    //      'hooks'.

    //drush_print_r($components);

    return $components;
  }

  // No need to declare collectFiles(): parent class will have something that
  // does nothing apart from recurse.

}
