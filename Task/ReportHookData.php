<?php

/**
 * @file
 * Definition of ModuleBuider\Task\ReportHookData.
 */

namespace ModuleBuider\Task;

// We have no autoloading for Tasks.
include_once(dirname(__FILE__) . "/ReportHookDataFolder.php");

/**
 * Task handler for reporting on hook data.
 *
 * TODO: revisit some of these and clean up names / clean up how many we have.
 */
class ReportHookData extends ReportHookDataFolder {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'hook_data';

  /**
   * Get the list of hook data.
   *
   * (Replaces module_builder_get_hook_data().)
   *
   * @return
   *  The unserialized contents of the processed hook data file.
   */
  function listHookData() {
    // We may come here several times, so cache this.
    // TODO: look into finer-grained caching higher up.
    static $hook_data;

    if (isset($hook_data)) {
      return $hook_data;
    }

    $directory = $this->environment->hooks_directory;

    $hooks_file = "$directory/hooks_processed.php";
    if (file_exists($hooks_file)) {
      $hook_data = unserialize(file_get_contents($hooks_file));
      return $hook_data;
    }
    // Sanity checks ensure we never get here, but in case they have been
    // skipped, return something that makes sense to the caller.
    return array();
  }

  /**
   * Get just hook names.
   *
   * (Replaces module_builder_get_hook_names().)
   *
   * @param $style
   *   Whether to return hook names as just 'init' or 'hook_init'. One of:
   *    - 'short': Return short names, i.e., 'init'.
   *    - 'full': Return full hook names, i.e., 'hook_init'.
   *
   * @return
   *   A flat array of strings.
   */
  function listHookNames($style = 'full') {
    $data = $this->getHookDeclarations();
    $names = array_keys($data);

    if ($style == 'short') {
      foreach ($names as $key => $hook_name) {
        $names[$key] = str_replace('hook_', '', $hook_name);
      }
    }

    return $names;
  }

  /**
   * Get hooks as a list of nested options.
   *
   * @return
   *   An array of hooks as options suitable for FormAPI, where each key is a
   *   full hook name, and each value is a description.
   */
  function listHookNamesOptions($style = 'full') {
    $data = $this->getHookDeclarations();

    $return = array();
    foreach ($data as $hook_name => $hook_info) {
      $return[$hook_info['group']][$hook_name] = $hook_info['description'];
    }

    return $return;
  }

  /**
   * Get stored hook declarations, keyed by hook name, with destination.
   *
   * (Replaces module_builder_get_hook_declarations().)
   *
   * @return
   *  An array of hook information, keyed by the full name of the hook
   *  standardized to lower case.
   *  Each item has the keys:
   *  - 'type': One of 'hook' or 'callback'.
   *  - 'name': The full name of the hook in the original case,
   *    eg 'hook_form_FORM_ID_alter'.
   *  - 'definition': The full function declaration.
   *  - 'description': The first line of the hook docblock.
   *  - 'destination': The file this hook should be placed in, as a module file
   *    pattern such as '%module.module'.
   *  - 'dependencies': TODO!
   *  - 'group': Erm write this later.
   *  - 'file_path': The absolute path of the file this definition was taken
   *    from.
   *  - 'body': The hook function body, taken from the API file.
   */
  function getHookDeclarations() {
    $data = $this->listHookData();

    $return = array();
    foreach ($data as $group => $hooks) {
      foreach ($hooks as $key => $hook) {
        // Standardize to lowercase.
        $hook_name = strtolower($hook['name']);

        $return[$hook_name] = $hook;
      }
    }

    return $return;
  }

}
