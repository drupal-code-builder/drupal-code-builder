<?php

/**
 * @file
 * Definition of ModuleBuider\Task\Generate.
 */

namespace ModuleBuider\Task;

/**
 * Task handler for generating a component.
 *
 * (Replaces generate.inc.)
 */
class Generate {

  /**
   * The sanity level this task requires to operate.
   */
  public $sanity_level = 'hook_data';

  /**
   * Constructor.
   *
   * @param $environment
   *  The current environment handler.
   */
  function __construct($environment) {
    $this->environment = $environment;
  }

  /**
   * Generate the files for a component.
   *
   * This is the entry point for the generating system.
   *
   * (Replaces module_builder_generate_component().)
   *
   * @param $component
   *  A component name. Currently supports 'module' and 'theme'.
   * @param $component_data
   *  An associative array of data for the component. Values depend on the
   *  component class. For details, see the constructor of the generator, of the
   *  form ModuleBuilderGeneratorCOMPONENT, e.g.
   *  ModuleBuilderGeneratorModule::__construct().
   *
   * @return
   *  A files array whose keys are filepaths (relative to the module folder) and
   *  values are the code destined for each file.
   */
  function generateComponent($component, $component_data) {
    // Load the legacy procedural include file, as that has functions we need.
    // TODO: move these into this class.
    $this->environment->loadInclude('generate');

    // Add the top-level component to the data.
    $component_data['base'] = $component;

    //drush_print_r($module_data);

    // Register our autoload handler for generator classes.
    spl_autoload_register('module_builder_autoload');

    $class = module_builder_get_class($component);
    $generator = new $class($component, $component_data);

    // Recursively get subcomponents.
    $generator->getSubComponents();

    //drush_print_r($generator->components);

    // Recursively build files.
    $files = array();
    $generator->collectFiles($files);
    //drush_print_r($files);

    $files_assembled = $generator->assembleFiles($files);

    return $files_assembled;
  }

  // Factory. WIP!
  // But how to other generators get to this???
  // call $this->factory->getGenerator($component);
  public function getGenerator($component, $component_data) {
    $class = module_builder_get_class($component);
    $generator = new $class($component, $component_data);

    // Each generator needs a link back to the factory to be able to make more
    // generators!
    $generator->factory = $this;

    return $generator;
  }

}
