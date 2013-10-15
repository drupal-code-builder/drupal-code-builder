<?php

/**
 * @file
 * Definition of ModuleBuider\Task\AnalyzeModule.
 */

namespace ModuleBuider\Task;

/**
 * Task handler for analyzing an existing module.
 */
class AnalyzeModule extends Base {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'hook_data';

  /**
   * Helper function to get all the code files for a given module
   *
   * TODO: does drush have this?
   *
   * (Replaces module_builder_get_module_files().)
   *
   * @param $module_root_name
   *  The root name of a module, eg 'node', 'taxonomy'.
   *
   * @return
   *  A flat array of filenames.
   */
  function getFiles($module_root_name) {
    $filepath = drupal_get_path('module', $module_root_name);

    //$old_dir = getcwd();
    //chdir($filepath);
    $files = scandir($filepath);

    foreach ($files as $filename) {
      $ext = substr(strrchr($filename, '.'), 1);
      if (in_array($ext, array('module', 'install', 'inc'))) {
        $module_files[] = $filepath . '/' . $filename;
      }
    }

    return $module_files;
  }

  /**
   * Helper function to get all function names from a file.
   *
   * (Replaces module_builder_get_functions().)
   *
   * @param $file
   *  A complete filename from the Drupal root, eg 'modules/user/user.module'.
   */
  function getFileFunctions($file) {
    $code = file_get_contents($file);
    //drush_print($code);

    $matches = array();
    $pattern = "/^function (\w+)/m";
    preg_match_all($pattern, $code, $matches);

    return $matches[1];
  }

  /**
   * Get the hooks that a module invents, i.e., the ones it should document.
   *
   * @param $module_root_name
   *  The module root name.
   *
   * @return
   *  An array of hooks and their parameters. The hooks are deduced from the
   *  calls to functions such as module_invoke_all(), and the probable
   *  parameters are taken from the variables passed to the call. The keys of
   *  the array are hook short names; the values are the parameters string,
   *  with separating commas but without the outer parentheses. E.g.:
   *    'foo_insert' => '$foo, $bar'
   *  These may not be complete if invocations omit any optional parameters.
   */
  function getInventedHooks($module_root_name) {
    // Get the module's folder.
    $module_folder = drupal_get_path('module', $module_root_name);

    // Bail if the folder doesn't exist yet: there is nothing to do.
    if (!file_exists($module_folder)) {
      return array();
    }

    // An array of short hook names that we'll populate from what we extract
    // from the files.
    $hooks = array();

    // Only consider hooks which are invented by this module: it is legitimate
    // for modules to invoke hooks invented by other modules. We assume the
    // module follows the convention of using its name as a prefix.
    $hook_prefix = $module_root_name . '_';

    foreach ($this->getFolderIterator($module_folder) as $filename => $object) {
      //print_r("$filename\n");
      $contents = file_get_contents("$filename");

      // List of patterns to match on.
      // They should all have capturing groups for:
      //  - 1. hook name
      //  - 2. optional parameters
      $hook_invocation_patterns = array(
        // module_invoke_all() calls (the key here is arbitrary).
        'module_invoke_all' => array(
          // The pattern for this item.
          'pattern' =>
            "/
            module_invoke_all \(
              ' ( $hook_prefix \w+ ) ' # Hook name, with the hook prefix.
              (?:
                , \s*
                (
                  [^)]* # Capture further parameters: anything up to the closing ')'.
                )
              )? # The further parameters are optional.
            /x",
        ),
        // module_invoke() calls.
        'module_invoke' => array(
          'pattern' =>
            "/
            module_invoke \(
              [^,]+ # The $module parameter.
              , \s*
              ' ( $hook_prefix \w+ ) ' # Hook name, with the hook prefix.
              (?:
                , \s*
                (
                  [^)]* # Capture further parameters: anything up to the closing ')'.
                )
              )? # The further parameters are optional.
            /x",
        ),
        // drupal_alter() calls.
        'drupal_alter' => array(
          'pattern' =>
            "/
            drupal_alter \(
              ' ( $hook_prefix \w+ ) ' # Hook name, with the hook prefix.
              (?:
                , \s*
                (
                  [^)]* # Capture further parameters: anything up to the closing ')'.
                )
              )? # The further parameters are optional.
            /x",
          // A process callback to apply to each hook name the pattern finds.
          // This is because the hook name in drupal_alter() needs a suffix to
          // be added to it.
          'process callback' => function ($hook_name) {
            return $hook_name . '_alter';
          },
        ),
      );

      // Process the file for each pattern.
      foreach ($hook_invocation_patterns as $pattern_info) {
        $pattern = $pattern_info['pattern'];

        $matches = array();
        preg_match_all($pattern, $contents, $matches);
        // Matches are:
        //  - 1: the first parameter, which is the hook short name.
        //  - 2: the remaining parameters, if any.

        // If we get matches, turn then into keyed arrays and merge them into
        // the cumulative array. This removes duplicates (caused by a hook being
        // invoked in different files).
        if (!empty($matches[1])) {
          //drush_print_r($matches);
          $file_hooks = array_combine($matches[1], $matches[2]);
          //drush_print_r($file_hooks);

          foreach ($file_hooks as $hook_short_name => $parameters) {
            // Perform additional processing on the hook short name, if needed.
            if (isset($pattern_info['process callback'])) {
              $hook_short_name = $pattern_info['process callback']($hook_short_name);
            }

            // If this hook is already in our list, we take the longest parameters
            // string, on the assumption that this may be more complete if some
            // parameters are options.
            if (isset($hooks[$hook_short_name])) {
              // Replace the existing hook if the new parameters are longer.
              if (strlen($parameters) > strlen($hooks[$hook_short_name])) {
                $hooks[$hook_short_name] = $parameters;
              }
            }
            else {
              $hooks[$hook_short_name] = $parameters;
            }
          }
        }
      }
    }
    //drush_print_r($hooks);

    return $hooks;
  }

  /**
   * Get an iterator for all (interesting) files in a component folder.
   *
   * @param
   *  The path to the folder.
   *
   * @return
   *  A RecursiveIteratorIterator for all the files in the component folder.
   *  Hidden files, and patch files and associated cruft are skipped.
   */
  protected function getFolderIterator($component_folder) {
    $iterator = new \RecursiveDirectoryIterator($component_folder);
    $filter = new ComponentFolderRecursiveFilterIterator($iterator);
    $all_files  = new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::SELF_FIRST);

    return $all_files;
  }

}

/**
 * Recursive filter iterator to skip unwanted files from folder iteration.
 *
 * @see http://stackoverflow.com/questions/18270629/php-recursive-directory-iterator-ignore-certain-files
 */
class ComponentFolderRecursiveFilterIterator extends \RecursiveFilterIterator {

  public function accept() {
    $current_filename = $this->current()->getFilename();
    $current_filename_extension = pathinfo($current_filename, PATHINFO_EXTENSION);

    // Filter out hidden files: don't want to be scanning .git folders!
    if (strpos($current_filename, '.') === (int) 0) {
      return FALSE;
    }

    // List of file extensions we should skip.
    $unwanted_extensions = array(
      // Module folders (well mine at least) frequently contain patch files and
      // other associated cruft which we want to skip.
      'patch',
      'orig',
      'rej',
      // Image files.
      'gif',
      'png',
      'jpg',
      'jpeg',
    );
    if (in_array($current_filename_extension, $unwanted_extensions)) {
      return FALSE;
    }

    // If you're still here then I guess you pass.
    return TRUE;
  }

}
