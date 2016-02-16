<?php

/**
 * @file
 * Contains \ModuleBuilder\Environment\EnvironmentInterface.
 */

namespace ModuleBuilder\Environment;

/**
 * Interface for environments.
 */
interface EnvironmentInterface {

  /**
   * Sanity check our basic environment to a specified level.
   *
   * This is called by ModuleBuilder\Factory when a Task is requested from it.
   *
   * The tests can be skipped by first calling skipSanityCheck(). This should
   * only be used in rare circumstances (such as drush autocomplete).
   *
   * @param $sanity_level
   *  The level up to which to verify sanity. The successive levels are:
   *    - 'none': No checks required.
   *    - 'data_directory_exists': The hooks directory exists (or can be
   *      created) and is writable.
   *    - 'component_data_processed': The hook data files are present in the
   *      hooks directory.
   *
   * @throws \ModuleBuilder\Exception\SanityException
   *  Throws an exception if the environment is not ready at the specified
   *  level. It's up to the caller to provide meaningful feedback to the user.
   */
  public function verifyEnvironment($sanity_level);

  /**
   * Set the environment to skip sanity checks until further notice.
   *
   * This may be set on the environment after it has been initialized. Example:
   * @code
   * \ModuleBuilder\Factory::setEnvironmentClass('Drush', 8);
   * \ModuleBuilder\Factory::getEnvironment()->skipSanityCheck(TRUE);
   * @endcode
   *
   * @param bool $setting
   *  Set to TRUE to set the environment to skip sanity checks; FALSE to restore
   *  sanity.
   */
  public function skipSanityCheck($setting);

  /**
   * Get the major version of Drupal core.
   *
   * @return int
   *  The major version number, e.g. 6, 7, 8.
   */
  public function getCoreMajorVersion();

  /**
  * Get path to the directory where collected data about hooks is stored.
  *
  * Depending on our environment this is either relative to Drupal root or
  * absolute, but in either case it is in a format that other environment
  * methods can use.
  *
  * Note that initially this only represents a user setting, and is not verified
  * as an existing, writable directory unless the Task's sanity level has
  * requested it.
   *
   * @return string
   *  The path to the directory.
   */
  public function getHooksDirectory();

  /**
   * Get a path to a resource that is safe to use either on Drupal or Drush.
   *
   * @param $subpath
   *  The subpath inside the module_builder folder. Eg, 'templates'.
   */
  function getPath($subpath);

  /**
   * Returns information about system object files (modules, themes, etc.).
   *
   * Version-independent wrapper for drupal_system_listing().
   *
   * This function is used to find all or some system object files (module files,
   * theme files, etc.) that exist on the site. It searches in several locations,
   * depending on what type of object you are looking for. For instance, if you
   * are looking for modules and call:
   * @code
   * drupal_system_listing("/\.module$/", "modules", 'name', 0);
   * @endcode
   * this function will search the site-wide modules directory (i.e., /modules/),
   * your installation profile's directory (i.e.,
   * /profiles/your_site_profile/modules/), the all-sites directory (i.e.,
   * /sites/all/modules/), and your site-specific directory (i.e.,
   * /sites/your_site_dir/modules/), in that order, and return information about
   * all of the files ending in .module in those directories.
   *
   * The information is returned in an associative array, which can be keyed on
   * the file name ($key = 'filename'), the file name without the extension ($key
   * = 'name'), or the full file stream URI ($key = 'uri'). If you use a key of
   * 'filename' or 'name', files found later in the search will take precedence
   * over files found earlier (unless they belong to a module or theme not
   * compatible with Drupal core); if you choose a key of 'uri', you will get all
   * files found.
   *
   * @param string $mask
   *   The preg_match() regular expression for the files to find.
   * @param string $directory
   *   The subdirectory name in which the files are found. For example,
   *   'modules' will search in sub-directories of the top-level /modules
   *   directory, sub-directories of /sites/all/modules/, etc.
   * @param string $key
   *   The key to be used for the associative array returned. Possible values are
   *   'uri', for the file's URI; 'filename', for the basename of the file; and
   *   'name' for the name of the file without the extension. If you choose 'name'
   *   or 'filename', only the highest-precedence file will be returned.
   * @param int $min_depth
   *   Minimum depth of directories to return files from, relative to each
   *   directory searched. For instance, a minimum depth of 2 would find modules
   *   inside /modules/node/tests, but not modules directly in /modules/node.
   *
   * @return array
   *   An associative array of file objects, keyed on the chosen key. Each element
   *   in the array is an object containing file information, with properties:
   *   - 'uri': Full URI of the file.
   *   - 'filename': File name.
   *   - 'name': Name of file without the extension.
   */
  public function systemListing($mask, $directory, $key = 'name', $min_depth = 1);

  /**
   * Invoke hook_module_builder_info().
   *
   * @return
   *  Data gathered from the hook implementations.
   */
  public function invokeInfoHook();

  /**
   * Get a user preference setting.
   *
   * @param $name
   *   The name of the setting to return. Because we can be in a variety of
   *   environments and versions, we have our own names for our settings, which
   *   version helper classes may convert to something else. The following
   *   are recognized, but settings marked 'optional' need not be supported by
   *   an environment:
   *    - 'data_directory': The location of our stored documentation and
   *      processed data files.
   *    - 'detail_level': (optional) The amount of detail to add to generated
   *      code. 0 for normal level, 1 for additional detail.
   *    - 'footer': (optional) Text to add to the end of every module code file.
   * @param $default
   *   The default value to use if this variable has never been set.
   *
   * @return
   *   The value of the variable. Unserialization is taken care of as necessary.
   */
  public function getSetting($name, $default = NULL);

  /**
   * Get the path to a Drupal extension, e.g. a module or theme.
   *
   * @param $type
   *  The type. One of 'module' or 'theme'.
   * @param $name
   *  The name of the extension.
   *
   * @return
   *  The path to the extension, or NULL if it is not present and active.
   */
  public function getExtensionPath($type, $name);

}
