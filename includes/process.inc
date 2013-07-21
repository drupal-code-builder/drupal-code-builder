<?php

/**
 * @file
 * Module builder code processing code.
 *
 * Turns downloaded data and shipped templates into data about hooks.
 */

/**
 * Get stored hook declarations, keyed by hook name, with destination.
 *
 * @param $dir
 *  The directory to look in for processed data.
 *
 * @return
 *  An array of hook information, keyed by the full name of the hook
 *  standardized to lower case.
 *  Each item has the keys:
 *  - 'name': The full name of the hook in the original case,
 *    eg 'hook_form_FORM_ID_alter'.
 *  - 'declaration': The full function declaration.
 *  - 'destination': The file this hook should be placed in, as a module file
 *    pattern such as '%module.module'.
 *  - 'group': Erm write this later.
 *  - 'file_path': The absolute path of the file this definition was taken from.
 *  - 'body': The hook function body, taken from the API file.
 */
function module_builder_get_hook_declarations($dir = NULL) {
  $data = module_builder_get_hook_data($dir);

  foreach ($data as $group => $hooks) {
    foreach ($hooks as $key => $hook) {
      $hook_name_lower_case = strtolower($hook['name']);
      $return[$hook_name_lower_case] = array(
        'name'        => $hook['name'],
        'declaration' => $hook['definition'],
        'destination' => $hook['destination'],
        'dependencies'  => $hook['dependencies'],
        'group'       => $group,
        'file_path'   => $hook['file_path'],
        'body'        => $hook['body'],
      );
    }
  }

  return $return;
}

/**
 * Get just hook declarations, keyed by hook name.
 *
 * @param $dir
 *  The directory to look in for processed data.
 *
 * @return
 *   An array of the form:
 *     [hook_action_info] => function hook_action_info()
 */
function module_builder_get_hook_declarations_plain($dir = NULL) {
  $data = module_builder_get_hook_data($dir);

  foreach ($data as $group => $hooks) {
    foreach ($hooks as $key => $hook) {
      $return[$hook['name']] = $hook['definition'];
    }
  }

  return $return;
}

/**
 * Get just hook names.
 *
 * @param $dir
 *  The directory to look in for processed data.
 * @param $short
 *   Whether to return hook names as just 'init' or 'hook_init'.
 *   Might as well call like this: module_builder_get_hook_names('short') for clarity.
 * @return
 *   A flat array of strings.
 */
function module_builder_get_hook_names($dir = NULL, $short = FALSE) {
  $data = module_builder_get_hook_data_flat($dir);
  $names = array_keys($data);

  if ($short) {
    foreach ($names as $key => $hook_name) {
      $names[$key] = str_replace('hook_', '', $hook_name);
    }
  }

  return $names;
}


/**
 * Helper for API functions that don't care about file grouping.
 *
 * @param $dir
 *  The directory to look in for processed data.
 *
 * @return
 *  An array of hook data, with hook names as keys and hook data as items.
 */
function module_builder_get_hook_data_flat($dir = NULL) {
  $data = module_builder_get_hook_data($dir);

  foreach ($data as $group => $hooks) {
    foreach ($hooks as $key => $hook) {
      $return[$hook['name']] = $hook;
    }
  }
  return $return;
}

/**
 * Retrieve hook data from storage file.
 *
 * @param $directory
 *  (optional) The directory to look in for processed data. If not given, the
 *  directory is obtained from settings.
 *
 * @return
 *  The unserialized contents of the processed hook data file.
 */
function module_builder_get_hook_data($directory = NULL) {
  if (!isset($directory)) {
    //$directory = file_create_path(variable_get('module_builder_hooks_directory', 'hooks'));
    $directory = _module_builder_get_hooks_directory();
  }

  $hooks_file = "$directory/hooks_processed.php";
  if (file_exists($hooks_file)) {
    return unserialize(file_get_contents($hooks_file));
  }
}

/**
 * Get the timestamp of the processed file.
 *
 * @param $directory
 *  The directory to look in for processed data.
 */
function module_builder_get_hook_data_last_updated($directory = NULL) {
  if (!isset($directory)) {
    $directory = _module_builder_get_hooks_directory();
  }
  $hooks_file = "$directory/hooks_processed.php";
  if (file_exists($hooks_file)) {
    $timestamp = filemtime($hooks_file);
    return format_date($timestamp, 'large');
  }
}

/**
 * Returns the path to a template file.
 *
 * TODO: this will eventually return either the user version or the module version.
 */
function module_builder_get_template($filename) {
  $pieces = array('templates', _module_builder_drupal_major_version(), $filename);
  $path = module_builder_get_path(implode('/', $pieces));

  $template_file = file_get_contents($path);

  return $template_file;
}

/**
 * Get the definition of hook presets.
 *
 * A preset is a collection of hooks with a machine name and a descriptive
 * label. Presets allow quick selection of hooks that are commonly used
 * together, eg those used to define a node type, or blocks.
 *
 * @return
 *  An array keyed by preset name, whose values are arrays of the form:
 *    'label': The label shown to the user.
 *    'hooks': A flat array of full hook names, eg 'hook_menu'.
 */
function module_builder_get_hook_presets() {
  // TODO: read user file preferentially.
  $presets_template = module_builder_get_template('hook_groups.template');
  $hook_presets = json_decode(preg_replace("@//.*@", '', $presets_template), TRUE);
  if (is_null($hook_presets)) {
    // @TODO: do something here to say its gone wrong. Throw Exception?
    drupal_set_message(t('Problem reading json file.'), 'error');
  }
  return $hook_presets;
}

/**
 * Builds complete hook data array from downloaded files and stores in a file.
 *
 * @param hook_file_data
 *  An array of data about the files to process, keyed by (safe) filename:
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
function module_builder_process_hook_data($hook_file_data) {
  /*
  // Get list of hook documentation files
  $files = module_builder_get_doc_files($directory);
  if (!isset($files)) {
    return NULL;
  }
  */

  //print_r($hook_file_data);

  // check file_exists?

  // Sort the files into a better order than just random.
  // TODO: allow for some control over this, eg frequently used core,
  // then rarer core, then contrib in the order defined by the MB hook.
  ksort($hook_file_data);

  // Build list of hooks
  $hook_groups = array();
  foreach ($hook_file_data as $file => $file_data) {
    $hook_data_raw = _module_builder_process_hook_file($file_data['path']);

    $file_name = basename($file, '.php');
    $group = $file_data['group'];

    // Should probably use module_hook_info(), but I don't use undocumented code FFS.
    // Note that the 'module' key is flaky: see module_builder_update_documentation()
    $module = $file_data['module'];
    $hook_info = array();
    if (module_hook($module, 'hook_info')) {
      $hook_info = module_invoke($module, 'hook_info');
    }

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
        print_r($hook_info);
        $destination = '%module.' . $hook_info[$short_name]['group'] . '.inc';
      }

      // Process hook dependendies for the file.
      $hook_dependencies = array();
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
      // TODO: see if there's a way to label this in the resulting source
      // as associated with the hook that requested this.
      $matches = array();
      preg_match_all("@(callback_\w+)\(\)@", $hook_data_raw['documentation'][$key], $matches);
      if (!empty($matches[1])) {
        $hook_dependencies += $matches[1];
      }

      // Because we're working through the raw data array, we keep the incoming
      // sort order.
      // But if there are multiple hook files for one module / group, then
      // they will go sequentially one after the other.
      // TODO: should this be improved, eg to group also by filename?
      $hook_groups[$group][$hook] = array(
        'type' => $hook_data_raw['type'][$key],
        'name' => $hook,
        'definition'  => $hook_data_raw['definitions'][$key],
        'description' => $hook_data_raw['descriptions'][$key],
        // Don't store this!! TODO!! just use it for callback dependencies!!!
        //'documentation' => $hook_data_raw['documentation'][$key],
        'destination' => $destination,
        'dependencies'  => $hook_dependencies,
        'group'       => $group,
        'file_path'   => $file_data['path'],
        'body'        => $hook_data_raw['bodies'][$key],
      );
      //dsm($hook_groups);

    } // foreach hook_data
  } // foreach files

  //dsm($hook_groups);
  //print_r($hook_groups);

  // Write the processed data to a file.
  $directory = _module_builder_get_hooks_directory();
  $serialized = serialize($hook_groups);
  file_put_contents("$directory/hooks_processed.php", $serialized);

  return $hook_groups;
}

/**
 * Retrieve list of documentation files containing hook definitions.
 *
 * @return array
 *   Array of files
 */
function module_builder_get_doc_files() {
  $dir = _module_builder_get_hooks_directory();

  if (!$dir) {
    drupal_set_message(t('Please configure the hook documentation path in <a href="!settings">module builder settings</a>.', array('!settings' => url('admin/settings/module_builder'))), 'error');
    return NULL;
  }

  $files = array();

  if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
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
function _module_builder_process_hook_file($filepath) {
  $contents = file_get_contents("$filepath");

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

  preg_match_all($pattern, $contents, $matches);

  // We don't care about the full matches.
  //array_shift($matches);

  $data = array(
    'descriptions'  => $matches[1],
    'documentation' => $matches[2],
    'definitions'   => $matches[3],
    'names'         => $matches[4],
    'type'          => $matches[5],
    'bodies'        => $matches[6],
  );

  return $data;
}

/**
 * Parse a module_builder template file.
 *
 * Template files are composed of several sections in the form of:
 *
 * == START [title of template section] ==
 * [the body of the template section]
 * == END ==
 *
 * @param string $file
 *   The template file to parse
 * @return Array
 *   Return array keyed by hook name, whose values are of the form:
 *    array('template' => TEMPLATE BODY)
 */
function module_builder_parse_template($file) {
  $data = array();

  // Captures a template name and body from a template file.
  $pattern = '#== START (.*?) ==(.*?)== END ==#ms';
   preg_match_all($pattern, $file, $matches);
  $count = count($matches[0]);
  for ($i = 0; $i < $count; $i++) {
    $data[$matches[1][$i]] = array(
      #'title' => $matches[1][$i],
      'template' => $matches[2][$i]
    );
    /*
    $hook_custom_declarations[] = array(
      'title' => $matches[1][$i],
      'data' => $matches[2][$i]
    );
    */
  }
  return $data;
}
