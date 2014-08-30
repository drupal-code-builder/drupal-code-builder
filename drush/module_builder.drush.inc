<?php

/**
 * @file
 *  Module builder drush commands.
 */

/**
 * Implementation of hook_drush_init().
 *
 * Include common code.
 */
function module_builder_drush_init() {
  $command_info = drush_get_command();

  if ($command_info['commandfile'] == 'module_builder') {
    module_builder_drush_init_helper();
  }
}

/**
 * Include our common files.
 *
 * Helper for hook_drush_init() and completion callbacks.
 */
function module_builder_drush_init_helper() {
  // Load the file containing our factory class.
  include_once(dirname(__FILE__) . '/../ModuleBuilderFactory.php');

  include_once(dirname(__FILE__) . '/../includes/common.inc');
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
    'callback' => 'drush_module_builder_callback_build_module',
    'description' => "Generate the code for a new Drupal module, including file headers and hook implementations.",
    'arguments' => array(
      'module name' => "The machine name of the module. Use '.' to specify the current folder name.",
      'hooks' => "Short names of hooks, separated by spaces.",
      'presets:' => "Names of preset groups of hooks, e.g., 'block'." . "\n" .
        "Use the 'presets:' marker to separate this from prior commands, eg 'mymodule hook_a hook_b presets: block'.",
      'routes:' => "Menu paths, separated by spaces. " . "\n" .
        "Use the 'routes:' marker to separate this from prior commands, eg 'mymodule hook_a hook_b routes: module/path module/otherpath'.",
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

  $items['mb-component'] = array(
    'callback' => 'drush_module_builder_callback_build_component',
    'aliases' => array('mbc'),
    'description' => "Generate a Drupal component, such as a module or profile.",
    'arguments' => array(
      'component type' => "The type of component, e.g., 'module'.",
    ),
    'options' => array(
      'write' => 'Write the component to the current site codebase. Will prompt to overwrite existing files; use yes to force. Use quiet to suppress output to the terminal.',
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
    'options' => array(
      'raw' => "Outputs the raw debug hook data.",
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
function drush_module_builder_callback_build_module() {
  $commands = func_get_args();

  // Check settings before we start. This sort of wastes the potential of using
  // exceptions, but it's polite to warn the user of problems before they've
  // spent ages typing in all the hook names in interactive mode.
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');

  // Get our task handler. This performs a sanity check on the environment which
  // throws an exception.
  try {
    $mb_task_handler_generate = $mb_factory->getTask('Generate', 'module');
  }
  catch (ModuleBuilderException $e) {
    // If the problem is that the hooks need downloading, we can recover from this.
    if ($e->needs_hooks_download) {
      if (drush_confirm(dt('No hook definitions found. Would you like to download them now?'))) {
        // Download the hooks so we can move on.
        $success = drush_module_builder_callback_hook_download();
        if (!$success) {
          drush_set_error(DRUSH_APPLICATION_ERROR, 'Problem downloading hook data.');
          return;
        }

        // Get the task handler that we were trying to get in the first place.
        $mb_task_handler_generate = $mb_factory->getTask('Generate', 'module');
      }
    }
    // Otherwise, fail.
    else {
      drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
      return;
    }
  }

  // Extra drush-specific component data info for the module component.
  // This defines command prefixes and drush options.
  // This gets merged with the component data info returned from the Module
  // generator.
  $component_info_drush_extra = array(
    'module_root_name' => array(
      'drush_value_process' => 'module_builder_drush_component_root_name_process',
    ),
    'hooks' => array(
      'command_prefix' => 'hooks',
      // The list is huge, so bypass showing the options.
      'drush_no_option_list' => TRUE,
    ),
    'module_hook_presets' => array(
      'command_prefix' => 'presets',
    ),
    'module_readable_name' => array(
      'drush_option' => 'name',
    ),
    'module_short_description' => array(
      'drush_option' => 'desc',
    ),
    'module_help_text' => array(
      'drush_option' => 'helptext',
    ),
    'module_dependencies' => array(
      'drush_option' => 'dep',
    ),
    'module_package' => array(
      'drush_option' => 'package',
    ),
    'router_items' => array(
      'command_prefix' => 'routes',
    ),
  );

  $component_data_extra = array();

  // Extra component data for the build list and the bare code options.
  // What to build.
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

  // Make a list out of the build option string. This may of course have only
  // one item in it.
  $build_list = explode(' ', $build);

  // Multi build: set a single string to switch on below.
  if (count($build_list) > 1) {
    $build = 'code';
  }

  // Set the build list in the module data.
  // TODO: move all the above to a helper function!
  $component_data_extra['requested_build'] = array_fill_keys($build_list, TRUE);

  //print_r($build_list);

  // The 'bare code' option. This doesn't fully work yet, as 'add' doesn't
  // fully work yet! TODO!
  $bare_code = drush_get_option('add');
  $component_data_extra['bare_code'] = $bare_code;

  // Insert the 'hooks' prefix into the commands array, after the module name.
  // This privileges the hooks: they don't need a prefix and all commands up to
  // another prefix are taken to be hooks.
  array_splice($commands, 1, 0, 'hooks:');

  // Call the main function to do the work of gathering data from the user and
  // building the files and optionally writing them.
  drush_module_builder_build_component($commands, 'module', $component_info_drush_extra, $component_data_extra);

  // Enable the module if requested.
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
  // We're too early in the drush bootstrap for this to be called, apparently.
  // So do it ourselves.
  module_builder_drush_init_helper();

  // Get a MB factory.
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');

  // Add our environment handler, and check hook data is ready.
  try {
    // Drupal bootstrap isn't ready enough to check environment, because the
    // file API isn't loaded. This isn't bad, because here it's us, the caller,
    // that knows we can't pass the check.
    $mb_factory->environment->skipSanity = TRUE;

    $mb_task_handler_report = $mb_factory->getTask('ReportHookData');
  }
  catch (ModuleBuilderException $e) {
    // Just do nothing if we're not ready: the actual command will deal with
    // error output to the user.
    return;
  }

  $data = $mb_task_handler_report->getHookDeclarations();
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

  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');

  // Create our environment handler, set it, then get the task.
  try {
    $mb_task_handler_generate = $mb_factory->getTask('Generate', 'theme');
  }
  catch (ModuleBuilderException $e) {
    // Doubtful we get here.
    return;
  }

  // Get the folder for the theme, and from that its machine name.
  $theme_data['theme_folder'] = module_builder_get_component_folder('theme', $commands[0]);
  $theme_data['theme_name'] = basename($theme_data['theme_folder']);

  array_shift($commands);
  $theme_data['themeables'] = $commands;

  // Build files.
  // Include generating component file.
  $files = $mb_task_handler_generate->generateComponent($theme_data);

  drush_print_r($theme_data);
  return;


  $theme_path = path_to_theme();

  foreach ($files as $filename => $code) {
    module_builder_drush_output_code($theme_path, $filename, $code);
  }
}

/**
 * Build a generic requested component.
 *
 * @param $component_type
 *  The type of the component, e.g. 'module'.
 */
function drush_module_builder_callback_build_component($component_type = NULL) {
  $commands = func_get_args();
  // Shift off the component name.
  array_shift($commands);

  // Determine whether we're in interactive mode.
  $interactive = !drush_get_option(array('non-interactive', 'noi'));

  // This is a shortcut for developing, if you have --noi forced in your drush
  // config and need to switch back to interactive for testing.
  if (drush_get_option(array('interactive'))) {
    $interactive = TRUE;
  }

  //drush_print_r($commands);

  if (empty($component_type)) {
    if ($interactive) {
      $component_type = drush_prompt("Enter a component name", 'module', TRUE);
    }
    else {
      throw new Exception("No component name given.");
    }
  }

  drush_module_builder_build_component($commands, $component_type);
}

/**
 * Workhorse for various Drush command callbacks.
 *
 * @param $commands
 *  The commands array; the original parameters from the Drush command callback.
 * @param $component_type
 *  The component type, e.g., 'module'.
 * @param $component_info_drush_extra = array()
 *  An array to merge into the component data info array. This may may contains
 *  additional information specific to drush. Possible keys for each property
 *  info array are:
 *    - 'command_prefix': A string that when suffixed with ':' acts as a marker
 *      for this property in the commands array. All commands after this marker
 *      taken as data for this property, until another marker is found or until
 *      the end of the commands array. For example, the following list of
 *      commands contains hooks and hook presets:
 *      'hooks: menu init presets: node field-widget'
 *    - 'drush_option': A string giving the drush option to take this property's
 *      value from.
 * @param $component_data = array()
 *  Initial component data, into which data obtained from the user will be
 *  added. Command callbacks may wish to gather extra data, or add defaults.
 */
function drush_module_builder_build_component($commands, $component_type, $component_info_drush_extra = array(), $component_data = array()) {
  // Get the module builder factory, set up for our environment.
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');

  // Get the Generator task, specifying the component we want so we get a
  // sanity check based on that and our environment.
  try {
    $mb_task_handler_generate = $mb_factory->getTask('Generate', $component_type);
  }
  catch (ModuleBuilderException $e) {
    drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
    return;
  }

  $root_generator = $mb_task_handler_generate->getRootGenerator();

  // Get the component data info, and add in extra info specific to Drush.
  $component_data_info = $root_generator->getComponentDataInfo();
  $component_data_info = array_merge_recursive($component_data_info, $component_info_drush_extra);
  //drush_print_r($component_data_info);

  // Split the commands array into:
  //  - plain commands
  //  - a nested array of prefixed commands
  //  - an array of boolean flags.
  // E.g. given 'foo bar hooks: biz presets: bax', we want:
  //  - $commands as just 'foo bar'
  //  - hooks as 'biz'
  //  - presets as 'bax'
  $plain_commands     = array();
  $prefixed_commands  = array();
  $boolean_commands   = array();
  foreach ($commands as $command) {
    if (substr($command, -1) == ':') {
      // This is a preset marker.
      $prefix_marker = substr($command, 0, -1);

      // Set the array up and move on.
      $prefixed_commands[$prefix_marker] = array();
      continue;
    }

    if (substr($command, -1) == '!') {
      // This is a boolean flag.
      $boolean_flag = substr($command, 0, -1);

      $boolean_commands[$boolean_flag] = TRUE;
      continue;
    }

    if (isset($prefix_marker)) {
      // Continue taking commands for this prefix until we find another prefix
      // marker or run out of commands.
      $prefixed_commands[$prefix_marker][] = $command;
    }
    else {
      $plain_commands[] = $command;
    }
  }
  //drush_print_r($prefixed_commands);

  // Determine whether we're in interactive mode.
  $interactive = !drush_get_option(array('non-interactive', 'noi'));

  // This is a shortcut for developing, if you have --noi forced in your drush
  // config and need to switch back to interactive for testing.
  if (drush_get_option(array('interactive'))) {
    $interactive = TRUE;
  }

  // Build the component data array from the given commands.
  // Work through the component data info, assembling the component data array
  // Each property info needs to be prepared, so iterate by reference.
  foreach ($component_data_info as $property_name => &$property_info) {
    // Prepare the single property: get options, default value, etc.
    $root_generator->prepareComponentDataProperty($property_name, $property_info, $component_data);

    // Initialize our value as empty. We try various things to set it.
    $value = NULL;

    // If the property is required, and there are plain command parameters
    // remaining, take one of those.
    if ($property_info['required'] && $plain_commands) {
      $value = array_shift($plain_commands);
      //drush_print_r("taking $property_name from commands as $value");
    }

    // If the property has a prefix, and the commands included that prefix,
    // then take the commands for that prefix.
    if (empty($value) && isset($property_info['command_prefix']) && !empty($prefixed_commands[$property_info['command_prefix']])) {
      $value = $prefixed_commands[$property_info['command_prefix']];
      //drush_print_r("taking $property_name from prefixed commands");
    }

    // If the property can be set with a command-line option, check that.
    if (empty($value) && isset($property_info['drush_option'])) {
      $drush_option_value = drush_get_option($property_info['drush_option']);
      if (!empty($drush_option_value)) {
        $value = $drush_option_value;
      }
    }

    // Boolean commands.
    if ($property_info['format'] == 'boolean') {
      if (isset($boolean_commands[$property_name])) {
        $value = TRUE;
      }
    }

    // If we're not in interactive mode, there's nothing more to do for this
    // command other than use the default value. That's already set in the
    // component data.

    if (empty($value) && $interactive) {
      // Show the user the available options, unless told not to.
      $numeric_options = FALSE;
      if (isset($property_info['options']) && empty($property_info['drush_no_option_list'])) {
        $numeric_options = TRUE;
        $option_by_index = array();
        $i = 1;
        foreach ($property_info['options'] as $option => $label) {
          $option_by_index[$i] = $option;
          drush_print("[$i] $option: $label", 2);

          $i++;
        }
      }

      // Make a prompt string, letting the user know if the input is optional
      // so they don't waste time typing.
      $prompt = "Enter the {$property_info['label']}";
      switch ($property_info['format']) {
        case 'array':
          $prompt .= ' ' . "(space separated list)";
          break;
        case 'boolean':
          $prompt .= ' ' . "(y/n)";
          break;
      }
      if (empty($property_info['required']) && empty($component_data[$property_name])) {
        $prompt .= ' ' . "(optional)";
      }

      // Turn empty defaults into a value that Drush won't output.
      $default = empty($component_data[$property_name]) ? NULL : $component_data[$property_name];

      $value = drush_prompt($prompt, $default, $property_info['required']);

      // Split up the value if it should be an array.
      if ($property_info['format'] == 'array') {
        $value = preg_split('/\s+/', $value, -1,  PREG_SPLIT_NO_EMPTY);
      }

      // Callback to convert a value given as an index in a list of options into
      // the actual option value.
      $convert_index_to_value_callback = function($item) use ($option_by_index) {
        if (is_numeric($item)) {
          return $option_by_index[$item];
        }
        else {
          return $item;
        }
      };

      // Convert a numeric option index value into the actual option value.
      if ($numeric_options) {
        // We have either an array of values or a single one.
        if (is_array($value)) {
          $value = array_map($convert_index_to_value_callback, $value);
        }
        else {
          $value = $convert_index_to_value_callback($value);
        }
      }
    }

    // Perform any processing specific to drush.
    if (isset($property_info['drush_value_process'])) {
      $callback = $property_info['drush_value_process'];
      $value = $callback($value);
    }

    // Set the value in the component data array.
    $component_data[$property_name] = $value;
  }

  // Perform any final processing on the component data.
  // This prepares data, for example expands options such as hook presets.
  $root_generator->processComponentData($component_data_info, $component_data);
  //drush_print_r($component_data);

  // Generate the component.
  $files = $mb_task_handler_generate->generateComponent($component_data);
  //drush_print_r($files);

  // Replace the uninitialized root generator we had with the proper one.
  $root_generator = $mb_task_handler_generate->getRootGenerator();

  $component_dir = module_builder_get_component_folder($component_type, $root_generator->getComponentSystemName());

  // Finally, output the files!
  foreach ($files as $filename => $code) {
    module_builder_drush_output_code($component_dir, $filename, $code);
  }
}

/**
 * Process the input for component base name for filesystem shorthands.
 *
 * This allows for the following in the component name:
 *  - A single '.' specifies the current folder.
 *  - A trailing slash is ignored, thus allowing autocompletion of folder names.
 *
 * @param $component_root_name
 *  The given component root name.
 *
 * @return
 *  The processed component root name.
 */
function module_builder_drush_component_root_name_process($component_root_name) {
  // Trim a final '/' from the module machine name, to allow use of tab
  // autocompletion on the command line.
  if (substr($component_root_name, -1) == '/') {
    $component_root_name = substr($component_root_name, 0, -1);
  }

  // An input machine name given as '.' means use the current folder as the
  // component name, thus write to the current folder.
  if ($component_root_name == '.') {
    $component_root_name = basename(drush_get_context('DRUSH_OLDCWD'));
  }

  return $component_root_name;
}

/**
 * Get the folder where a component should be saved.
 *
 * @param $component_type
 *  The type of the component. One of 'module' or 'theme'.
 * @param $component_name
 *  The component name.
 *
 * @return
 *  The full system path for the component's folder, without a trailing slash.
 */
function module_builder_get_component_folder($component_type, $component_name) {
  $drupal_root = drush_get_context('DRUSH_DRUPAL_ROOT');

  // First try: if the component exists, we write there: nice and simple.
  $component_path = drupal_get_path($component_type, $component_name);
  if (!empty($component_path)) {
    return $drupal_root . '/' . $component_path;
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
        if ($subdir == $component_dir) {
          drush_print("Module directory $component_dir created");
        }
        else {
          drush_print("Module subdirectory $subdir created");
        }
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
 *
 * @return
 *  Boolean indicating TRUE for success, FALSE for failure.
 */
function drush_module_builder_callback_hook_download() {
  // Get the MB factory.
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');

  // Get our task handler. This performs a sanity check which throws an
  // exception.
  try {
    $mb_task_handler_collect = $mb_factory->getTask('Collect');
  }
  catch (ModuleBuilderException $e) {
    return drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
  }

  $mb_task_handler_collect->collectHooks();

  // TODO: there is no return value from the hook collection (nor has there
  // been for a long time!)
  // Fake this for now!
  $return = TRUE;

  if (!$return) {
    return drush_set_error(DRUSH_APPLICATION_ERROR, 'Problem downloading hooks.');
  }
  else {
    drush_print("Hook files have been downloaded to {$mb_factory->environment->hooks_directory} and processed.");
    return TRUE;
  }
}

/**
 * Callback to list known hooks.
 */
function drush_module_builder_callback_hook_list() {
  // Get a MB factory.
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');

  // Get our task handler, which checks hook data is ready.
  try {
    $mb_task_handler_report = $mb_factory->getTask('ReportHookData');
  }
  catch (ModuleBuilderException $e) {
    drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
    return;
  }

  $time = $mb_task_handler_report->lastUpdatedDate();
  $data = $mb_task_handler_report->listHookData();

  if (drush_get_option('raw')) {
    drush_print_r($data);
    return;
  }

  if (count($commands)) {
    // Put the requested filenames into the keys of an array, and intersect them
    // with the hook data.
    $files_requested = array_fill_keys($commands, TRUE);
    $data_requested = array_intersect_key($data, $files_requested);
  }
  else {
    $data_requested = $data;
  }

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
  try {
    $mb_task_handler_report_presets = $mb_factory->getTask('ReportHookPresets');
  }
  catch (ModuleBuilderException $e) {
    drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
    return;
  }

  $hook_presets = $mb_task_handler_report_presets->getHookPresets();
  foreach ($hook_presets as $hook_preset_name => $hook_preset_data) {
    drush_print("Preset $hook_preset_name: " . $hook_preset_data['label'], 2);
    foreach ($hook_preset_data['hooks'] as $hook) {
      drush_print($hook, 4);
    }
  }

  $hooks_directory = $mb_factory->environment->hooks_directory;
  drush_print(t("Hook data retrieved from @dir.", array('@dir' => $hooks_directory)));
  drush_print(t("Hook data was processed on @time.", array('@time' => $time)));

  //print_r($data);
}

/**
 * Callback to list hook implementations found in a given module.
 */
function drush_module_builder_callback_hook_analyze() {
  // Get a MB factory.
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');

  // Get our task handler, which checks hook data is ready.
  try {
    $mb_task_handler_report = $mb_factory->getTask('ReportHookData');
    $mb_task_handler_analyze = $mb_factory->getTask('AnalyzeModule');
  }
  catch (ModuleBuilderException $e) {
    drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
    return;
  }

  $commands = func_get_args();

  // The first argument is the module machine name.
  $module_root_name = array_shift($commands);

  // TODO: use module_builder_get_hook_implementations().
  $hooks = $mb_task_handler_report->listHookNames('short');

  foreach ($hooks as $key => $hook) {
    $hooks[$key] = $module_root_name . '_' . $hook;
  }

  $module_files = $mb_task_handler_analyze->getFiles($module_root_name);
  $module_hooks = $module_hooks_flat = array();

  foreach ($module_files as $file) {
    $functions = $mb_task_handler_analyze->getFileFunctions($file);

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
  // TODO: Currently broken: need to find a way to get into the generating system!
  return;

  // Get a MB factory.
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');

  // Get our task handlers, which checks hook data is ready.
  try {
    $mb_task_handler_report = $mb_factory->getTask('ReportHookData');
    $mb_task_handler_analyze = $mb_factory->getTask('AnalyzeModule');
  }
  catch (ModuleBuilderException $e) {
    drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
    return;
  }

  $commands = func_get_args();

  // The first argument is the module machine name.
  $module_root_name = array_shift($commands);

  $module_files = $mb_task_handler_analyze->getFiles($module_root_name);

  $hook_names = $mb_task_handler_report->listHookNames('short');

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
  // Get a MB factory.
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');
  try {
    $mb_task_handler_report_hooks = $mb_factory->getTask('ReportHookData');
  }
  catch (ModuleBuilderException $e) {
    drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
    return;
  }

  // We don't actually need a task, we just need to get the property from the
  // environment. However, getting the ReportHookData task runs the sanity check
  // directory so we can report if it's not writable.
  $hooks_directory = $mb_factory->environment->hooks_directory;

  drush_print('Module builder data is in ' . $hooks_directory);
}

/**
 * WORK IN PROGRESS
 * Add function headers wherever needed with params.
 */
function drush_module_builder_callback_doc_params() {
  // TODO: Currently broken: need to find a way to get into the generating system!
  return;

  $commands = func_get_args();

  // Get a MB factory.
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');

  // Get our task handler, which checks hook data is ready.
  try {
    $mb_task_handler_report = $mb_factory->getTask('ReportHookData');
  }
  catch (ModuleBuilderException $e) {
    drush_set_error(DRUSH_APPLICATION_ERROR, $e->getMessage());
    return;
  }

  $hook_names = $mb_task_handler_report->listHookNames('short');

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
  include(dirname(__FILE__) . '/../includes/process.inc');
  $data = module_builder_get_hook_data_flat();
  drush_print_r($data);




  return;
}
