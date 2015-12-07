<?php

/**
 * @file
 * Contains ModuleBuilder\Environment\DrupalLibrary.
 */

namespace ModuleBuilder\Environment;

/**
 * Environment class for use as a Drupal Library.
 *
 * This allows use of Module Builder in the following way:
 *  - place the Module Builder folder in sites/all/libraries
 *  - create a normal Drupal module, with a dependency on Libraries API.
 *
 * Sample code to include MB as a library:
 * @code
 *  function YOURMODULE_libraries_info() {
 *    $libraries['module_builder'] = array(
 *      // Only used in administrative UI of Libraries API.
 *      'name' => 'Module Builder Core',
 *      'vendor url' => 'http://example.com',
 *      'download url' => 'http://example.com/download',
 *      // We have to declare a version.
 *      'version' => 'none',
 *      'files' => array(
 *        'php' => array(
 *          'Factory.php',
 *        ),
 *      ),
 *      // Auto-load the files.
 *      'integration files' => array(
 *        'module_builder_ui' => array(
 *          'php' => array(
 *            'Factory.php',
 *          ),
 *        ),
 *      ),
 *    );
 *    return $libraries;
 *  }
 * @endcode
 */
class DrupalLibrary extends DrupalUI {

  /**
   * Set the hooks directory.
   */
  function setHooksDirectory() {
    // Set the module folder based on variable.
    $directory = $this->getSetting('module_builder_hooks_directory', 'hooks');

    // Run it through version-specific stuff.
    // This basically prepends 'public://' or 'sites/default/files/'.
    $this->version_helper->directoryPath($directory);

    $this->hooks_directory = $directory;
  }

  /**
   * Get a path to a module builder file or folder.
   */
  function getPath($subpath) {
    $path = libraries_get_path('module_builder');
    $path = $path . '/' . $subpath;
    return $path;
  }

}
