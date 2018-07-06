<?php

namespace DrupalCodeBuilder\Environment;

/**
 * Environment class for use as a Drupal Library with Libraries API.
 *
 * This allows use of Drupal Code Builder in the following way:
 *  - place the Drupal Code Builder folder in sites/all/libraries
 *  - create a normal Drupal module, with a dependency on Libraries API.
 *
 * Sample code to include MB as a library:
 * @code
 *  function YOURMODULE_libraries_info() {
 *    $libraries['drupal-code-builder'] = array(
 *      // Only used in administrative UI of Libraries API.
 *      'name' => 'Drupal Code Builder',
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
 *        'module_builder' => array(
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
class DrupalLibrariesAPI extends DrupalUI {

  /**
   * Get a path to a module builder file or folder.
   */
  function getPath($subpath) {
    $path = libraries_get_path('drupal-code-builder');
    $path = $path . '/' . $subpath;
    return $path;
  }

}
