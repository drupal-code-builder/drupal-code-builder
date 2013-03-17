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
    // A module needs:
    //  - info file
    //  - hooks & callbacks: abstract component, which then produces:
    //    -- files
    //  - other abstract components which we don't do yet: form, entity type.
    //    -- (Node these will want to merge into the main module file!!!)
    //  - tests
    //    -- files
    // TODO: make this react to the drush --build param to know what to output!
    return array(
      // TODO:
      //'hooks',
      //'callbacks', ?? separate or with hooks?
      'info',
      'readme',
    );
  }

  // No need to declare collectFiles(): parent class will have something that
  // does nothing apart from recurse.

}
