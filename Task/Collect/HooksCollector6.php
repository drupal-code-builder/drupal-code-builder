<?php

namespace DrupalCodeBuilder\Task\Collect;


/**
 * Task helper for collecting data on hooks on Drupal 6.
 */
class HooksCollector6 extends HooksCollector {

  /**
   * {@inheritdoc}
   */
  public function getJobList() {
    // D6 and prior does not support batched analysis, mostly because I don't
    // have a working D6 site to test on!
    return NULL;
  }

  /**
   * Gather hook documentation files.
   *
   * This retrieves a list of api hook documentation files from drupal.org's
   * version control server.
   *
   * Because getJobList() returns NULL, $job_list is NULL and is ignored.
   */
  protected function gatherHookDocumentationFiles($job_list) {
    $directory = \DrupalCodeBuilder\Factory::getEnvironment()->getDataDirectory();

    // Fetch data about the files we need to download.
    $hook_files = $this->getHookFileUrls($directory);
    //print_r($hook_files);

    // Retrieve each file and store it in the hooks directory, overwriting what's currently there
    foreach ($hook_files as $file_name => $data) {
      $file_contents = drupal_http_request($data['url']);

      // TODO: replace with call to environment output.
      file_put_contents("$directory/$file_name", $destination . $file_contents->data);
    }

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
    // Get our data.
    $data = $this->getHookInfo();

    foreach ($data as $module => $module_data) {
      $branch = $module_data['branch'];
      foreach ($module_data['hook_files'] as $hook_file => $destination) {
        $url = str_replace(
          ['%file', '%branch'],
          [$hook_file, $branch],
          $module_data['url']
        );
        // Create our own safe filename with module prefix.
        $hook_file_safe_name = "$module.$hook_file";
        $urls[$hook_file_safe_name]['path'] = $directory . '/' . $hook_file_safe_name;
        $urls[$hook_file_safe_name]['url'] = $url;
        $urls[$hook_file_safe_name]['destination'] = $destination;
        if (isset($module_data['hook_destinations'])) {
          $urls[$hook_file_safe_name]['hook_destinations'] = [];
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

  /**
   * {@inheritdoc}
   */
  protected function getAdditionalHookInfo() {
    $info = [
      // Hooks on behalf of Drupal core.
      'system' => [
        'url' => 'http://drupalcode.org/project/documentation.git/blob_plain/refs/heads/%branch:/developer/hooks/%file',
        'branch' => '6.x-1.x',
        'group' => '#filenames',
        'hook_files' => [
          // List of files we should slurp from the url for hook defs.
          // and the destination file for processed code.
          'core.php' =>    '%module.module',
          'node.php' =>    '%module.module',
          'install.php' => '%module.install',
        ],
      ],
      // We need to do our own stuff now we have a hook!
      'module_builder' => [
        'url' => 'http://drupalcode.org/project/module_builder.git/blob_plain/refs/heads/%branch:/hooks/%file',
        'branch' => '6.x-2.x',
        'group' => 'module builder',
        'hook_files' => [
          'module_builder.php' => '%module.module_builder.inc',
        ],
      ],

      // Support for some contrib modules (the ones I use ;) -- for more please
      // file a patch either here or with the module in question.
      // Views
      'views' => [
        'url' => 'http://drupalcode.org/project/views.git/blob_plain/refs/heads/%branch:/docs/%file',
        'branch' => '6.x-2.x',
        'group' => 'views',
        'hook_files' => [
          'docs.php' => '%module.module',
          // other files here: view.inc, views.default.inc
        ],
        // hooks that go in files other than %module.module
        'hook_destinations' => [
          '%module.views.inc' => [
            'hook_views_data',
            'hook_views_data_alter',
            'hook_views_admin_links_alter',
            'hook_views_handlers',
            'hook_views_plugins',
            'hook_views_preview_info_alter',
            'hook_views_query_alter',
          ],
          '%module.views_convert.inc' => [
            'hook_views_convert',
          ],
          '%module.views_default.inc' => [
            'hook_views_default_views',
          ],
        ],
      ],
      // Ubercart
      'ubercart' => [
        'url' => 'http://drupalcode.org/project/ubercart.git/blob_plain/refs/heads/%branch:/docs/%file',
        'branch' => '6.x-2.x',
        'group' => 'ubercart',
        'hook_files' => [
          'hooks.php' => '%module.module',
        ],
      ],
      // Signup
      'signup' => [
        'url' => 'http://drupalcode.org/project/signup.git/blob_plain/refs/heads/%branch:/%file',
        'branch' => '6.x-2.x',
        'group' => 'signup',
        'hook_files' => [
          'signup.api.php' => '%module.module',
        ],
      ],
      // Ctools
      'ctools' => [
        'url' => 'http://drupalcode.org/project/ctools.git/blob_plain/refs/heads/%branch:/%file',
        'branch' => '6.x-1.x',
        'group' => 'ctools',
        'hook_files' => [
          'ctools.api.php' => '%module.module',
        ],
      ],
      // Webform
      'webform' => [
        'url' => 'http://drupalcode.org/project/webform.git/blob_plain/refs/heads/%branch:/%file',
        'branch' => '6.x-3.x',
        'group' => 'webform',
        'hook_files' => [
          'webform_hooks.php' => '%module.module',
        ],
      ],
      // Payment API
      'pay' => [
        'url' => 'http://drupalcode.org/project/pay.git/blob_plain/refs/heads/%branch:/%file',
        'branch' => '6.x-1.x',
        'group' => 'pay',
        'hook_files' => [
          'pay.api.php' => '%module.module',
        ],
      ],
    ];
    return $info;
  }

}
