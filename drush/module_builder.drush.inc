<?php

/**
 * @file
 *  Module builder drush commands.
 *
 * IMPORTANT: This file should be identical across versions of Drupal.
 */
 
/**
 * Initialization.
 *
 * @todo: is there a hook to move this to?
 */ 
// Include common code.
include_once(dirname(__FILE__) . '/../includes/common.inc');
// Set our environment.
define('MODULE_BUILDER_ENV', 'drush');

/**
 * Implementation of hook_drush_command().
 *
 * In this hook, you specify which commands your
 * drush module makes available, what it does and 
 * description.
 *
 * Notice how this structure closely resembles how 
 * you define menu hooks.
 * 
 * @See drush_parse_command() for a list of recognized keys.
 *
 * @return
 *   An associative array describing your command(s).
 */
function module_builder_drush_command() {
  $items = array();

  // the key in the $items array is the name of the command.
  $items['mb-build'] = array(
    'callback' => 'module_builder_callback_build',
    'description' => "Generate the code for a new Drupal module, including file headers and hook implementations.",
    'arguments' => array(
      'module name' => 'The machine name of the module.',
      'hooks' => 'Short names of hooks, separated by spaces.',
    ),
    'aliases' => array('mb'),
    'options' => array(
      '--noi' => "Disables interactive mode.",
      '--data' => "Location to read hook data. May be absolute, or relative to Drupal files dir. Defaults to 'files/hooks'.",
      '--build' => "Which file type to generate: 'all', 'code', 'info', 'FILE'. " .
        "'all' generates everything: info and any code files needed by the requested hooks. " .
        "'code' generates code files as needed. " .
        "'info' makes just the info file. " .
        "'module', 'install' make just the foo.module or foo.install files. " .
        "'If custom modules define other files to output, you can request those too, omitting the module root name part and any .inc extension, eg 'module_builder' for 'foo.module_builder.inc. " .
        "Default is 'all' if writing new files, 'code' if appending to file or outputting only to terminal.",
      '--write' => 'Write files to sites/all/modules. Will prompt to overwrite existing files; use --yes to force. Use --quiet to suppress output to the terminal.',
      '--go' => 'Write all module files and enable the new module. Take two commands into the shower? Not me.',
      '--add' => "Append hooks to module file. Implies '--write --build=code'. Warning: will not check hooks already exist.",
      '--name' => 'Readable name of the module.',
      '--desc' => 'Description (for the admin module list).',
      '--help' => 'Module help text (for the system help).',
      '--dep' => 'Dependencies, separated by spaces, eg "forum views".',
      '--package' => 'Module package.',
      '--parent' => "Name of a module folder to place this new module into; use if this module is to be added to an existing package. Use '.' for the current working directory.",
    ),
    'examples' => array(
      'drush mb my_module menu cron nodeapi' => 
        'Generate module code with hook_menu, hook_cron, hook_nodeapi.',
      'drush mb my_module --build=info --name="My module" --dep="forum views"' => 
        'Generate module info with readable name and dependencies.',
      'drush mb my_module menu cron --write --name="My module" --dep="forum views"' => 
        'Generate both module files, write files and also output to terminal.',
      'drush mb my_module menu cron --write ' => 
        'Generate module code, write files and also output to terminal.',
      'drush mb my_module menu cron --write --quiet --name="My module" --dep="forum views"' => 
        'Generate both module files, write files and output nothing to terminal.',
      'drush mb my_module menu cron --add'=> 
        'Generate code for hook_cron and add it to the existing my_module.module file.',
      'drush mb my_module menu cron --write --parent=cck'=> 
        'Generate both module files, write files to a folder my_module inside the cck folder.',
      'drush mb my_module menu cron --write --parent=.'=> 
        'Generate both module files, write files to a folder my_module in the current working directory.',
    ),
  );
  
  $items['mb-download'] = array(
    'callback' => 'module_builder_callback_hook_download',
    'description' => "Update module_builder hook data.",
    'options' => array(
      '--data' => "Location to save downloaded files. May be absolute, or relative to Drupal files dir. Defaults to 'files/hooks'.",
    ),
    'aliases' => array('mbdl'),
    //'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.    
  );
  
  $items['mb-list'] = array(
    'callback' => 'module_builder_callback_hook_list',
    'description' => "List the hooks module_builder knows about.",
  );

  $items['mb-analyze'] = array(
    'callback' => 'module_builder_callback_hook_analyze',
    'description' => "List the hooks found in a given module.",
    'aliases' => array('mban'),
  );

  $items['mb-dochooks'] = array(
    'callback' => 'module_builder_callback_doc_hooks',
    'description' => "Adds comment headers to hooks that need them in the given module.",
  );

  $items['mb-docparams'] = array(
    'callback' => 'module_builder_callback_doc_params',
    'description' => "Adds params... WIP!",
  );
  
  $items['mb-debug'] = array(
    'callback' => 'module_builder_callback_debug',
    'description' => "Debug module builder. Does whatever was needed at the time.",
    //'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );
  
  return $items;
}

/**
 * Implementation of hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 *
 * @param
 *   A string with the help section (prepend with 'drush:')
 *
 * @return
 *   A string with the help text for your command.
 */
function module_builder_drush_help($section) {
  switch ($section) {
    case 'drush:mb-build':
      return dt('Generates and optionally writes module code with the specified hooks. ' .
        'By default this runs in interactive mode, and will prompt you for each ' .
        "of the module's properties. Use the --noi option to use as a single command.");
  }
}

/**
 * Module builder drush command callback.
 *
 * Form:
 * $drush mb machine_name hookA hookB hookC
 * where 'hookA' is the short name, ie 'menu' not hook_menu'.
 */
function module_builder_callback_build() {  
  $commands = func_get_args();
  
  // Build the module data.
  $module_data = module_builder_build_data($commands);
  
  // What to build
  $build = drush_get_option('build');
  
  // write options:
  // - all -- everything we can do
  // - code -- code files, not info (module + install _ ..?)
  // - info -- only info fole
  // - module -- only module file
  // - install -- only install file
  // - ??? whatever hooks need
    
  // No build: set nice default.
  if (!$build) {
    // If we are adding, 'code' is implied
    if (drush_get_option('add')) {
      $build = 'code';
    }
    // If we are writing or going, all.
    elseif (drush_get_option(array('write', 'go'))) {
      $build = 'all';
    }
    // Otherwise, outputting to terminal: only module
    else {
      $build = 'code';     
    }
  }
  
  //print_r($build);
  
  // Make a list  
  $build_list = explode(' ', $build);
  
  // Multi build: set a single string to switch on below.
  if (count($build_list) > 1) {
    $build = 'code';  
  }
  
  //print_r($build_list);  
  
  // Build files.
  // Include generating component file.
  module_builder_include('generate');
  // Build module code in all cases bar 'info'.
  if ($build != 'info') {
    // Check hook data file exists.
    if (!_module_builder_check_hook_data()) {
      return drush_set_error("DRUSH_NOT_COMPLETED', 'No hook definitions found. You need to download hook definitions before using this module: see the command 'mbdl'.");
    }
    
    module_builder_build_module($commands, $module_data, $build_list);        
  }
  // Build info code in cases 'info' and 'all'.
  if ($build == 'info' or $build == 'all') {
    module_builder_build_info($commands, $module_data);
  }
  
  /*
  switch ($build) {
    case 'info':
      // info and stop
      module_builder_callback_info($commands, $module_data);
      break;
    case 'all':
      // info and fall through
      module_builder_callback_info($commands, $module_data);
    case 'code':
      // this is just here to look pretty
    default:
      // anything else, eg module, install etc
      module_builder_callback_module($commands, $module_data, $build_list);    
  }
  */

  if (drush_get_option('go')) {
    pm_module_manage(array(array_shift($commands)), TRUE);
  }
}

/**
 * Helper function to build the array of module_data.
 */
function module_builder_build_data($commands) {
  // Determine whether we're in interactive mode.
  $interactive = !drush_get_option(array('non-interactive', 'noi'));

  // Information about the keys we need to build the module data.
  $data = array(
    'module_root_name' => array(
      'commands' => 0,
      'prompt' => dt('module name'),
      'required' => TRUE,
    ),
    // It is essential this key follow the root name, so that the the root
    // name gets to the commands array first.
    'hooks' => array(
      'commands' => 'all',
      'prompt' => dt('required hooks'),
      'required' => TRUE,
    ),
    'module_readable_name' => array(
      'key' => 'name',
      'prompt' => dt('human readable name'),
      'required' => TRUE,
      // A callback to generate the default value of this key.
      // The signature is foo($commands, $module_data)
      'default_callback' => 'module_builder_default_readable_name',
    ),
    'module_short_description' => array(
      'key' => 'desc',
      'prompt' => dt('description'),
      'default' => 'TODO: Description of module',
    ),
    'module_help_text' => array(
      'key' => 'help',
      'prompt' => dt('help text'),
    ),
    'module_dependencies' => array(
      'key' => 'dep',
      'prompt' => dt('dependencies'),
    ),
    'module_package' => array(
      'key' => 'package',
      'prompt' => dt('package'),
    ),
  );

  foreach ($data as $name => $definition) {
    // Merge in default values.
    $definition += array(
      'required' => FALSE,
    );
    // First pass: get data from either drush command line options...
    if (isset($definition['key'])) {
      $module_data[$name] = drush_get_option($definition['key']);
    }
    // ... or the commands themselves.
    elseif (isset($definition['commands'])) {
      // A numeric value of 'commands' means take that index from the commands array.
      if (is_numeric($definition['commands']) && isset($commands[$definition['commands']])) {
        $module_data[$name] = $commands[$definition['commands']];
        unset($commands[$definition['commands']]);
      }
      // Otherwise, take the whole thing.
      // This depends on the module root name having been taken out first!
      else {
        $module_data[$name] = $commands;
      }
    }

    // Second pass: prompt the user for data.
    if ($interactive && empty($module_data[$name])) {
      $value = drush_prompt(dt('Enter the @type', array('@type' => $definition['prompt'])), NULL, $definition['required']);
      if ($value !== FALSE) {
        $module_data[$name] = $value;
      }
    }
    
    // Third pass: set a default value from the definition or a callback.
    if (empty($module_data[$name])) {
      if (isset($definition['default'])) {
        $module_data[$name] = $definition['default'];
        continue;
      }
      elseif (isset($definition['default_callback'])) {
        $function = $definition['default_callback'];
        $module_data[$name] = $function($commands, $module_data);
      }
    }
  }
  
  // Extra processing for the hooks array (or not).
  if (!is_array($module_data['hooks'])) {
    $module_data['hooks'] = preg_split('/\s+/', $module_data['hooks']);
  }
  // Convert the array from numeric to keyed by full hook name.
  foreach ($module_data['hooks'] as $hook_name) {
    $hooks["hook_$hook_name"] = TRUE;
  }
  $module_data['hooks'] = $hooks;

  //print_r($module_data);
  return $module_data;
}

/**
 * Callback for generating default for module readable name. 
 */
function module_builder_default_readable_name($commands, $module_data) {
  return ucfirst(str_replace('_', ' ', $module_data['module_root_name']));
}

/**
 * Generates and outputs module code.
 *
 * @param $commands
 *    The drush array of commands.
 * @param $module_data
 *    An array of module data. Passed by reference so file information can
 *    be added by module_builder_generate_module().
 * @param $build_list
 *    An array of requested code files to output
 *    'code' or 'all' means all of them.
 */  
function module_builder_build_module($commands, &$module_data, $build_list) {
  //drush_print_r($module_data);
  //exit;
  /*
  $input = drush_input('input?...');
  drush_print("You entered: >$input<");
  */
   
  // Generate all our code.
  $module_code = module_builder_generate_module($module_data, drush_get_option('add'));
  
  if (is_null($module_code)) {
    return drush_set_error('DRUSH_NOT_COMPLETED', 'No module code has been generated: perhaps you have specified invalid hook names or hooks this module does not know about.');
  }

  //print_r($build_list);  
  if (!in_array($build_list[0], array('code', 'all'))) {
    // We have keys in module_code that are entire filenames, eg 'foo.install'
    // We have array items in build_list that are sort of file endings, eg 'install'
    // Match them up!
    $requested_files = module_builder_requested_filenames($module_data['module_root_name'], array_keys($module_code), $build_list);
  }
  else {
    // Meh we want the lot.
    $requested_files = array_keys($module_code);
  }
  //print_r($requested_files);  

  foreach ($requested_files as $filename) {
    $code = $module_code[$filename];
    module_builder_drush_output_code($module_data['module_root_name'], $filename, $code);   
    
  }
  return;
}

/**
 * Figure out which of $real filenames are being requested in the list of $abbrev'iated ones.
 *
 * @return
 *  A flat array of filenames from $real. Those whose abreviations were not found.
 *  in $abbrev are removed.
 */
function module_builder_requested_filenames($module_root_name, $real, $abbrev) {
  //print_r($real);
  
  foreach ($real as $r) {
    $p = preg_replace(
      array(
        "[^$module_root_name\.]", // module_name. at start
        '[\.inc$]'), // possibly .inc at end
      array('', ''),
      $r
    );
    $processed[$r] = $p;
    // build an array with the real names as keys
    // and the abbrevs as values 
  }
  //print_r($processed);
  //print_r($abbrev);
  
  // Intersecting thorws away values that are not in $abbrev
  // while keeping the real filename keys.
  $result = array_intersect($processed, $abbrev);
   
  //print_r($result); 
  // We only care about the keys anyway
  return array_keys($result);
}

/**
 * Generates and outputs info file code.
 */  
function module_builder_build_info($commands, $module_data) {
  module_builder_include('generate');
  $info_code = module_builder_generate_info_oo($module_data);
  
  /*
  module_builder_include('generate_info');
  $info_code = module_builder_generate_info($module_data);
  */

  module_builder_drush_output_code($module_data['module_root_name'], $module_data['module_root_name'] . '.info', $info_code); 
}

/**
 * Output generated text, to terminal or to file.
 */
function module_builder_drush_output_code($module_root_name, $filename, $code) {
    
  // Output to terminal
  if (!drush_get_option('quiet')) {
    drush_print("Proposed $filename:");
    drush_print_r($code);
  }
  
  $write = drush_get_option('write');
    
  // Write to file
  // Add to file option implies write.
  // Write & go option implies write.
  if (drush_get_option(array('write', 'add', 'go'))) {
    
    $module_dir = pm_dl_destination('module');
    
    // Gee great. Drush HEAD doesn't give us the trailing /.
    if (substr($module_dir, -1, 1) != '/') {
      $module_dir .= '/';
    }
    
    if (drush_get_option('parent')) {
      // The --parent option allows the user to specify a location for the new module folder.
      $parent_dir = drush_get_option('parent');
      if (substr($parent_dir, 0 , 1) == '.') {
        // An initial . means to start from the current directory rather than 
        // the modules folder, which allows submodules to be created where the
        // user is standing.
        $module_dir = drush_get_context('DRUSH_OLDCWD') . '/';
        // Remove both the . and the following /.
        $parent_dir = substr($parent_dir, 2);
        if ($parent_dir) {
          // If there's anything left (since just '.' is a valid option), append it.
          $module_dir .= $parent_dir . '/';
        }
      }
      else {
        $module_dir .= $parent_dir . '/';
      }
    }
    // $module_dir should now be a full path to the parent of the destination
    // folder, with a trailing slash.
    $module_dir .= $module_root_name;
    
    if (!is_dir($module_dir)) {
      @drush_op('mkdir', $module_dir, 0777);
      //drush_print("Module directory $module_dir created");
    }

    $filepath = $module_dir . '/' . $filename;
    
    // Add to file option
    // if file doesn't exist, we skip this and silently write it anyway
    if (drush_get_option('add') && file_exists($filepath)) {
      $fh = fopen($filepath, 'a');
      fwrite($fh, $code);
      fclose($fh);
      return;
    }

    // if file exists, ask for whether to overwrite
    if (file_exists($filepath)) {
      if (!drush_confirm(dt('File ' . $filename . ' exists. Do you really want to overwrite?'))) {
        return; 
      }
    }
    
    file_put_contents($filepath, $code);
  }
}

/**
 * Ask the user for input. DOESN'T WORK.
 *
 * @param $msg The question to ask
 * @return The entered string.
 */
function drush_input($msg, $required = FALSE, $indent = 0) {
  print str_repeat(' ', $indent) . (string)$msg . ": ";

  while ($line = trim(fgets(STDIN))) {
    if (!$required or strlen($line) > 0) {
      return $line;
    }
    print 'we never get here wtf?';
    print str_repeat(' ', $indent) . (string)$msg . ": ";
  }
}

/**
 * Callback for downloading hook data.
 */
function module_builder_callback_hook_download() {
  $directory = _module_builder_get_hooks_directory();  
  $return = module_builder_update_data();
  if (!$return) {
    return drush_set_error('Problem downloading hooks.');
  }
  else {
    drush_print("Hook files have been downloaded to $directory and processed.");
  }
}

/** 
 * Callback to list known hooks.
 */
function module_builder_callback_hook_list() {
  // Include generating component file.
  module_builder_include('process');
  
  $data = module_builder_get_hook_data();
  $time = module_builder_get_hook_data_last_updated();

  foreach ($data as $file => $hooks) {
    drush_print("Group $file:", 2);
    foreach ($hooks as $key => $hook) {
      drush_print($hook['name'] . ': ' . $hook['description'], 4);
    }
  }
  
  drush_print(t("Hook data retrieved from @dir.", array('@dir' => _module_builder_get_hooks_directory())));  
  drush_print(t("Hook data was processed on @time.", array('@time' => $time)));

  //print_r($data);
}

/**
 * Callback to list hook implementations found in a given module.
 */
function module_builder_callback_hook_analyze() {
  $commands = func_get_args();
  
  // The first argument is the module machine name.
  $module_root_name = array_shift($commands);  

  // Include process component file.
  module_builder_include('process');
  $hooks = module_builder_get_hook_names(_module_builder_get_hooks_directory(), TRUE);
  foreach ($hooks as $key => $hook) {
    $hooks[$key] = $module_root_name . '_' . $hook;
  }

  $module_files = module_builder_get_module_files($module_root_name);
  //drush_print_r($module_files);
  
  foreach ($module_files as $file) {
    $functions = module_builder_get_functions($file);
    
    $module_hooks[$file] = array_intersect($functions, $hooks);
  }
  
  if (drush_get_option('flat')) {

  }  
  
  drush_print_r($module_hooks);
  drush_print_r(array_merge($module_hooks));
}

/** 
 * Callback to add doc headers to existing hooks.
 */
function module_builder_callback_doc_hooks() {
  $commands = func_get_args();
  
  // The first argument is the module machine name.
  $module_root_name = array_shift($commands);  
  
  $module_files = module_builder_get_module_files($module_root_name);
  
  // Include component files.
  module_builder_include('process');
  module_builder_include('generate');
  
  $hook_names = module_builder_get_hook_names('short');

  $pattern = '[(?<! \* / \n )' . # no PHP doc: single quoted so \n works
    "function \ image_gallery _ ( \w * )  # function declaration: capture hook name
     ]mx";
    
  foreach ($module_files as $filename) {
    $code = file_get_contents($filepath . '/' . $filename);
    //print $code;
    
    // Get functions that have no docs.
    preg_match_all($pattern, $code, $function_names);
    
    // Get only those that are actual hooks.
    $bad_hooks = array_intersect($function_names[1], $hook_names);
    
    // For each hook that has no documentation.
    foreach ($bad_hooks as $hook_name) {
      $doc = module_builder_generate_hook_doxy("hook_$hook_name");
      $pattern2 = "[(?= function \ image_gallery _ $hook_name )]x";
      $code = preg_replace($pattern2, $doc, $code);
    }

    if (!drush_get_option('quiet')) {
      print $code;
    }
    
    print 'Added hook documentation headers for: ' . implode(', ', $bad_hooks) . "\n";
    if (!drush_confirm(dt('Are you sure you want to overwrite ' . $filename . '?'))) {
      continue; 
    }
    file_put_contents($filepath . '/' .$filename, $code);  
  }
}


/**
 * WORK IN PROGRESS
 * Add function headers wherever needed with params.
 */
function module_builder_callback_doc_params() {
  $commands = func_get_args();
  
  
  print 'wip!!!';
  return;
  
  // The first argument is the module machine name.
  $module_root_name = array_shift($commands);  
  
  $filepath = drupal_get_path('module', $module_root_name);

  //$old_dir = getcwd();
  //chdir($filepath);
  $files = scandir($filepath);

  foreach ($files as $filename) {
    $ext = substr(strrchr($filename, '.'), 1);
    if (in_array($ext, array('module', 'install', 'inc'))) {
      $module_files[] = $filename;
    }
  }
  
  // Include component files.
  module_builder_include('process');
  module_builder_include('generate');
  
  $hook_names = module_builder_get_hook_names('short');

  $pattern = '[
      / \* \* \n    # start phpdoc
      \ \* \ ( .* ) \n  # first line of phpdoc: capture the text
  (?: \ \* .* \n )* # lines of phpdoc
      \ \* /  \n    # end phpdoc
      function \ ( \w* ) \( ( .* ) \) \  { # function declaration: capture both entire declaration and name
  ]mx'; 
    
  foreach ($module_files as $filename) {
    $code = file_get_contents($filepath . '/' . $filename);
    //print $code;
    
    // Get functions that have no docs.
    preg_match_all($pattern, $code, $function_names);
    
    
    
    
    // Get only those that are actual hooks.
    //$bad_hooks = array_intersect($function_names[1], $hook_names);
    
    // For each hook that has no documentation.
    foreach ($bad_hooks as $hook_name) {
      $doc = module_builder_generate_hook_doxy("hook_$hook_name");
      $pattern2 = "[(?= function \ image_gallery _ $hook_name )]x";
      $code = preg_replace($pattern2, $doc, $code);
    }

    if (!drush_get_option('quiet')) {
     // print $code;
    }
    
    print 'Added hook documentation headers for: ' . implode(', ', $bad_hooks) . "\n";
    if (!drush_confirm(dt('Are you sure you want to overwrite ' . $filename . '?'))) {
      continue; 
    }
    //file_put_contents($filepath . '/' .$filename, $code);  
  }
}

/**
 * Just for testing stuff on the commandline while developing the module.
 */
function module_builder_callback_debug() {
  
  /*
  include(dirname(__FILE__) . '/../includes/process.inc');
  include(dirname(__FILE__) . '/../includes/update.inc');
  
  module_builder_update_documentation();
  */
  
    
  return;  
}
