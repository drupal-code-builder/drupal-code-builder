<?php

/**
 * @file
 * Definition of ModuleBuider\Task\ReportHookData.
 */

namespace ModuleBuider\Task;

/**
 * Task handler for reporting on hook data.
 *
 * Note that this task expects verifyHookData() to have been called on the
 * environment handler.
 */
class ReportHookData {

  /**
   * The sanity level this task requires to operate.
   *
   * We only need the hooks folder: we're fine to report that it's empty!
   */
  public $sanity_level = 'hook_directory';

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
   * Get the timestamp of the last hook data upate.
   *
   * @return
   *  A unix timestamp, or NULL if the hooks have never been collected.
   */
  public function lastUpdatedDate() {
    $directory = $this->environment->hooks_directory;
    $hooks_file = "$directory/hooks_processed.php";
    if (file_exists($hooks_file)) {
      $timestamp = filemtime($hooks_file);
      return format_date($timestamp, 'large');
    }
  }

  /**
   * Get a list of all collected hook api.php files.
   *
   * @return
   *  A flat array of filenames, relative to the hooks directory. If no files
   *  are present, an empty array is returned.
   */
  public function listHookFiles() {
    $directory = $this->environment->hooks_directory;

    $files = array();

    if (is_dir($directory)) {
      if ($dh = opendir($directory)) {
        while (($file = readdir($dh)) !== FALSE) {
          // Ignore files that don't make sense to include
          // TODO: replace all the .foo with one of the arcane PHP string checking functions
          if (!in_array($file, array('.', '..', '.DS_Store', 'CVS', 'hooks_processed.php'))) {
            $files[] = $file;
          }
        }
        closedir($dh);
      }
      else {
        drupal_set_message(t('There was an error opening the hook documentation path. Please try again.'), 'error');
        return NULL;
      }
    }
    else {
      drupal_set_message(t('Hook documentation path is invalid. Please return to the <a href="!settings">module builder settings</a> page to try again.', array('!settings' => url('admin/settings/module_builder'))), 'error');
      return NULL;
    }

    return $files;
  }

  /**
   * Get the list of hook data.
   *
   * (Replaces module_builder_get_hook_data().)
   *
   * @return
   *  The unserialized contents of the processed hook data file.
   */
  function listHookData() {
    $directory = $this->environment->hooks_directory;

    $hooks_file = "$directory/hooks_processed.php";
    if (file_exists($hooks_file)) {
      return unserialize(file_get_contents($hooks_file));
    }
    // Sanity checks ensure we never get here, but in case they have been
    // skipped, return something that makes sense to the caller.
    return array();
  }

  /**
   * Get a flat list of hook names.
   *
   * (Replaces module_builder_get_hook_data_flat().)
   *
   * @return
   *  An array of hook data, with hook names as keys and hook data as items.
   */
  function listHookNames() {
    $data = $this->listHookData();

    $return = array();
    foreach ($data as $group => $hooks) {
      foreach ($hooks as $key => $hook) {
        $return[$hook['name']] = $hook;
      }
    }
    return $return;
  }

}
