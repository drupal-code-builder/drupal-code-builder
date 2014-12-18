<?php

/**
 * @file
 * Contains Module Builder Environment handlers.
 *
 * The environment system provides an abstraction layer between Module Builder
 * and its current environment, e.g., whether we are running as a Drush plugin,
 * a Drupal module, or being loaded as a library, and what major version of
 * Drupal core we are running on. The environment handler takes care of things
 * such as:
 *  - how to output debug data
 *  - how to get the Drupal core version
 *  - how to load an include file with a version suffix
 *  - how to find the hooks data directory.
 * To initialize the environment, pass the environment handler class name as a
 * parameter to module_builder_get_factory():
 * @code
 * $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrupalUI');
 * @endcode
 * The classes for the execution environment (Drush, Drupal, library) are
 * supported by helper classes for Drupal core version, thus allowing the
 * execution environment to be orthogonal to the major version. All methods on
 * the helper core version object should be access via a wrapper on the main
 * environment class.
 */

/**
 * Base class for environments.
 */
abstract class ModuleBuilderEnvironmentBase {

  /**
   * Whether to skip the sanity tests.
   *
   * This may be set on the environment after its class name is passed to
   * module_builder_get_factory. Example:
   * @code
   * $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');
   * $mb_factory->environment->skipSanity = TRUE;
   * @endcode
   */
  public $skipSanity = FALSE;

  /**
   * The path to the hooks directory.
   *
   * Depending on our environment this is either relative to Drupal root or
   * absolute, but in either case it is in a format that other environment
   * methods can use.
   *
   * Set by the constructor, and thus may be accessed within a Task handler,
   * though initially this only represents a user setting, and is not verified
   * as an existing, writable directory unless the Task's sanity level has
   * requested it.
   */
  public $hooks_directory;

  /**
   * The major Drupal core version.
   *
   * Set by the constructor, and thus may be accessed within a Task handler.
   */
  public $major_version;

  /**
   * A helper object for version-specific code.
   *
   * This allows code specific to different major version of Drupal to be
   * orthogonal to the environment, without external systems having to deal
   * with it.
   */
  protected $version_helper;

  /**
   * Constructor.
   */
  function __construct() {
    // Set the major version.
    $this->setMajorVersion();

    // Set up the helper for version-specific code.
    $this->initVersionHelper();

    // Set the hooks directory.
    $this->setHooksDirectory();
  }

  /**
   * Set the hooks directory.
   */
  abstract function setHooksDirectory();

  /**
   * Sanity check our basic environment to a specified level.
   *
   * This is called by the factory when a Task is requested from it.
   *
   * If the property $skipSanity is set on this environment object, the tests
   * are skipped. This should only be used in rare circumstances (such as drush
   * autocomplete).
   *
   * @param $sanity_level
   *  The level up to which to verify sanity. The successive levels are:
   *    - 'none': No checks required.
   *    - 'hook_directory': The hooks directory exists (or can be created) and
   *      is writable.
   *    - 'hook_data': The hook data files are present in the hooks directory.
   *
   * @throws ModuleBuilderException
   *  Throws an exception if the environment is not ready at the specified
   *  level. It's up to the caller to provide meaningful feedback to the user.
   */
  public function verifyEnvironment($sanity_level) {
    // Allow the environment to request skipping the sanity checks.
    if ($this->skipSanity) {
      return;
    }

    // Sanity level 'none': nothing to do.
    if ($sanity_level == 'none') {
      return;
    }

    // Sanity level 'hook_directory':
    $this->version_helper->prepareDirectory($this->hooks_directory);

    // This is as far as we need to go for the hooks_directory level.
    if ($sanity_level == 'hook_directory') {
      return;
    }

    // Sanity level 'hook_data':
    $hooks_processed = $this->hooks_directory . "/hooks_processed.php";
    if (!file_exists($hooks_processed)) {
      $e = new ModuleBuilderException("No hook definitions found. You need to download hook definitions before using this module.");
      $e->needs_hooks_download = TRUE;
      throw $e;
    }

    // This is as far as we need to go for the hook_data level.
    if ($sanity_level == 'hook_data') {
      return;
    }

    // There are no further sanity levels!
  }

  /**
   * Output debug data.
   */
  abstract function debug($data, $message = '');

  /**
   * Get a path to a resource that is safe to use either on Drupal or Drush.
   *
   * @param $subpath
   *  The subpath inside the module_builder folder. Eg, 'templates'.
   */
  abstract function getPath($subpath);

  /**
   * Detect the major Drupal core version and set the property for it.
   *
   * Helper for __construct().
   */
  protected function setMajorVersion() {
    // ARGH D8 is different and at this point we can't specialize per-version,
    // since we're trying to GET the version!
    if (defined('VERSION')) {
      $version = VERSION;
    }
    else {
      $version = \Drupal::VERSION;
    }

    list($major_version) = explode('.', $version);

    $this->major_version = $major_version;
  }

  /**
   * Initialize the version helper object.
   *
   * Helper for __construct().
   */
  protected function initVersionHelper() {
    $helper_class_name = 'ModuleBuilderEnvironmentVersionHelper' . $this->major_version;

    $this->version_helper = new $helper_class_name($this);
  }

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
  public function systemListing($mask, $directory, $key = 'name', $min_depth = 1) {
    return $this->version_helper->systemListing($mask, $directory, $key, $min_depth);
  }

  /**
   * Invoke hook_module_builder_info().
   *
   * The tricky part is that we want to include ourselves, but module_builder
   * might not be installed (or even present) in Drupal if we are on Drush.
   *
   * @return
   *  Data gathered from the hook implementations.
   */
  public function invokeInfoHook() {
    return $this->version_helper->invokeInfoHook();
  }

  /**
   * Get a user preference setting.
   *
   * @param $name
   *   The name of the variable to return.
   * @param $default
   *   The default value to use if this variable has never been set.
   *
   * @return
   *   The value of the variable. Unserialization is taken care of as necessary.
   */
  public function getSetting($name, $default = NULL) {
    return $this->version_helper->getSetting($name, $default);
  }

}

/**
 * Environment class for Drupal UI.
 *
 * TODO: retire this; it's just for transition?
 */
class ModuleBuilderEnvironmentDrupalUI extends ModuleBuilderEnvironmentBase {

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
    $path = drupal_get_path('module', 'module_builder');
    $path = $path . '/' . $subpath;
    return $path;
  }

  /**
   * Output debug data.
   */
  function debug($data, $message = '') {
    if (module_exists('devel')) {
      dpm($data, $message);
    }
  }

}

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
 *          'includes/common.inc',
 *          'ModuleBuilderFactory.php',
 *        ),
 *      ),
 *      // Auto-load the files.
 *      'integration files' => array(
 *        'module_builder_ui' => array(
 *          'php' => array(
 *            'includes/common.inc',
 *            'ModuleBuilderFactory.php',
 *          ),
 *        ),
 *      ),
 *    );
 *    return $libraries;
 *  }
 * @endcode
 */
class ModuleBuilderEnvironmentDrupalLibrary extends ModuleBuilderEnvironmentDrupalUI {

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

/**
 * Environment class for use as a Drush plugin.
 */
class ModuleBuilderEnvironmentDrush extends ModuleBuilderEnvironmentBase {

  /**
   * Set the hooks directory.
   */
  function setHooksDirectory() {
    // Get the hooks directory.
    $directory = $this->getHooksDirectory();

    // Run it through version-specific stuff.
    // This basically prepends 'public://' or 'sites/default/files/'.
    $this->version_helper->directoryPath($directory);

    $this->hooks_directory = $directory;
  }

  /**
   * Get the hooks directory.
   *
   * On Drush, this can come from several places, in the following order of
   * preference:
   *  - The Drush --data option. This allows use of a central store of hook data
   *    that needs only be downloaded once for all Drupal sites. Subdirectories
   *    are made for each major version.
   *  - The Module Builder UI's variable. This will only be set if module
   *    builder has been installed as a Drupal module on the current site.
   */
  private function getHooksDirectory() {
    // Set the module folder based on variable.
    // First try the drush 'data' option.
    if (drush_get_option('data')) {
      $directory = drush_get_option('data');
      if ($directory) {
        // In pure Drush, the hooks folder contains subfolder for hooks for
        // each major version of Drupal.
        if (substr($directory, -1, 1) != '/') {
          $directory .= '/';
        }
        $directory .= $this->major_version;
        return $directory;
      }
    }
    // Second, check if we're in mixed drush.
    if (function_exists('variable_get')) {
      // We're in a loaded Drupal, but MB might not be installed here.
      $directory = variable_get('module_builder_hooks_directory', 'hooks');
      return $directory;
    }
    // If we get here then argh. Set to the default and hope...
    $directory = 'hooks';
    return $directory;
  }

  /**
   * Output debug data.
   */
  function debug($data, $message = '') {
    drush_print_r("== $message:");
    drush_print_r($data);
  }

  /**
   * Get a path to a module builder file or folder.
   */
  function getPath($subpath) {
    // On Drush we just have to jump through hoops.
    $mb_path = dirname(__FILE__) . '/..';

    $path = $mb_path . '/' . $subpath;

    return $path;
  }

}

/**
 * Base environment class for tests.
 */
abstract class ModuleBuilderEnvironmentTests extends ModuleBuilderEnvironmentBase {

  /**
   * Get a path to a module builder file or folder.
   */
  function getPath($subpath) {
    $path = dirname(__FILE__) . '/..';
    $path = $path . '/' . $subpath;
    return $path;
  }

  /**
   * Output debug data.
   */
  function debug($data, $message = '') {
    if (module_exists('devel')) {
      debug($data, $message);
    }
  }

}

/**
 * Environment class for tests using prepared sample hook data.
 */
class ModuleBuilderEnvironmentTestsSampleLocation extends ModuleBuilderEnvironmentTests {

  /**
   * Set the hooks directory.
   */
  function setHooksDirectory() {
    // Set the folder for the hooks. This contains a prepared file for the tests
    // to use.
    $directory = dirname(dirname(__FILE__)) . '/tests/sample_hook_definitions/' . $this->major_version;

    $this->hooks_directory = $directory;
  }

}

/**
 * Environment class for tests writing hook data to the Drupal's temp folder.
 */
class ModuleBuilderEnvironmentTestsTempLocation extends ModuleBuilderEnvironmentTests {

  /**
   * Set the hooks directory.
   */
  function setHooksDirectory() {
    // Set the folder for the hooks. This contains a prepared file for the tests
    // to use.
    // By some magic this appears to be safe to use with DrupalUnitTestCase.
    $directory = file_directory_temp() . '/module_builder_hook_definitions/' . $this->major_version;

    $this->hooks_directory = $directory;
  }

}

/**
 * @defgroup module_builder_environment_version_helpers Environment version helpers
 * @{
 * Wrapper objects for Drupal APIs that change between Drupal major versions.
 *
 * These allow the environment classes to work orthogonally across different
 * environments (Drush, Drupal UI) and different core versions.
 *
 * Each major version of Drupal core needs a version helper class. This is
 * instantiated by the environment object's initVersionHelper(). No direct calls
 * should be made to the helper, rather, the environment base class should
 * provide a wrapper.
 *
 * Version helper classes inherit in a cascade, with older versions inheriting
 * from newer. This means that if, say, an API function does not change between
 * Drupal 6 and 7, then its wrapper does not need to be present in the Drupal 6
 * helper class.
 */

/**
 * Environment helper for Drupal 8.
 */
class ModuleBuilderEnvironmentVersionHelper8 {

  private $major_version = 8;

  private $environment;

  /**
   * Constructor.
   *
   * @param $environment
   *  The environment object this is a helper for.
   */
  function __construct($environment) {
    $this->environment = $environment;
  }

  /**
   * Determine whether module_builder is installed as a module.
   */
  function installedAsModule() {
    return \Drupal::moduleHandler()->moduleExists('module_builder');
  }

  /**
   * Transforms a path into a path within the site files folder, if needed.
   *
   * Eg, turns 'foo' into 'public://foo'.
   * Absolute paths are unchanged.
   */
  function directoryPath(&$directory) {
    if (substr($directory, 0, 1) != '/') {
      // Relative, and so assumed to be in Drupal's files folder: prepend this to
      // the given directory.
      $directory = 'public://' . $directory;
    }
  }

  /**
   * Check that the directory exists and is writable, creating it if needed.
   *
   * @throws
   *  ModuleBuilderException
   */
  function prepareDirectory($directory) {
    $status = file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    if (!$status) {
      throw new ModuleBuilderException("The hooks directory cannot be created or is not writable.");
    }
  }

  /**
   * A version-independent wrapper for drupal_system_listing().
   *
   * Based on notes in change record at https://www.drupal.org/node/2198695.
   */
  function systemListing($mask, $directory, $key = 'name', $min_depth = 1) {
    $files = array();
    foreach (\Drupal::moduleHandler()->getModuleList() as $name => $module) {
      $files += file_scan_directory($module->getPath(), $mask, array('key' => $key));
    }
    return $files;
  }

  /**
   * Invoke hook_module_builder_info().
   */
  function invokeInfoHook() {
    $major_version = $this->environment->major_version;

    // TODO: just get ours if no bootstrap?
    $mask = '/\.module_builder.inc$/';
    $mb_files = $this->systemListing($mask, 'modules');

    $module_data = array();

    foreach ($mb_files as $file) {
      // Our system listing wrapper ensured that there is a uri property on all versions.
      include_once($file->uri);
      // Use a property of the (badly-documented!) $file object that is common to both D6 and D7.
      $module = str_replace('.module_builder', '', $file->name);
      // Note that bad data got back from the hook breaks things.
      if ($result = module_invoke($module, 'module_builder_info', $major_version)) {
        $module_data = array_merge($module_data, $result);
      }
    }

    //print_r($module_data);

    // If we are running as Drush command, we're not an installed module.
    if (!\Drupal::moduleHandler()->moduleExists('module_builder')) {
      include_once(dirname(__FILE__) . '/../module_builder.module_builder.inc');
      $result = module_builder_module_builder_info($major_version);
      $data = array_merge($module_data, $result);
    }
    else {
      $data = $module_data;
      // Yeah we switch names so the merging above isn't affected by an empty array.
      // Gah PHP. Am probably doin it wrong.
    }

    //drush_print_r($data);
    return $data;
  }

  /**
   * Get a user preference setting.
   *
   * On Drupal 8, I have no idea yet, so return the default.
   */
  public function getSetting($name, $default = NULL) {
    // TODO: fix this!
    return $default;
  }

}

/**
 * Environment helper for Drupal 7.
 */
class ModuleBuilderEnvironmentVersionHelper7 extends ModuleBuilderEnvironmentVersionHelper8 {

  /**
   * Determine whether module_builder is installed as a module.
   */
  function installedAsModule() {
    return module_exists('module_builder');
  }

  /**
   * A version-independent wrapper for drupal_system_listing().
   */
  function systemListing($mask, $directory, $key = 'name', $min_depth = 1) {
    return drupal_system_listing($mask, $directory, $key, $min_depth);
  }


  /**
   * Invoke hook_module_builder_info().
   */
  function invokeInfoHook() {
    $major_version = $this->environment->major_version;

    // TODO: just get ours if no bootstrap?
    $mask = '/\.module_builder.inc$/';
    $mb_files = drupal_system_listing($mask, 'modules');
    //print_r($mb_files);

    $module_data = array();

    foreach ($mb_files as $file) {
      // Our system listing wrapper ensured that there is a uri property on all versions.
      include_once($file->uri);
      // Use a property of the (badly-documented!) $file object that is common to both D6 and D7.
      $module = str_replace('.module_builder', '', $file->name);
      // Note that bad data got back from the hook breaks things.
      if ($result = module_invoke($module, 'module_builder_info', $major_version)) {
        $module_data = array_merge($module_data, $result);
      }
    }

    //print_r($module_data);

    // If we are running as Drush command, we're not necessarily an installed
    // module.
    if (!$this->installedAsModule()) {
      include_once(dirname(__FILE__) . '/../module_builder.module_builder.inc');
      $result = module_builder_module_builder_info($major_version);
      $data = array_merge($module_data, $result);
    }
    else {
      $data = $module_data;
      // Yeah we switch names so the merging above isn't affected by an empty array.
      // Gah PHP. Am probably doin it wrong.
    }

    //drush_print_r($data);
    return $data;
  }

  /**
   * Get a user preference setting.
   *
   * On Drupal 7 and below, this is a wrapper around variable_get().
   */
  public function getSetting($name, $default = NULL) {
    return variable_get($name, $default);
  }

}

/**
 * Environment helper for Drupal 6.
 */
class ModuleBuilderEnvironmentVersionHelper6 extends ModuleBuilderEnvironmentVersionHelper7 {

  /**
   * Transforms a path into a path within the site files folder, if needed.
   *
   * Eg, turns 'foo' into 'sites/default/foo'.
   * Absolute paths are unchanged.
   */
  function directoryPath(&$directory) {
    if (substr($directory, 0, 1) != '/') {
      // Relative, and so assumed to be in Drupal's files folder: prepend this to
      // the given directory.
      // sanity check. need to verify /files exists before we do anything. see http://drupal.org/node/367138
      $files = file_create_path();
      file_check_directory($files, FILE_CREATE_DIRECTORY);
      $directory = file_create_path($directory);
    }
  }

  /**
   * Check that the directory exists and is writable, creating it if needed.
   *
   * @throws
   *  ModuleBuilderException
   */
  function prepareDirectory($directory) {
    // Because we may have an absolute path whose base folders are not writable
    // we can't use the standard recursive D6 pattern.
    $pieces = explode('/', $directory);

    // Work up through the folder's parentage until we find a directory that exists.
    // (Or in other words, backwards in the array of pieces.)
    $length = count($pieces);
    for ($i = 0; $i < $length; $i++) {
      //print $pieces[$length - $i];
      $slice = array_slice($pieces, 0, $length - $i);
      $path_slice = implode('/', $slice);
      if (file_exists($path_slice)) {
        $status = file_check_directory($path_slice, FILE_CREATE_DIRECTORY);
        break;
      }
    }

    // If we go right the way along to the base and still can't create a directory...
    if ($i == $length) {
      throw new ModuleBuilderException("The directory $path_slice cannot be created or is not writable.");
    }
    // print "status: $status for $path_slice - i: $i\n";

    // Now work back down (or in other words, along the array of pieces).
    for ($j = $length - $i; $j < $length; $j++) {
      $slice[] = $pieces[$j];
      $path_slice = implode('/', $slice);
      //print "$path_slice\n";
      $status = file_check_directory($path_slice, FILE_CREATE_DIRECTORY);
    }

    if (!$status) {
      throw new ModuleBuilderException("The hooks directory cannot be created or is not writable.");
    }
  }

  /**
   * A version-independent wrapper for drupal_system_listing().
   */
  function systemListing($mask, $directory, $key = 'name', $min_depth = 1) {
    $files = drupal_system_listing($mask, $directory, $key, $min_depth);

    // This one is actually only for Drupal 6.
    // The file object is:
    //    D6         D7         what it actually is
    //  - filename | uri      | full path and name
    //  - basename | filename | name with the extension
    //  - name     | name     | name without the extension
    // So we copy filename to uri, and then the caller can handle the returned
    // array as if it were Drupal 7 style.
    foreach ($files as $file) {
      $file->uri = $file->filename;
    }

    return $files;
  }

  /**
   * Invoke hook_module_builder_info().
   *
   * @return
   *  Data gathered from the hook implementations.
   */
  public function invokeInfoHook() {
    $major_version = $this->environment->major_version;

    // TODO: just get ours if no bootstrap?
    $mb_files = $this->systemListing('/\.module_builder.inc$/', 'modules');
    //print_r($mb_files);

    $module_data = array();

    foreach ($mb_files as $file) {
      // Our system listing wrapper ensured that there is a uri property on all versions.
      include_once($file->uri);
      // Use a property of the (badly-documented!) $file object that is common to both D6 and D7.
      $module = str_replace('.module_builder', '', $file->name);
      // Note that bad data got back from the hook breaks things.
      if ($result = module_invoke($module, 'module_builder_info', $major_version)) {
        $module_data = array_merge($module_data, $result);
      }
    }

    //print_r($module_data);

    // If we are running as Drush command, we're not necessarily an installed
    // module.
    if (!$this->installedAsModule()) {
      include_once(dirname(__FILE__) . '/../module_builder.module_builder.inc');
      $result = module_builder_module_builder_info($major_version);
      $data = array_merge($module_data, $result);
    }
    else {
      $data = $module_data;
      // Yeah we switch names so the merging above isn't affected by an empty array.
      // Gah PHP. Am probably doin it wrong.
    }

    //drush_print_r($data);
    return $data;
  }

}

/**
 * Environment helper for Drupal 5.
 */
class ModuleBuilderEnvironmentVersionHelper5 extends ModuleBuilderEnvironmentVersionHelper6 {
  // D5 helper is the same as D6.
}

/**
* @} End of "defgroup module_builder_environment_version_helpers".
*/
