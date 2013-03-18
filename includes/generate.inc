<?php

/**
 * @file
 * Module builder code generating code.
 */

/**
 * Generate the files for a component.
 *
 * This is the entry point for the generating system.
 *
 * @param $module_data
 *   An associative array of data for the module, [passed by reference so data
 *   on generated files can be added!?!?!?!]
 *   The keys can *mostly* be taken straight from form values. They are as follows:
 *     - 'module_root_name'
 *     - 'module_readable_name'
 *     - 'module_short_description'
 *     - 'module_help_text'
 *     - 'hooks': An associative array whose keys are full hook names
 *       (eg 'hook_menu'), where requested hooks have a value of TRUE.
 *       Unwanted hooks may also be included as keys provided their value is FALSE.
 *     - 'module_dependencies': a string of dependencies, eg 'forum views'.
 *     - 'module_package': the module package.
 *     - 'module_folder': (optional) The destination folder to write the module
 *        files to.
 *     - 'module_files': ??? OBSOLETE!? added by this function. A flat array of filenames that have been generated.
 *    - 'requested_build': An array whose keys are requested things to build.
 // - all -- everything we can do
 // - code -- code files, not info (module + install _ ..?)
 // - info -- only info fole
 // - module -- only module file
 // - install -- only install file
 // - ??? whatever hooks need
 * @param $component
 *  A component. Currently only supports 'module'!
 *
 * @return
 *  A files array whose keys are filepaths (relative to the module folder) and
 *  values are the code destined for each file.
 */
function module_builder_generate_component($module_data, $component) {
  $class = module_builder_get_class($component);
  $generator = new $class($component, $module_data);

  // Recursively get subcomponents.
  $generator->getSubComponents();

  //drush_print_r($generator->components);

  // Recursively build files.
  $files = array();
  $generator->collectFiles($files);

  $files_assembled = $generator->assembleFiles($files);

  return $files_assembled;
}

/**
 * Helper function to get the desired class.
 *
 * The hierarchy is:
 *  - ModuleBuilderGenerator
 *    - ModuleBuilderGeneratorCode
 *      - ModuleBuilderGeneratorCode6
 *    - ModuleBuilderGeneratorInfo
 *      - ModuleBuilderGeneratorInfo5
 *      - ModuleBuilderGeneratorInfo6
 *      - ModuleBuilderGeneratorInfo7
 *    - ModuleBuilderGeneratorReadme
 *
 * @param $type
 *  The type of the component. This is the key of arrays returned by
 *  generators' subComponents() method.
 *
 * @return
 *  A class name for the type and, if it exists, version, e.g.
 *  'ModuleBuilderGeneratorInfo6'.
 */
function module_builder_get_class($type) {
  // Include our general include files, which contains base and parent classes.
  // In the absence of autoloading, we need to do this.
  include_once("generators/ModuleBuilderGenerators.inc");

  $type     = ucfirst($type);
  $version  = _module_builder_drupal_major_version();
  $class    = 'ModuleBuilderGenerator' . $type . $version;
  // Generator classes are in standardly named files, with all versions in the
  // same file.
  include_once("generators/ModuleBuilderGenerator$type.inc");

  // If there is no version-specific class, use the base class.
  if (!class_exists($class)) {
    $class  = 'ModuleBuilderGenerator' . $type;
  }
  return $class;
}

/**
 * Generate module code.
 *
 * TODO: OBSOLETE!
 *  - replace ALL mentions in code!
 *  - support the bare option in new system somehow.
 *
 * @param $module_data
 * @param $bare
 *   If true, omit header and footers and output only hook code.
 * @return
 *   An array of code, indexed by filename. Eg,
 *     'modulename.module' => CODE
 */
function module_builder_generate_module(&$module_data, $bare = FALSE) {
  // Force hook_help() if there is help text in the incoming data.
  if (isset($module_data['module_help_text'])) {
    $module_data['hooks']['hook_help'] = TRUE;
  }

  // Get a set of hook declarations and function body templates for the hooks we want.
  // $hook_data is of the form:
  //   'hook_foo' => array( 'declaration' => DATA, 'template' => DATA )
  $hook_file_data = module_builder_get_templates($module_data);

  // There must always be a MODULE.module file, even if there are no hooks to
  // go in it.
  // (Slight niggle: it gets put at the end :/)
  $hook_file_data += array(
    $module_data['module_root_name'] . '.module' => array(),
  );

  //print_r($module_data);
  //dsm($hook_file_data);

  // Iterate over our data array, because it's in a pretty order.
  // by each needed file of code.
  $module_code = array();
  foreach ($hook_file_data as $filename => $hook_data) {
    $class = module_builder_get_class('code');
    $generator = new $class($module_data);
    $generator->hook_data = $hook_data;
    $generator->filename = $filename;

    if ($bare) {
      $code = $generator->code_body;
    }
    else {
      $code = $generator->build();
    }

    //dsm($code);
    //print $code;

    $module_code[$filename] = $code;
    // Add the generated filename to the module data for the info generation to find.
    $module_data['module_files'][] = $filename;
  } // foreach file

  //print_r($module_data);

  return $module_code;
}

/**
 * Helper function for module_builder_generate_module
 *
 * TODO: well in fact, ModuleBuilderGeneratorHooks.
 *
 * Move this to that generator class, or might it be used by other components
 * in future?
 *
 * Returns an array of hook data and templates for the requested hooks.
 * This is handled live rather than in process.inc to allow the user to alter
 * their custom hook templates.
 *
 * @return
 *   An array whose keys are destination filenames, with tokens replaced, and
 *   whose values are arrays of hook data. The hook data keys are:
 *    - declaration: The function declaration, with the 'hook' part not
 *        yet replaced.
 *    - destination: The destination, with tokens still in place.
 *    - template_files: A list of template file types, in order of preference,
 *        keyed by filename and with the value TRUE if the hook code exists
 *        in that template file.
 *    - template (optional): The template code, if any was found.
 *   Example:
 *  'destination file' => array(
 *    'hook_foo' => array('declaration' => DATA, 'template' => DATA)
 */
function module_builder_get_templates($module_data) {
  // Include generating component file.
  module_builder_include('process');

  // Build a clean list of the requested hooks, by filtering out the keys
  // with 0 values that come from UI form.
  $requested_hooks = array_filter($module_data['hooks']);
  //print_r($requested_hooks);
  // TODO: might not need this; easier to test truth than isset.

  // Get array of the hook function declarations from the downloaded hook data.
  // This is a complete list of all hooks that exist.
  // In the form: 'hook_foo' => array('declaration', 'destination')
  // This array is the order they are in the files from d.org: alphabetical apparently.
  // We don't care for this order!
  $hook_function_declarations = module_builder_get_hook_declarations();
  // If we get NULL then no hook data exists: return NULL again.
  // TODO: check this sort of error at an earlier stage!
  if (is_null($hook_function_declarations)) {
    return NULL;
  }
  //drush_print_r($hook_function_declarations);
  // TODO: this should contain the name of the api.php file that provided it!

  // Add hook dependencies.
  foreach (array_keys($requested_hooks) as $hook_name) {
    if (!empty($hook_function_declarations[$hook_name]['dependencies'])) {
      //drush_print_r($hook_function_declarations[$hook_name]['dependencies']);
      foreach ($hook_function_declarations[$hook_name]['dependencies'] as $hook_dependency) {
        $requested_hooks[$hook_dependency] = TRUE;
      }
    }
  }

  // Trim this down to just the ones we care about.
  // By this point, both sets of array keys are standardized to lower case.
  $hook_function_declarations = array_intersect_key($hook_function_declarations, $requested_hooks);
  //print_r("hook_function_declarations: \n");
  //drush_print_r($hook_function_declarations);

  // Filter out the requested hooks that don't have definitions.
  // We do this now as it's possible for a hook to have no definition because
  // the user doesn't have it, but have a template because we provide it,
  // eg views_api.
  // We do this by hand this time rather than array_intersect_key() so we can
  // make a list of hooks we're rejecting for (TODO!) eventual warning output.
  $rejected_hooks = array();
  foreach (array_keys($requested_hooks) as $hook_name) {
    if (!isset($hook_function_declarations[$hook_name])) {
      unset($requested_hooks[$hook_name]);
      $rejected_hooks[] = $hook_name;
    }
  }
  // TODO: at this point we should check if we have any actual hooks to
  // process, and warn if not.
  // We should probably also do something with rejected hooks list.

  // Step 1:
  // Build up a list of the basic template files we want to parse.
  //  - in each $hook_function_declarations item, place an ordered list of
  //    all potential template files. We will set these to TRUE in step 2
  //    if they hold a template for the hook.
  //  - meanwhile, build up list of template files we will want to check for
  //    existence and parse.
  // Template filenames are of the following form, in the order they should be
  // checked, ie from most specific to most general:
  //  - GROUP.hooks.template, eg node.hooks.template
  //    (Though groups are still TODO: this is scaffold only for now!)
  //  - FILENAME.template, where the modulename is replaced with 'hooks', hence
  //    hooks.module.template, hooks.install.template, hooks.views.inc.template.
  //  - hooks.template - the base template, final fallback
  // These are found in module_builder/templates/VERSION, and
  // in addition, a file may be overridden by being present in the user's
  // data directory. Though just what the 'data directory' means exactly is
  // not yet properly defined...
  $template_file_names = array();
  foreach ($hook_function_declarations as $hook_name => $hook) {
    // TODO: $groupname_template = 'GROUP.hooks.template';
    $filename_template  = str_replace('%module', 'hooks', $hook['destination']) . '.template';
    // Place in each $hook_function_declarations item an ordered list of
    // potential files from best fit to fallback.
    // These are keyed by filename and all with value FALSE initially.
    $hook_function_declarations[$hook_name]['template_files'] = array_fill_keys(array(
      // TODO: $groupname_template,
      $filename_template,
      'hooks.template',
    ), FALSE);

    // Meanwhile, build up list of files we will want to check for existence and parse.
    // TODO: $template_file_names[$groupname_template] = TRUE;
    $template_file_names[$filename_template] = TRUE;
    $template_file_names['hooks.template'] = TRUE;

  }

  // print_r("template file names: \n");
  // print_r($template_file_names);

  // print_r("hook_function_declarations are now:: \n");
  // print_r($hook_function_declarations);

  // Step 2:
  // Now we parse the templates we need.
  // We look in two places: module_builder's own '/templates' folder, and the optional
  // location given for user data (the latter is in fact TODO...)
  // User templates override on a per-file basis, so a custom
  // node.hooks.template will only override that same file in the module data;
  // if the hook is not requested as part of a group then that file will not be considered.
  // (Though groups are broken for now...)
  $version = _module_builder_drupal_major_version();
  $template_base_path_module = module_builder_get_path('templates') . '/' . $version;
  //print "base path: $template_base_path_module";
  // $template_base_paths['module']
  // $template_base_paths['user']

  $template_data = array();
  foreach (array_keys($template_file_names) as $filename) {
    $filepath = "$template_base_path_module/$filename";
    if (file_exists($filepath)) {
      $template_file = file_get_contents($filepath);
      $template_data = module_builder_parse_template($template_file);

      // Trim the template data to the hooks we care about.
      $template_data = array_intersect_key($template_data, $requested_hooks);

      // Flag the template file in the hook list; ie, set to TRUE the template
      // file in the list which we first created as entirely FALSE.
      foreach (array_keys($template_data) as $hook_name) {
        $hook_function_declarations[$hook_name]['template_files'][$filename] = TRUE;
      }
    }
  }

  //print_r("hook_function_declarations now have template files \n");
  //print_r($hook_function_declarations);


  // $template_data is now an array of the form:
  //  [hook name] => array('template' => DATA)
  // in a pretty order which we want to hold on to.

  //print_r('template data is:');
  //print_r($template_data);

  // Step 3a:
  // Build a new array of hook data, so that we take the order from the
  // template data, but using the same data structure as the
  // $hook_function_declarations array.
  // The big question here: once we have other template files, such as those
  // for groups, or user ones, how do we combine the order from all of them?
  // Or do we just have an overall order from the template files' order, and
  // then within that respect each of theirs, so in effect it's like
  // concatenating all the template files we use?
  $hook_data_return = array();
  foreach (array_keys($template_data) as $hook_name) {
    $destination = $hook_function_declarations[$hook_name]['destination'];
    // TODO: unclear when to replace destination tokens.
    $destination = str_replace('%module', $module_data['module_root_name'], $destination);

    // Copy over the data we already had.
    $hook_data_return[$destination][$hook_name] = $hook_function_declarations[$hook_name];

    // Copy over the template.
    // TODO: more here.
    $hook_data_return[$destination][$hook_name]['template']    = $template_data[$hook_name]['template'];
  }

  // Step 3b:
  // Not all hooks have template data, so fill these in too.
  foreach ($hook_function_declarations as $hook_name => $hook) {
    $destination = $hook_function_declarations[$hook_name]['destination'];
    $destination = str_replace('%module', $module_data['module_root_name'], $destination);

    if (!isset($hook_data_return[$destination][$hook_name])) {
      $hook_data_return[$destination][$hook_name] = $hook_function_declarations[$hook_name];
    }
    // We have no template data, so fill in the sample from the api.php file,
    // as this is often informative.
    if (empty($hook_data_return[$destination][$hook_name]['template'])) {
      $hook_data_return[$destination][$hook_name]['template'] = $hook_function_declarations[$hook_name]['body'];
    }
  }


  //print_r('step 3:');
  //print_r($hook_data_return);
  return $hook_data_return;
}
