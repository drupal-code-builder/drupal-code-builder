<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Base task helper for collecting data on hooks.
 */
abstract class HooksCollector extends CollectorBase {

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'hooks';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'hook definitions';

  /**
   * Constructs a new helper.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment object.
   */
  public function __construct(
    EnvironmentInterface $environment
  ) {
    $this->environment = $environment;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataCount($data) {
    $count = 0;

    // Hook data is grouped rather than flat.
    foreach ($data as $group => $hooks) {
      $count += count($hooks);
    }

    return $count;
  }

  /**
   * Get definitions of hooks.
   *
   * @param $job_list
   *   The data returned from getJobList().
   *
   * @return array
   *   An array of data about hooks.
   */
  public function collect($job_list) {
    $hook_files = $this->gatherHookDocumentationFiles($job_list);

    // Copy the hook files to the hooks directory.
    // This is done after the files have been gathered, so when gathering
    // sample data for tests the filtered files only are written.
    foreach ($hook_files as $file_info) {
      copy($file_info['original'], $file_info['path']);
    }

    // Process the hook files into a single array for storage.
    $processed_hook_data = $this->processHookData($hook_files);

    return $processed_hook_data;
  }

  /**
   * {@inheritdoc}
   */
  public function mergeComponentData($existing_data, $new_data) {
    foreach ($new_data as $group => $group_data) {
      foreach ($group_data as $hook => $hook_data) {
        $existing_data[$group][$hook] = $hook_data;
      }
    }

    return $existing_data;
  }

  /**
   * Gather hook documentation files.
   *
   * This adds extra data to the list of files retrieved by getJobList().
   *
   * TODO: Rename this now it doesn't do the actual gathering!
   *
   * @param $system_listing
   *   The data on api.php files returned by getJobList().
   *
   * @return
   *  Array of data about hook files suitable for passing to processHookData().
   *  The array keys are the source filenames, and therefore must be unique. If
   *  there is a possibility of filename clash these must be rendered safe, for
   *  example by prefixing the module name.
   *  Each item has the following properties:
   *  - path: The full path to this file
   *  - url: (internal to this handler) URL to download this file from.
   *  - original: (probably not used; just here for interest) the full path this
   *    file was copied from.
   *  - destination: The module code file where the hooks from this hook data
   *    file should be saved by code generation. This may contain placeholders,
   *    for instance, '%module.views.inc'.
   *  - hook_destinations: Per-hook overrides to destination.
   *  - group: The group this file's hooks belong to. Usually this just the
   *    name of the source file with the 'api.php' suffix removed.
   *  - module: The module that provided this file. WARNING: this is not
   *    entirely reliable!
   * Example:
   * @code
   *  [system.core.php] => array(
   *    [path]        => /Users/you/data/drupal_hooks/7/system.api.php
   *    [url]         => (not used on 7)
   *    [original]    => /Users/joachim/Sites/7-drupal/modules/system/system.api.php
   *    [destination] => %module.module
   *    [group]       => core
   *    [module]      => node
   * @endcode
   */
  protected function gatherHookDocumentationFiles($system_listing) {
    // Needs to be overridden by subclasses.
  }

  /**
   * Builds complete hook data array from downloaded files and stores in a file.
   *
   * @param hook_file_data
   *  An array of data about the files to process:
   *   -[MODULE.FILENAME] => Array // eg system.core.php
   *     - [path] => full path to the file
   *     - [destination] => %module.module
   *     - [group] => GROUP  // eg core
   *     - [hook_destinations] => array(%module.foo => hook_foo, etc)
   *     - [hook_dependencies] => array()
   *  This is the same format as returned by update.inc.
   *
   * @return
   *  An array keyed by originating file of the following form:
   *     [GROUP] => array(  // grouping for UI.
           [hook_foo] => array(
             [name] => hook_foo
             [definition] => function hook_foo($node, $teaser = FALSE, $page = FALSE)
             [description] => Description.
             [destination] => Destination module file for hook code from this file.
             ... further properties:

             'type' => $hook_data_raw['type'][$key],
             'name' => $hook,
             'definition'  => $hook_data_raw['definitions'][$key],
             'description' => $hook_data_raw['descriptions'][$key],
             // TODO: Don't store this, just use it to figure out
             // callback dependencies!
             //'documentation' => $hook_data_raw['documentation'][$key],
             'destination' => $destination,
             'dependencies'  => $hook_dependencies,
             'group'       => $group,
             'file_path'   => $file_data['path'],
             'body'        => $hook_data_raw['bodies'][$key],

   */
  protected function processHookData($hook_file_data) {
    //print_r($hook_file_data);

    // check file_exists?

    /*
    // Developer trapdoor for generating the sample hook definitions file for
    // our tests. This limits the number of files to just a few from core.
    $intersect = array(
      'system.api.php',
      'node.api.php',
    );
    $hook_file_data = array_intersect_key($hook_file_data, array_fill_keys($intersect, TRUE));
    // End.
    */

    // Get hook_hook_info() from Drupal.
    $hook_info = $this->getDrupalHookInfo();

    // Build list of hooks
    $hook_groups = [];
    foreach ($hook_file_data as $file_data) {
      $hook_data_raw = $this->processHookFile($file_data['path']);

      $file_name = basename($file_data['path'], '.php');
      $group = $file_data['group'];

      // Create an array in the form of:
      // array(
      //   'filename' => array(
      //     array('hook' => 'hook_foo', 'description' => 'hook_foo description'),
      //     ...
      //   ),
      //   ...
      // );
      foreach ($hook_data_raw['names'] as $key => $hook) {
        // The destination is possibly specified per-hook; if not, then given
        // for the whole file.
        if (isset($file_data['hook_destinations'][$hook])) {
          $destination = $file_data['hook_destinations'][$hook];
        }
        else {
          $destination = $file_data['destination'];
        }

        // Also try to get destinations from hook_hook_info().
        // Argh why don't we have the short name here yet????
        // @todo: clean up!
        $short_name = substr($hook, 5);
        if (isset($hook_info[$short_name])) {
          //print_r($hook_info);
          $destination = '%module.' . $hook_info[$short_name]['group'] . '.inc';
        }

        // Process hook dependendies for the file.
        $hook_dependencies = [];
        if (isset($file_data['hook_dependencies'])) {
          // Incoming data is of the form:
          //  'required hook' => array('dependent hooks')
          // where the latter may be a regexp.
          foreach ($file_data['hook_dependencies'] as $required_hook => $dependency_data) {
            foreach ($dependency_data as $match) {
              if (preg_match("@$match@", $hook)) {
                $hook_dependencies[] = $required_hook;
              }
            }
          }
        }

        // See if the hook has any callbacks as dependencies. We assume that
        // mention of a string of the form 'callback_foo()' means it's needed
        // for the hook.
        // Ugly special case for install hooks which mention Batch API callbacks
        // in their docs but shouldn't have these as dependencies.
        // TODO: see if there's a way to label this in the resulting source
        // as associated with the hook that requested this.
        if ($file_data['group'] != 'core:module') {
          $matches = [];
          preg_match_all("@(callback_\w+)\(\)@", $hook_data_raw['documentation'][$key], $matches);
          if (!empty($matches[1])) {
            $hook_dependencies += $matches[1];
          }
        }

        // Because we're working through the raw data array, we keep the incoming
        // sort order.
        // But if there are multiple hook files for one module / group, then
        // they will go sequentially one after the other.
        // TODO: should this be improved, eg to group also by filename?
        $hook_groups[$group][$hook] = [
          'type' => $hook_data_raw['type'][$key],
          'name' => $hook,
          'definition'  => $hook_data_raw['definitions'][$key],
          'description' => $hook_data_raw['descriptions'][$key],
          // Don't store this!! TODO!! just use it for callback dependencies!!!
          //'documentation' => $hook_data_raw['documentation'][$key],
          'destination' => $destination,
          'dependencies'  => $hook_dependencies,
          'group'       => $group,
          'core'        => $file_data['core'] ?? NULL,
          'file_path'   => $file_data['path'],
          'body'        => $hook_data_raw['bodies'][$key],
        ];
        //dsm($hook_groups);
        //drush_print_r($hook_groups);

      } // foreach hook_data
    } // foreach files

    //dsm($hook_groups);
    //print_r($hook_groups);

    return $hook_groups;
  }

  /**
   * Extracts raw hook data from a downloaded hook documentation file.
   *
   * @param string $filepath
   *   Path to hook file.
   *
   * @return array
   *   Array of hook data, in different arrays, where each array is keyed
   *   numerically and all the indexes match up. The arrays are:
   *    - 'type': Whether this is a hook or a callback.
   *    - 'descriptions': Each hook's user-friendly description, taken from the
   *      first line of the PHPdoc.
   *    - 'documentation': The rest of the PHPdoc.
   *    - 'definitions: Each hook's entire function declaration: "function name($params)"
   *    - 'names': The long names of the hooks, i.e. 'hook_foo'.
   *    - 'bodies': The function bodies of each hook.
   */
  protected function processHookFile($filepath) {
    $contents = file_get_contents("$filepath");

    // Prepare a replacement set of the import statements, so we can replace
    // short class names will fully-qualified ones, so the sample code can
    // actually run.
    $imports_pattern = "@^use (.+);@m";
    $matches = [];
    preg_match_all($imports_pattern, $contents, $matches);
    $imports = $matches[1];

    $import_replacements = [];
    foreach ($imports as $import) {
      $pieces = explode('\\', $import);
      $short_class_name = array_pop($pieces);

      $import_replacements['@\b' . $short_class_name . '\b@'] = '\\' . $import;
    }

    // The pattern for extracting function data: capture first line of doc,
    // function declaration, and hook name.
    $pattern = '[
             / \* \* \n     # start phpdoc
            \  \* \  ( .* ) \n  # first line of phpdoc: capture the text for the description
      ( (?: \  \* .* \n )* )  # lines of phpdoc: capture the documentation
            \  \* /  \n     # end phpdoc
           ( function \ ( ( hook | callback ) \w+ ) .* ) \ { # function declaration: capture...
      #    ^ entire definition and name
      #                 ^ function name
      #                   ^ whether this is a hook or a callback
       ( (?: .* \n )*? )    # function body: capture example code
           ^ }
    ]mx';

    $matches = [];
    preg_match_all($pattern, $contents, $matches);

    // We don't care about the full matches.
    //array_shift($matches);

    $data = [
      'descriptions'  => $matches[1],
      'documentation' => $matches[2],
      'definitions'   => $matches[3],
      'names'         => $matches[4],
      'type'          => $matches[5],
      'bodies'        => $matches[6],
    ];

    // Replace all the short class names in body code with fully-qualified ones.
    foreach ($data['bodies'] as &$hook_body) {
      $hook_body = preg_replace(array_keys($import_replacements), array_values($import_replacements), $hook_body);
    }
    // Replace all the short class names in definitions with fully-qualified
    // ones. Some api.php files use fully-qualified classes in the function
    // declarations, and some rely on import statements, and there is no
    // documentation standard for it :(
    foreach ($data['definitions'] as &$hook_definition) {
      $hook_definition = preg_replace(array_keys($import_replacements), array_values($import_replacements), $hook_definition);
    }

    return $data;
  }

  /**
   * Get info about hooks from Drupal Core.
   *
   * This invokes hook_hook_info().
   *
   * @return
   *  The data from hook_hook_info().
   */
  protected function getDrupalHookInfo() {
    $hook_info = module_invoke_all('hook_info');
    return $hook_info;
  }

  /**
   * Get hook information declared by Module Builder.
   *
   * This invokes our own hook, hook_module_builder_info(), as well as adding
   * hardcoded info such as module file locations, which can't be deduced from
   * either api.php files or hook_hook_info().
   */
  protected function getHookInfo() {
    // Get data by invoking our hook.
    $data = \DrupalCodeBuilder\Factory::getEnvironment()->invokeInfoHook();

    // Add our data.
    $result = $this->getAdditionalHookInfo();
    $data = array_merge($data, $result);

    return $data;
  }

  /**
   * Declare our own info to add to data from our info hook.
   *
   * We're not necessarily running as a Drupal module, so we declare the data
   * we want to add here.
   */
  protected function getAdditionalHookInfo() {
    // Subclasses should override this.
    return [];
  }

}
