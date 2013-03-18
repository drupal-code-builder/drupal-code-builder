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

    // If that's the only thing requested, we're done.
    if (count($build_list) == 1) {
      return $components;
    }

    // The special token 'all' means info, hooks, and readme.
    if (isset($build_list['all'])) {
      $components['info'] = 'info';
      $components['hooks'] = 'hooks';
      $components['readme'] = 'readme';
    }

    // We always add hooks, FOR NOW, because at this point we can't tell if
    // there are specific files being requested!
    // TODO: we need to add hooks if there's 'all' or anything we don;t
    // recognize that's a filename.
    $components['hooks'] = 'hooks';

    return $components;
  }

  // No need to declare collectFiles(): parent class will have something that
  // does nothing apart from recurse.

}
