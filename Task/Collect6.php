<?php

/**
 * @file
 * Definition of ModuleBuider\Task\Collect6.
 */

namespace ModuleBuider\Task;

/**
 * Task handler for collecting and processing hook definitions.
 */
class Collect6 extends Collect {

  /**
   * Gather hook documentation files.
   *
   * This retrieves a list of api hook documentation files from drupal.org's
   * version control server.
   */
  protected function gatherHookDocumentationFiles() {
    $mb_factory = module_builder_get_factory();
    $directory = $mb_factory->environment->hooks_directory;

    // Fetch data about the files we need to download.
    $hook_files = $this->getHookFileUrls($directory);
    //print_r($hook_files);

    // For testing only: skip downloading, just process.
    /*
    module_builder_process_hook_data($hook_files);
    return $hook_files;
    */

    // Retrieve each file and store it in the hooks directory, overwriting what's currently there
    foreach ($hook_files as $file_name => $data) {
      $file_contents = drupal_http_request($data['url']);

      // TODO: replace with call to environment output.
      //_module_builder_drush_print("writing $directory/$file_name", 2);
      file_put_contents("$directory/$file_name", $destination . $file_contents->data);
    }

    // inform that hook documentation has been downloaded.
    drupal_set_message(t("Module Builder has just downloaded hook documentation to your %dir directory from CVS. This documentation contains detailed descriptions and usage examples of each of Drupal's hooks. Please view the files for more information, or view them online at the <a href=\"!api\">Drupal API documentation</a> site.", array('%dir' => 'files/'. variable_get('module_builder_hooks_directory', 'hooks'), '!api' => url('http://api.drupal.org/'))));

    return $hook_files;
  }

  /**
   * Get list of hook file URLS from any modules that declare them.
   *
   * @param $directory
   *  The path to the module builder hooks directory.
   *
   * @return
   *   An array of data about the files to download, keyed by (safe) filename:
      [system.core.php] => Array
        [path] => the full path this file should be saved to
        [url] => URL
        [destination] => %module.module
        [group] => core
   */
  function getHookFileUrls($directory) {
    // Get data by invoking our hook.
    $mb_factory = module_builder_get_factory();
    $data = $mb_factory->environment->invokeInfoHook();

    foreach ($data as $module => $module_data) {
      $branch = $module_data['branch'];
      foreach ($module_data['hook_files'] as $hook_file => $destination) {
        $url = str_replace(
          array('%file', '%branch'),
          array($hook_file, $branch),
          $module_data['url']
        );
        // Create our own safe filename with module prefix.
        $hook_file_safe_name = "$module.$hook_file";
        $urls[$hook_file_safe_name]['path'] = $directory . '/' . $hook_file_safe_name;
        $urls[$hook_file_safe_name]['url'] = $url;
        $urls[$hook_file_safe_name]['destination'] = $destination;
        if (isset($module_data['hook_destinations'])) {
          $urls[$hook_file_safe_name]['hook_destinations'] = array();
          foreach ($module_data['hook_destinations'] as $destination => $hooks) {
            $urls[$hook_file_safe_name]['hook_destinations'] += array_fill_keys($hooks, $destination);
          }
        }
        if ($module_data['group'] == '#filenames') {
          $urls[$hook_file_safe_name]['group'] = str_replace('.php', '', $hook_file);
        }
        else {
          $urls[$hook_file_safe_name]['group'] = $module_data['group'];
        }
      }
    }

    //print_r($urls);

    return $urls;
  }

}
