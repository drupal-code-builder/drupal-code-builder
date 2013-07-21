<?php

/**
 * @file
 *  Module builder drush commands.
 *
 * IMPORTANT: This file should be identical across versions of Drupal.
 */

/**
 * Implementation of hook_drush_init().
 *
 * Include common code and set our environment.
 */
function module_builder_drush_init() {
  $command_info = drush_get_command();

  if ($command_info['commandfile'] == 'module_builder') {
    module_builder_drush_init_helper();
  }
}

/**
 * Include our common files and set our environment.
 *
 * Helper for hook_drush_init() and completion callbacks.
 */
function module_builder_drush_init_helper() {
  include_once(dirname(__FILE__) . '/../includes/common.inc');
  // Set our environment.
  define('MODULE_BUILDER_ENV', 'drush');
}

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
    'callback' => 'drush_module_builder_callback_build',
    'description' => "Generate the code for a new Drupal module, including file headers and hook implementations.",
    'arguments' => array(
      'module name' => "The machine name of the module. Use '.' to specify the current folder name.",
      'hooks' => "Short names of hooks, separated by spaces. Hook preset groups can be specific with an initial '@', eg '@block'.",
    ),
    // Commented out, as only the first argument is required.
    // TODO: figure out how to specify this!
    //'required-arguments' => TRUE,
    'aliases' => array('mb'),
    'options' => array(
      'noi' => "Disables interactive mode.",
      'data' => "Location to read hook data. May be absolute, or relative to Drupal files dir. Defaults to 'files/hooks'.",
      'build' => "Which module components to generate:
  - 'info' makes the info file.
  - 'readme' makes README file.
  - 'api' makes an api.php file to document hooks. It will parse existing module files for calls to module_invoke_all() and use those as starter file content.
  - 'tests' makes the tests folder and test case file.
  - 'module', 'install' make the foo.module or foo.install file respectively.
  - 'FILE': If custom modules define other files to output, you can request those too, omitting the module root name part and any .inc extension, eg 'views' for 'foo.views.inc.
  - 'code' generates code files as needed: the module and install files, and any files requested by hooks.
  - 'all' generates everything, including any code files needed by the requested hooks.
Default is 'all' if writing new files, 'code' if appending to file or outputting only to terminal.",
      'write' => 'Write files to sites/all/modules. Will prompt to overwrite existing files; use yes to force. Use quiet to suppress output to the terminal.',
      'go' => 'Write all module files and enable the new module. Take two commands into the shower? Not me.',
      'add' => "Append hooks to module file. Implies 'write build=code'. Warning: will not check hooks already exist.",
      'name' => 'Readable name of the module.',
      'desc' => 'Description (for the admin module list).',
      'helptext' => 'Module help text (for the system help).',
      'dep' => 'Dependencies, separated by spaces, eg "forum views".',
      'package' => 'Module package.',
      'parent' => "Name of a module folder to place this new module into; use if this module is to be added to an existing package. Use '.' for the current working directory.",
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

  $items['mb-theme'] = array(
    'callback' => 'drush_module_builder_callback_build_theme',
    'aliases' => array('mbt'),
    'description' => "TODO.",
    'arguments' => array(
      'theme name' => "The machine name of the theme. Use '.' to specify the current folder name.",
      'themeables' => "Short names of templates or theme functions, separated by spaces.",
    ),
    'options' => array(
      'write' => 'Write files to sites/all/modules. Will prompt to overwrite existing files; use yes to force. Use quiet to suppress output to the terminal.',
      'parent' => "Name of a theme folder to place this new theme into; use if this theme is to be added to an existing package. Use '.' for the current working directory.",
    ),
  );

  $items['mb-download'] = array(
    'callback' => 'drush_module_builder_callback_hook_download',
    'description' => "Update module_builder hook data.",
    'options' => array(
      'data' => "Location to save downloaded files. May be absolute, or relative to Drupal files dir. Defaults to 'files/hooks'.",
    ),
    'aliases' => array('mbdl'),
    //'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['mb-list'] = array(
    'callback' => 'drush_module_builder_callback_hook_list',
    'description' => "List the hooks module_builder knows about.",
    'arguments' => array(
      'modules' => '(optional) Names of modules, separated by spaces.',
    ),
  );

  $items['mb-analyze'] = array(
    'callback' => 'drush_module_builder_callback_hook_analyze',
    'description' => "List the hooks found in a given module.",
    'aliases' => array('mban'),
    'options' => array(
      'flat' => "Shows a flat list of hooks rather than grouped by file.",
    ),
  );

  $items['mb-dochooks'] = array(
    'callback' => 'drush_module_builder_callback_doc_hooks',
    'description' => "Adds comment headers to hooks that need them in the given module.",
  );

  $items['mb-docparams'] = array(
    'callback' => 'drush_module_builder_callback_doc_params',
    'description' => "Adds params... WIP!",
  );

  $items['mb-debug'] = array(
    'callback' => 'drush_module_builder_callback_debug',
    'description' => "Debug module builder. Does whatever was needed at the time.",
    //'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['mb-dir'] = array(
    'callback' => 'drush_module_builder_callback_get_data_dir',
    'description' => "Print the location of the module builder data directory.",
  );

  // Retain backwards compatility with Drush 4 and whatever other version
  // numbers come before 5.
  if (version_compare(DRUSH_VERSION, '5.0-dev', '<')) {
    foreach ($items as $command => $item) {
      if (isset($item['options'])) {
        $items[$command]['options'] = array();
        foreach ($item['options'] as $option => $text) {
          $items[$command]['options']["--$option"] = $text;
        }
      }
    }
  }

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
function drush_module_builder_callback_build() {
  $commands = func_get_args();

  // Check settings before we start. This sort of wastes the potential of using
  // exceptions, but it's polite to warn the user of problems before they've
  // spent ages typing in all the hook names in interactive mode.
  try {
    _module_builder_sanity_check();
  }
  catch (ModuleBuilderException $e) {
    // If the problem is that the hooks need downloading, we can recover from this.
    if ($e->needs_hooks_download) {
      if (drush_confirm(dt('No hook definitions found. Would you like to download them now?'))) {
        // download the hooks so we can move on.
        if (!drush_module_builder_callback_hook_download()) {
          drush_set_error(DRUSH_APPLICATION_ERROR, 'Problem downloading hook data.');
        }
      }
    }
    // Otherwise, fail.
    else {
      drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
      return;
    }
  }

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

  // Set the build list in the module data.
  // TODO: move all the above to a helper function!
  $module_data['requested_build'] = array_fill_keys($build_list, TRUE);

  //print_r($build_list);

  // The 'bare code' option. This doesn't fully work yet, as 'add' doesn't
  // fully work yet! TODO!
  $bare_code = drush_get_option('add');
  $module_data['bare_code'] = $bare_code;

  // Build files.
  // Include generating component file.
  module_builder_include('generate');

  // For now, just assume it's a module we've been requested. We are nowhere
  // near ready to have other pathways into this.
  $files = module_builder_generate_component('module', $module_data);

  //drush_print_r($files);

  // TODO: check the files have code!
  /*
  if (is_null($module_code)) {
    return drush_set_error('DRUSH_NOT_COMPLETED', 'No module code has been generated: perhaps you have specified invalid hook names or hooks this module does not know about.');
  }
  */

  // This has been set in module_builder_build_data().
  $module_dir = $module_data['component_folder'];

  // Finally, output the files!
  foreach ($files as $filename => $code) {
    module_builder_drush_output_code($module_dir, $filename, $code);
  }

  if (drush_get_option('go')) {
    pm_module_manage(array(array_shift($commands)), TRUE);
  }
}

/**
 * Command argument complete callback: mb-build.
 *
 * Of form COMMANDFILE_COMMAND_complete().
 *
 * (Lousy Drush docs: COMMAND is module_builder_callback_build, apparently!)
 *
 * Debug this with, for example:
 *  $ drush --early=includes/complete.inc --complete-debug drush mb foo ini
 * and comment out cache in drush_complete_get().
 */
function module_builder_module_builder_callback_build_complete() {
  module_builder_drush_init_helper();

  // Include generating component file.
  module_builder_include('process');

  $data = module_builder_get_hook_data_flat();
  $complete = array();
  foreach ($data as $hook_name => $hook_data) {
    $complete[] = $hook_name;

    // Add the short name too.
    $complete[] = substr($hook_name, 5);

    // TODO: in anticipation of us doing callbacks too, don't just chop off the
    // front!
    //if (preg_match('/^hook_/'))
  }

  //drush_print_r($complete);
  return array(
    'values' => $complete,
  );
}

/**
 * Command callback for building a theme.
 */
function drush_module_builder_callback_build_theme() {
  $commands = func_get_args();

  //drush_print_r($commands);

  $theme_data = array();

  // Get the folder for the theme, and from that its machine name.
  $theme_data['theme_folder'] = module_builder_get_component_folder('theme', $commands[0]);
  $theme_data['theme_name'] = basename($theme_data['theme_folder']);

  array_shift($commands);
  $theme_data['themeables'] = $commands;

  // Build files.
  // Include generating component file.
  module_builder_include('generate');
  $files = module_builder_generate_component('theme', $theme_data);

  $theme_path = path_to_theme();

  foreach ($files as $filename => $code) {
    module_builder_drush_output_code($theme_path, $filename, $code);
  }
}

/**
 * Helper function to build the array of module_data.
 *
 * @see ModuleBuilderGeneratorModule::__construct()
 */
function module_builder_build_data($commands) {
  // Determine whether we're in interactive mode.
  $interactive = !drush_get_option(array('non-interactive', 'noi'));

  // Information about the keys we need to build the module data.
  // For each key, the source of the data is defined thus:
  //  - 'commands' means data can come from the initial commands. A numeric
  //    value gives the position; 'all' means all remaining commands after
  //    ones specified by position are taken.
  $data = array(
    'module_root_name' => array(
      'commands' => 0,
      'prompt' => dt('module name'),
      'required' => TRUE,
    ),
    'presets' => array(
      'prompt' => dt('required hook presets'),
      // This has neither 'commands' nor 'key' set, as these are pulled from the
      // hook list in non-interactive mode.
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
      'key' => 'helptext',
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

  // Get the folder for the module, and from that its machine name.
  $module_data['component_folder'] = module_builder_get_component_folder('module', $module_data['module_root_name']);
  $module_data['module_root_name'] = basename($module_data['component_folder']);
  drush_print_r('---- module folder: ' . $module_data['component_folder']);

  // Include generating component file.
  module_builder_include('process');
  // Get the flat list of hooks, standardized to lower case.
  $hook_definitions = array_change_key_case(module_builder_get_hook_data_flat());

  // Extra processing for the hooks array (or not).
  $hook_presets = module_builder_get_hook_presets();
  $hooks = array();
  if (!is_array($module_data['hooks'])) {
    $module_data['hooks'] = preg_split('/\s+/', $module_data['hooks']);
  }
  // Convert the array from numeric to keyed by full hook name.
  foreach ($module_data['hooks'] as $hook_name) {
    // Hook group presets can be specified with an initial @.
    if (substr($hook_name, 0, 1) == '@') {
      $preset_name = substr($hook_name, 1);
      // Convert the preset hooks list to the right format and add it to the array.
      $preset_hooks = array_fill_keys($hook_presets[$preset_name]['hooks'], TRUE);
      $hooks += $preset_hooks;
    }
    else {
      // Standardize to lowercase.
      $hook_name = strtolower($hook_name);

      // By default, accept the short definition of hooks, ie 'boot' for 'hook_boot'.
      if (isset($hook_definitions["hook_$hook_name"])) {
        $hooks["hook_$hook_name"] = TRUE;
      }
      // Also fall back to allowing full names. This is handy if you're copy-pasting
      // from an existing module and want the same hooks.
      // In theory there won't be any clashes; only hook_hook_info() is weird.
      elseif (isset($hook_definitions[$hook_name])) {
        $hooks[$hook_name] = TRUE;
      }
    }
  }
  // Special shortcut to get ALL THE HOOKS. BWAHAHA MINE ALL MINE.
  if (count($hooks) == 1 && isset($hooks['hook_all'])) {
    $hooks = $hook_definitions;
  }
  $module_data['hooks'] = $hooks;

  //print_r($module_data);
  return $module_data;
}

/**
 * Get the folder and machine name for a component.
 *
 * @param $component_type
 *  The type of the component. One of 'module' or 'theme'.
 * @param $component_name
 *  The input given for the component name. This allows several shorthands:
 *  - A single '.' specifies the current folder.
 *  - A trailing slash is ignored, thus allowing autocompletion of folder names.
 *  The processed component name should be extracted from the return value using
 *  basename().
 *
 * @return
 *  The full system path for the component's folder, without a trailing slash.
 */
function module_builder_get_component_folder($component_type, $component_name) {
  $drupal_root = drush_get_context('DRUSH_DRUPAL_ROOT');

  // Cleanup: trim a final '/' from the module machine name, to
  // allow use of tab autocompletion on the command line.
  if (substr($component_name, -1) == '/') {
    $component_name = substr($component_name, 0, -1);
  }

  // First try: if the component exists, we write there: nice and simple.
  $component_path = drupal_get_path($component_type, $component_name);
  if (!empty($component_path)) {
    return $drupal_root . '/' . $component_path;
  }

  // Second try: an input machine name given as '.' means use the current folder
  // as the module name, thus write to the current folder.
  if ($component_name == '.') {
    return drush_get_context('DRUSH_OLDCWD');
  }

  // Third try: 'parent' option was given.
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
      // If there's no dot, assume that an existing module is meant.
      // (Would anyone enter a complete path for this??? If we do need this,
      // then consider recursing into this for the parent path??)
      $module_dir .= drupal_get_path($component_type, $parent_dir) . '/';
    }
    return $module_dir . $component_name;
  }

  // Fourth and final try: build it based on the module folder structure.

  // Drush changes function names for version 5.
  // I wish its branch names and version numbers made some sort of sense.
  if (version_compare(DRUSH_VERSION, '5.0-dev', '>=')) {
    // There is probably a proper way to do this but it's Sunday morning and
    // I want this to just work and so brute force appeals.
    require_once DRUSH_BASE_PATH . '/commands/pm/download.pm.inc';
    $module_dir = _pm_download_destination($component_type);
  }
  else {
    $module_dir = pm_dl_destination($component_type);
  }

  // Gee great. Drush HEAD doesn't give us the trailing /.
  if (substr($module_dir, -1, 1) != '/') {
    $module_dir .= '/';
  }

  if ($component_type == 'module') {
    // Drush tries to put any module into 'contrib' if the folder exists;
    // hack this out and put the code in 'custom'.
    if (substr($module_dir, -8, 7) == 'contrib') {
      $module_dir_custom = substr_replace($module_dir, 'custom', -8, 7);
      if (is_dir($module_dir_custom)) {
        $module_dir = $module_dir_custom;
      }
    }
  }

  // $module_dir should now be a full path to the parent of the destination
  // folder, with a trailing slash.
  $module_dir .= $component_name;

  return $module_dir;
}

/**
 * Callback for generating default for module readable name.
 */
function module_builder_default_readable_name($commands, $module_data) {
  return ucfirst(str_replace('_', ' ', $module_data['module_root_name']));
}

/**
 * Output generated text, to terminal or to file.
 *
 * @param $component_dir
 *  The base folder for the component. May or may not exist.
 * @param $filename
 *  The filename to write, relative to the $component_dir.
 * @param code
 *  The text to write to the file.
 */
function module_builder_drush_output_code($component_dir, $filename, $code) {
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
    $filepath = $component_dir . '/' . $filename;

    // Because the filename part can contain subdirectories, check these exist
    // too.
    $subdir = dirname($filepath);
    if (!is_dir($subdir)) {
      $result = mkdir($subdir, 0777, TRUE);
      if ($result && !drush_get_option('quiet')) {
        drush_print("Module directory $component_dir created");
      }
    }

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
 * Callback for downloading hook data.
 */
function drush_module_builder_callback_hook_download() {
  // Check our data directory first.
  try {
    $hooks_directory = _module_builder_get_hooks_directory();
    module_builder_prepare_directory($hooks_directory);
  }
  catch (ModuleBuilderException $e) {
    drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
    return;
  }

  $return = module_builder_update_data();
  if (!$return) {
    return drush_set_error(DRUSH_APPLICATION_ERROR, 'Problem downloading hooks.');
  }
  else {
    drush_print("Hook files have been downloaded to $hooks_directory and processed.");
  }
}

/**
 * Callback to list known hooks.
 */
function drush_module_builder_callback_hook_list() {
  try {
    _module_builder_sanity_check();
  }
  catch (ModuleBuilderException $e) {
    drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
    return;
  }

  $commands = func_get_args();

  // Include generating component file.
  module_builder_include('process');

  $data = module_builder_get_hook_data();
  $time = module_builder_get_hook_data_last_updated();

  if (count($commands)) {
    // Put the requested filenames into the keys of an array, and intersect them
    // with the hook data.
    $files_requested = array_fill_keys($commands, TRUE);
    $data_requested = array_intersect_key($data, $files_requested);
  }
  else {
    $data_requested = $data;
  }

  drush_print_r($data);

  if (!count($data_requested) && count($files_requested)) {
    drush_print(t("No hooks found for the specified files."));
  }

  foreach ($data_requested as $file => $hooks) {
    drush_print("Group $file:", 2);
    foreach ($hooks as $key => $hook) {
      drush_print($hook['name'] . ': ' . $hook['description'], 4);
    }
  }

  // List presets.
  module_builder_include('process');
  $hook_presets = module_builder_get_hook_presets();
  foreach ($hook_presets as $hook_preset_name => $hook_preset_data) {
    drush_print("Preset $hook_preset_name: " . $hook_preset_data['label'], 2);
    foreach ($hook_preset_data['hooks'] as $hook) {
      drush_print($hook, 4);
    }
  }

  drush_print(t("Hook data retrieved from @dir.", array('@dir' => _module_builder_get_hooks_directory())));
  drush_print(t("Hook data was processed on @time.", array('@time' => $time)));

  //print_r($data);
}

/**
 * Callback to list hook implementations found in a given module.
 */
function drush_module_builder_callback_hook_analyze() {
  try {
    _module_builder_sanity_check();
  }
  catch (ModuleBuilderException $e) {
    drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
    return;
  }

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
  $module_hooks = $module_hooks_flat = array();

  foreach ($module_files as $file) {
    $functions = module_builder_get_functions($file);

    $file_hooks = array_intersect($functions, $hooks);

    $filename = basename($file);
    $module_hooks[$filename] = $file_hooks;
    foreach ($file_hooks as $hook) {
      $module_hooks_flat[$hook] = $filename;
    }
  }

  if (drush_get_option('flat')) {
    foreach ($module_hooks_flat as $hook => $filename) {
      drush_print("$hook implemented in $filename");
    }
  }
  else {
    foreach ($module_hooks as $filename => $hooks) {
      if (!count($hooks)) {
        continue;
      }
      drush_print("hooks found in $filename:");
      foreach ($hooks as $hook) {
        drush_print("  $hook");
      }
    }

  }

  //drush_print_r($module_hooks);
  //drush_print_r(array_merge($module_hooks));
}

/**
 * Callback to add doc headers to existing hooks.
 */
function drush_module_builder_callback_doc_hooks() {
  $commands = func_get_args();

  // The first argument is the module machine name.
  $module_root_name = array_shift($commands);

  $module_files = module_builder_get_module_files($module_root_name);

  // Include component files.
  module_builder_include('process');
  module_builder_include('generate');

  $hook_names = module_builder_get_hook_names(_module_builder_get_hooks_directory(), TRUE);

  $pattern = '[(?<! \* / \n )' . # no PHP doc: single quoted so \n works
    "function \ image_gallery _ ( \w * )  # function declaration: capture hook name
     ]mx";

  $filepath = realpath(drush_get_context('DRUSH_DRUPAL_ROOT'));
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
 * Callback to output the location of the data directory.
 */
function drush_module_builder_callback_get_data_dir() {
  drush_print('Module builder data is in ' . _module_builder_get_hooks_directory());
}

/**
 * WORK IN PROGRESS
 * Add function headers wherever needed with params.
 */
function drush_module_builder_callback_doc_params() {
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
function drush_module_builder_callback_debug() {

  /*
  include(dirname(__FILE__) . '/../includes/process.inc');
  include(dirname(__FILE__) . '/../includes/update.inc');

  module_builder_update_documentation();
  */


  return;
}
