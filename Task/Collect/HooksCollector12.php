<?php

namespace DrupalCodeBuilder\Task\Collect;


/**
 * Task helper for collecting data on hooks on Drupal 12.
 */
class HooksCollector12 extends HooksCollector {

  /**
   * The names of api.php files to collect for testing sample data.
   */
  protected $testingApiFiles = [
    'block.api.php' => TRUE,
    // Need this for hook_install().
    'CORE_module.api.php' => TRUE,
    // Need this for hook_form_alter().
    'CORE_form.api.php' => TRUE,
    // Need this for hook_ENTITY_TYPE_view().
    'CORE_entity.api.php' => TRUE,
    // Need this for hook_tokens().
    'CORE_token.api.php' => TRUE,
    // Need this for hook_help().
    'help.api.php' => TRUE,
    // Need this for ThemeHook component.
    'CORE_theme.api.php' => TRUE,
];

  /**
   * {@inheritdoc}
   */
  public function getJobList() {
    $system_listing = \DrupalCodeBuilder\Factory::getEnvironment()->systemListing('/\.api\.php$/', 'modules', 'filename');

    // Convert the file objects into arrays.
    $api_files = [];
    // Keep the key for now so we can sort by it.
    foreach ($system_listing as $filename => $file) {
      $api_files[$filename] = (array) $file;

      $api_files[$filename]['process_label'] = 'hooks';
      $api_files[$filename]['item_label'] = $filename;
    }

    // Add in api.php files in core/lib.
    $core_directory = new \RecursiveDirectoryIterator('core/lib/Drupal');
    $iterator = new \RecursiveIteratorIterator($core_directory);
    // We need to make the regex grab everything from the start to get the
    // whole relative pathname in the result.
    $regex = new \RegexIterator($iterator, '/^.+\.api.php$/i', \RecursiveRegexIterator::GET_MATCH);
    $core_api_files = [];
    foreach ($regex as $regex_files) {
      foreach ($regex_files as $file) {
        $filename = basename($file);

        $component_name = explode('.', $filename)[0];
        $api_files['core:' . $filename] = [
          'uri' => $file,
          // Prefix the filename, to prevent file.api.php that's in core/lib
          // clobbering the one for file module (and any other such WTFs that
          // come up in future).
          'filename' => 'CORE_' . $filename,
          'name' => basename($file, '.php'),
          'group' => 'core:' . $component_name,
          'module' => 'core',
          'process_label' => 'hooks',
          'item_label' => $filename,
        ];
      }
    }

    // Add in core.api.php, which won't have been picked up because it's not
    // in a module!
    $api_files['core.api.php'] = [
      'uri' => 'core/core.api.php',
      'filename' => 'CORE_core.api.php',
      'name' => 'core.api',
      'group' => 'core:core',
      'module' => 'core',
      'process_label' => 'hooks',
      'item_label' => 'core.api.php',
    ];

    // Sort by the key, which is the filename for module files, and the group
    // for core files.
    ksort($api_files);

    // Strip the key out for the job list.
    return array_values($api_files);
  }

  /**
   * Gather hook documentation files.
   *
   * This retrieves a list of api hook documentation files from the current
   * Drupal install. On D8 these are files of the form MODULE.api.php and are
   * present in the codebase (rather than needing to be downloaded from an
   * online code repository viewer as is the case in previous versions of
   * Drupal).
   *
   * Because Drupal 8 puts api.php files in places other than module folders,
   * keys of the return array may be in one of these forms:
   *  - foo.api.php: The API file for foo module.
   *  - core:foo.api.php: The API file in a Drupal component.
   *  - core.api.php: The single core.api.php file.
   */
  protected function gatherHookDocumentationFiles($api_files) {
    // Get the hooks directory.
    $data_directory = \DrupalCodeBuilder\Factory::getEnvironment()->getDataDirectory();

    // Get Drupal root folder as a file path.
    // DRUPAL_ROOT is defined both by Drupal and Drush.
    // @see _drush_bootstrap_drupal_root(), index.php.
    $drupal_root = DRUPAL_ROOT;

    $hook_files = [];
    foreach ($api_files as $file) {
      $filename = $file['filename'];

      // Extract the module name from the path.
      // WARNING: this is not always going to be correct: will fail in the
      // case of submodules. So Commerce is a big problem here.
      // We could instead assume we have MODULE.api.php, but some modules
      // have multiple API files with suffixed names, eg Services.
      // @todo: make this more robust, somehow!
      if (!isset($file['module'])) {
        $matches = [];
        preg_match('@modules/(?:(?:contrib|custom)/)?(\w+)@', $file['uri'], $matches);
        //print_r($matches);
        $file['module'] = $matches[1];
        $file['group'] = $file['module'];
      }
      //dsm($matches, $module);

      // Mark core files.
      $core = str_starts_with($file['uri'], 'core');

      $hook_files[$filename] = [
        'original' => $drupal_root . '/' . $file['uri'], // no idea if useful
        'path' => $data_directory . '/' . $file['filename'],
        'destination' => '%module.module', // Default. We override this below.
        'group'       => $file['group'],
        'module'      => $file['module'],
        'core'        => $core,
      ];
    }

    // We now have the basics.
    // We should now see if some modules have extra information for us.
    $this->getHookDestinations($hook_files);

    // Filter for testing sample data collection.
    if (!empty($this->environment->sample_data_write)) {
      $hook_files = array_intersect_key($hook_files, $this->testingApiFiles);
    }

    return $hook_files;
  }

  /**
   * Do nothing on Drupal 12.
   *
   * @todo Refactor the caller so we don't need this empty method.
   */
  protected function getHookDestinations(&$hook_files) {
    return;
  }

  /**
   * Do nothing on Drupal 12.
   *
   * @todo Refactor the caller so we don't need this empty method.
   */
  protected function getDrupalHookInfo() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdditionalHookInfo() {
    // Keys should match the filename MODULE.api.php
    $info = [
      // Hooks on behalf of Drupal core.
      // api.php files that are in core rather than in a module have a prefix of
      // 'CORE_'.
      // TODO: clarify and document what this key represents. It's sort of the
      // api file basename, unless it's a file from core components rather than
      // a module.
      'CORE_database' => [
        'hook_destinations' => [
          '%module.install' => [
            'hook_schema',
            'hook_schema_alter',
          ],
        ],
      ],
      'CORE_module' => [
        'hook_destinations' => [
          '%module.install' => [
            'hook_requirements',
            'hook_install',
            'hook_update_N',
            'hook_update_last_removed',
            'hook_uninstall',
          ],
          '%module.post_update.php' => [
            'hook_post_update_NAME',
          ],
        ],
      ],
    ];
    return $info;
  }

}
