<?php

/**
 * @file
 * Definition of ModuleBuider\Task\ReportHookDataFolder.
 */

namespace ModuleBuider\Task;

/**
 * Task handler for reporting on the folder for hook data.
 *
 * This is a lighter version of ReportHookData, used for admin status reports.
 *
 * It requires the hook folder to exist, *prefers* if there is hook data there,
 * but won't die if there isn't and will return sane empty values in that event.
 */
class ReportHookDataFolder extends Base {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'hook_directory';

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
        return array();
      }
    }
    else {
      drupal_set_message(t('Hook documentation path is invalid. Please return to the <a href="!settings">module builder settings</a> page to try again.', array('!settings' => url('admin/settings/module_builder'))), 'error');
      return array();
    }

    return $files;
  }

}
