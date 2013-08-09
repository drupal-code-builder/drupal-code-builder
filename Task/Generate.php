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
class Generate extends Base {

  /**
   * The sanity level this task requires to operate.
   *
   * We have no sanity level; it's obtained from our base component generator.
   */
  protected $sanity_level = NULL;

  /**
   * Our base component name, i.e. either 'module' or 'theme'.
   */
  public $base;

  /**
   * Our base generator.
   */
  public $base_generator;

  /**
   * Override the base constructor.
   *
   * @param $environment
   *  The current environment handler.
   * @param $component_name
   *  A component name. Currently supports 'module' and 'theme'.
   *  (We need this early on so we can use it to determine our sanity level.)
   */
  public function __construct($environment, $component_name) {
    $this->environment = $environment;

    $this->initGenerators();

    // Fake the component data for now, as it's expected by the constructor.
    $component_data = array();

    $this->base = $component_name;
    $this->base_generator = $this->getGenerator($component_name, $component_data);
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
  function getSanityLevel() {
    return $this->base_generator->sanity_level;
  }

  /**
   * Helper to perform setup tasks: include files, register autoloader.
   */
  function initGenerators() {
    // Load the legacy procedural include file, as that has functions we need.
    // TODO: move these into this class.
    $this->environment->loadInclude('generate');

    // Register our autoload handler for generator classes.
    spl_autoload_register('module_builder_autoload');
  }

  /**
   * Generate the files for a component.
   *
   * This is the entry point for the generating system.
   *
   * (Replaces module_builder_generate_component().)
   *
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
  public function generateComponent($component_data) {
    // Add the top-level component to the data.
    $component_data['base'] = $this->base;

    // Set the component data on the base generator, as when we built it in
    // our __construct() it got a dummy empty array.
    $this->base_generator->component_data = $component_data;

    // Recursively get subcomponents.
    $this->base_generator->getSubComponents();

    //drush_print_r($generator->components);

    // Recursively build files.
    $files = array();
    $this->base_generator->collectFiles($files);
    //drush_print_r($files);

    $files_assembled = $this->base_generator->assembleFiles($files);

    return $files_assembled;
  }

  /**
   * Generator factory. WIP!
   *
   * TODO: switch over to using this everywhere.
   */
  public function getGenerator($component, $component_data) {
    $class = module_builder_get_class($component);
    $generator = new $class($component, $component_data);

    // Each generator needs a link back to the factory to be able to make more
    // generators, and also so it can access the environment.
    $generator->task = $this;

    return $generator;
  }

}
