<?php

/**
 * @file
 * Module builder code generating code.
 */

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
  $version  = $mb_factory = module_builder_get_factory()->environment->major_version;
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
