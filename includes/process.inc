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
 * @return
 *   An array of the form:
 *     [hook_action_info] => 
 *       'declaration' => function hook_action_info()
 *       'destination' => function hook_action_info()
 */
function module_builder_get_hook_declarations($dir = NULL) {
  $data = module_builder_get_hook_data($dir);
  
  foreach ($data as $group => $hooks) {
    foreach ($hooks as $key => $hook) {
      $return[$hook['name']] = array(
        'declaration' => $hook['definition'],
        'destination' => $hook['destination'],
      );
    }
  }
  
  return $return;
}

/**
 * Get just hook declarations, keyed by hook name.
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
 * Builds complete hook data array from downloaded files and stores in a file.
 *
 * @param hook_file_data
 *  An array of data about the files to process, keyed by (safe) filename:
    [MODULE.FILENAME] => Array // eg system.core.php
      [path] => full path to the file
      [destination] => %module.module
      [group] => GROUP  // eg core
      [hook_destinations] => array(%module.foo => hook_foo, etc)
 *  This is the same format as returned by update.inc.
 * @return
 *  An array keyed by originating file of the following form:
 *     [GROUP] => array(  // grouping for UI.
         [{i}] => array(
           [name] => hook_foo
           [definition] => function hook_foo($node, $teaser = FALSE, $page = FALSE)
           [description] => Description.
           [destination] => Destination module file for hook code from this file.
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
  
  // Build list of hooks
  $hook_groups = array();
  foreach ($hook_file_data as $file => $file_data) {    
    $hook_data_raw = _module_builder_process_hook_file($file_data['path']);
        
    $file_name = basename($file, '.php');
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
      
      $hook_groups[$group][$key] = array(
        'name' => $hook, 
        'definition'  => $hook_data_raw['definitions'][$key],
        'description' => $hook_data_raw['descriptions'][$key],
        'destination' => $destination,
      );
      //dsm($hook_groups);

    } // foreach hook_data
  } // foreach files
    
  //dsm($hook_groups);
  //print_r($hook_groups);
  
  // Write the processed data to a file.
  $directory = _module_builder_get_hooks_directory();    
  file_put_contents("$directory/hooks_processed.php", serialize($hook_groups));
  
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
 * Extracts raw hook data from downloaded hook documentation files.
 *
 * @param string $path
 *   Path to hook file
 * @param string $file
 *   Name of hook file
 * @return array
 *   Array of hook data:
 *    [0]: Each hook's user-friendly description
 *    [1]: Each hook's entire function declaration: "function name($params)"
 *    [2]: Name of each hook
 */
function _module_builder_process_hook_file($filepath) {

  $contents = file_get_contents("$filepath");
 
  // The pattern for extracting function data: capture first line of doc,
  // function declaration, and hook name.
  $pattern = '[
      / \* \* \n    # start phpdoc
      \ \* \ ( .* ) \n  # first line of phpdoc: capture the text
  (?: \ \* .* \n )* # lines of phpdoc
      \ \* /  \n    # end phpdoc
    ( function \ ( hook_\w* ) .* ) \  { # function declaration: capture both entire declaration and name
  ]mx';  

  preg_match_all($pattern, $contents, $matches);
  
  // We don't care about the full matches.
  //array_shift($matches);
  
  $data = array(
    'descriptions' => $matches[1],
    'definitions'  => $matches[2],
    'names'        => $matches[3], 
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
