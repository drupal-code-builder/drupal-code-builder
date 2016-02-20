<?php

/**
 * @file
 * Contains ModuleBuilder\Task\ReportHookDataFolder.
 */

namespace ModuleBuilder\Task;

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
  protected $sanity_level = 'data_directory_exists';

  /**
   * Get the timestamp of the last hook data upate.
   *
   * @return
   *  A unix timestamp, or NULL if the hooks have never been collected.
   */
  public function lastUpdatedDate() {
    $directory = $this->environment->getHooksDirectory();
    $hooks_file = "$directory/hooks_processed.php";
    if (file_exists($hooks_file)) {
      $timestamp = filemtime($hooks_file);
      return $timestamp;
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
    $directory = $this->environment->getHooksDirectory();

    $files = array();

    // No need to verify $directory; our sanity check has taken care of it.
    $dh = opendir($directory);
    while (($file = readdir($dh)) !== FALSE) {
      // Ignore files that don't make sense to include.
      // System files and cruft.
      // TODO: replace all the .foo with one of the arcane PHP string checking functions
      if (in_array($file, array('.', '..', '.DS_Store', 'CVS', 'hooks_processed.php'))) {
        continue;
      }
      // Our own processed files.
      if (strpos($file, '_processed.php')) {
        continue;
      }

      $files[] = $file;
    }
    closedir($dh);

    return $files;
  }

}
