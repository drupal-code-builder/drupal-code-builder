<?php

/**
 * @file
 * Module builder code generating code.
 */

/**
 * Generate the files for a component.
 *
 * This is the entry point for the generating system.
 *
 * @param $component
 *  A component name. Currently supports 'module' and 'theme'.
 * @param $component_data
 *  An associative array of data for the component. Values depend on the
 *  component class.
 *
 * @return
 *  A files array whose keys are filepaths (relative to the module folder) and
 *  values are the code destined for each file.
 */
function module_builder_generate_component($component, $component_data) {
  // Add the top-level component to the data.
  $component_data['base'] = $component;

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

  $files_assembled = $generator->assembleFiles($files);

  return $files_assembled;
}

/**
 * Helper function to get the desired class.
 *
 * The hierarchy is:
 *  - ModuleBuilderGenerator
 *    - ModuleBuilderGeneratorModuleCodeFile
 *      - ModuleBuilderGeneratorModuleCodeFile6
 *    - ModuleBuilderGeneratorInfo
 *      - ModuleBuilderGeneratorInfo5
 *      - ModuleBuilderGeneratorInfo6
 *      - ModuleBuilderGeneratorInfo7
 *    - ModuleBuilderGeneratorReadme
 *
 * @param $type
 *  The type of the component. This is the key of arrays returned by
 *  generators' subComponents() method.
 *
 * @return
 *  A class name for the type and, if it exists, version, e.g.
 *  'ModuleBuilderGeneratorInfo6'.
 *
 * @see module_builder_autoload()
 */
function module_builder_get_class($type) {
  // Include our general include files, which contains base and parent classes.
  include_once("generators/ModuleBuilderGenerators.inc");

  $type     = ucfirst($type);
  $version  = _module_builder_drupal_major_version();
  $class    = 'ModuleBuilderGenerator' . $type . $version;

  // Trigger the autoload for the base name without the version, as all versions
  // are in the same file.
  class_exists('ModuleBuilderGenerator' . $type);

  // If there is no version-specific class, use the base class.
  if (!class_exists($class)) {
    $class  = 'ModuleBuilderGenerator' . $type;
  }
  return $class;
}

/**
 * Autoload handler for generator classes.
 *
 * @see module_builder_get_class()
 */
function module_builder_autoload($class) {
  // Generator classes are in standardly named files, with all versions in the
  // same file.
  if (file_exists(dirname(__FILE__) . "/generators/$class.inc")) {
    include_once("generators/$class.inc");
  }
}
