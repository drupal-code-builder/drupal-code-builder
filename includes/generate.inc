<?php

/**
 * @file
 * Module builder code generating code.
 */
 
/**
 * Generate info code.
 */
function module_builder_generate_info($module_data) {
  $class = module_builder_get_class('info');
  $generator = new $class($module_data);
  $code = $generator->build();
  
  return $code;
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
 *
 * @param $type
 *  One of (so far) 'info' or 'code'.
 * @return
 *  A class name for the type and, if it exists, version, eg 'ModuleBuilderGeneratorInfo6'.
 */
function module_builder_get_class($type) {
  $type     = ucfirst($type);
  $version  = _module_builder_drupal_major_version();
  $class    = 'ModuleBuilderGenerator' . $type . $version;
  if (!class_exists($class)) {
    $class  = 'ModuleBuilderGenerator' . $type;
  }
  return $class;
}
 
/**
 * Base class for code generators.
 *
 * TODO: much more of the code generation could be moved to OO code:
 *  - pass all hook data to one code generator object, representing the .module
 *  file, which then instantiates further objects for the other files needed.
 *  This would probably entail a controller object which can hold the list of
 *  current generators.
 *  - templates.
 */ 
abstract class ModuleBuilderGenerator {
  function __construct($module_data) {
    $this->module_data = $module_data;
  }
  
  /**
   * The main code building function.
   */
  function build() {
    $output = '';
    $output .= $this->file_header();
    $output .= $this->code_header();
    $output .= $this->code_body();
    $output .= $this->code_footer();
    
    return $output;    
  }
  

  /**
   * Return the PHP file header line.
   */
   function file_header()  {
     return "<?php\n";     
   }
  
  /**
   * Return the file doxygen header and any custom header code.
   */
  function code_header() {
    $filename = $this->filename;
    $default = <<<EOT
/**
 * @file $filename
 * TODO: Enter file description here.
 */

EOT;
    $code = variable_get('module_builder_header', $default);
    return $code;
  }
  
  /**
   * Return the main body of the file code.
   */
  abstract function code_body();
  
  /**
   * Return a file footer.
   */
  function code_footer() {}
}

/**
 * Generator class for module code files.
 */
class ModuleBuilderGeneratorCode extends ModuleBuilderGenerator {
  function __construct($module_data) {
    $this->module_data = $module_data;
  }

  /**
   * Make the doxygen header for a given hook.
   * This does not return with an initial newline so the doc block may be
   * inserted into existing code.
   *
   * @param
   *   The long hook name, eg 'hook_menu'.
   */
  function hook_doxygen($hook_name) {
    return <<<EOT
/**
 * Implements $hook_name().
 */

EOT;
  }

  function code_body() {
    // Get old style variable names.
    $module_data = $this->module_data;    
    $hook_data = $this->hook_data;
    $code = '';
    foreach ($hook_data as $hook_name => $hook) {
      
      // Display PHP doc, using the original case of the hook name.
      $code .= "\n" . $this->hook_doxygen($hook['name']);

      // function declaration: put in the module name, add closing brace, decode html entities
      $declaration = preg_replace('/(?<=function )hook/', $module_data['module_root_name'], $hook['declaration']);
      $declaration .= ' {';
      $code .= htmlspecialchars_decode($declaration);


      // See if function bodies exist; if so, use function bodies from template
      if (isset($hook['template'])) {
        // Strip out INFO: comments for advanced users
        if (!variable_get('module_builder_detail', 0)) {
          // Used to strip INFO messages out of generated file for advanced users.
          $pattern = '#\s+/\* INFO:(.*?)\*/#ms';
          $hook['template'] = preg_replace($pattern, '', $hook['template']);
        }
        //dsm($hook);

        $code .= $hook['template'];
      }
      else {
        $code .= "\n\n";
      }
      // Gap between successive hooks.
      $code .= "}\n";
    } // foreach hook

    // Replace variables
    $variables = array(
      '%module' => $module_data['module_root_name'],
      '%description' => str_replace("'", "\'", $module_data['module_short_description']),
      '%name' => !empty($module_data['module_readable_name']) ? str_replace("'", "\'", $module_data['module_readable_name']) : $module_data['module_root_name'],
      '%help' => !empty($module_data['module_help_text']) ? str_replace('"', '\"', $module_data['module_help_text']) : t('TODO: Create admin help text.'),
      '%readable' => str_replace("'", "\'", $module_data['module_readable_name']),
    );
    $code = strtr($code, $variables);
    
    return $code;
  }
  
  /**
   * Return a file footer.
   */
  function code_footer() {
    $footer = variable_get('module_builder_footer', '');
    return $footer;
  }
}

/**
 * Generator class for module code files for Drupal 6.
 */
class ModuleBuilderGeneratorCode6 extends ModuleBuilderGeneratorCode {
  /**
   * Make the doxygen header for a given hook with the Drupal 6 format.
   *
   * This does not return with an initial newline so the doc block may be
   * inserted into existing code.
   *
   * @param
   *   The long hook name, eg 'hook_menu'.
   */
  function hook_doxygen($hook_name) {
    return <<<EOT
/**
 * Implementation of $hook_name().
 */

EOT;
  }
}

/**
 * Generator base class for module info file.
 */
class ModuleBuilderGeneratorInfo extends ModuleBuilderGenerator {
  /**
   * Override as info files have no header.
   */
  function file_header() { }
  
  /**
   * Override as info files have no header.
   */
  function code_header($filename = NULL) {
  }
  
  // Override abstract...
  function code_body() {}

  /**
   * Merge an array of info file lines into a single piece of text.
   *
   * @param $lines
   *  An array of lines keyed by label.
   *  Place grouped labels (eg, dependencies) into an array of
   *  their own, keyed numerically.
   *  Eg:
   *    name => module name
   *    dependencies => array(foo, bar)
   *
   * @return
   *  The merged text suitable for output into a .info file.
   */
  function merge_info_lines($lines) {
    foreach ($lines as $label => $data) {
      if (is_array($data)) {
        foreach ($data as $data_piece) {
          $merged_lines[] = $label . "[] = $data_piece"; // Urgh terrible variable name!
        }
      }
      else {
        $merged_lines[] = "$label = $data";
      }
    }
    $info = implode("\n", $merged_lines);
    // Add a newline to the final line.
    $info .= "\n";
    return $info;
  }
}

/**
 * Generator class for module info file for Drupal 5.
 */
class ModuleBuilderGeneratorInfo5 extends ModuleBuilderGeneratorInfo {
  function code_body() {
    $module_data = $this->module_data;
    
    $lines = array();
    $lines['name'] = $module_data['module_readable_name'];
    $lines['description'] = $module_data['module_short_description'];

    if (!empty($module_data['module_dependencies'])) {
      $lines['dependencies'] = $module_data['module_dependencies'];
    }

    if (!empty($module_data['module_package'])) {
      $lines['package'] = $module_data['module_package'];
    }

    $info = $this->merge_info_lines($lines);
    return $info;
  }
}

/**
 * Generator class for module info file for Drupal 6.
 */
class ModuleBuilderGeneratorInfo6 extends ModuleBuilderGeneratorInfo {
  function code_body() {
    $module_data = $this->module_data;

    $lines = array();
    $lines['name'] = $module_data['module_readable_name'];
    $lines['description'] = $module_data['module_short_description'];
    if (!empty($module_data['module_dependencies'])) {
      // For lines which form a set with the same key and array markers,
      // simply make an array.
      foreach (explode(' ', $module_data['module_dependencies']) as $dependency) {
        $lines['dependencies'][] = $dependency;
      }
    }
    
    if (!empty($module_data['module_package'])) {
      $lines['package'] = $module_data['module_package'];
    }
    $lines['core'] = "6.x";
    
    $info = $this->merge_info_lines($lines);
    return $info;
  }
}

/**
 * Generator class for module info file for Drupal 7.
 */
class ModuleBuilderGeneratorInfo7 extends ModuleBuilderGeneratorInfo {
  function code_body() {
    $module_data = $this->module_data;
    //print_r($module_data);

    $lines = array();
    $lines['name'] = $module_data['module_readable_name'];
    $lines['description'] = $module_data['module_short_description'];
    if (!empty($module_data['module_dependencies'])) {
      // For lines which form a set with the same key and array markers,
      // simply make an array.
      foreach (explode(' ', $module_data['module_dependencies']) as $dependency) {
        $lines['dependencies'][] = $dependency;
      }
    }

    if (!empty($module_data['module_package'])) {
      $lines['package'] = $module_data['module_package'];
    }

    $lines['core'] = "7.x";

    /*
    // Only files containing classes need to be declared in the .info file;
    // we don't add any classes yet.
    if (is_array($module_data['module_files'])) {
      foreach ($module_data['module_files'] as $file) {
        $lines['files'][] = $file;
      }
    }
    */

    $info = $this->merge_info_lines($lines);
    return $info;
  }
}


/**
 * Generate module code.
 *
 * @param $module_data
 *   An associative array of data for the module, passed by reference so data
 *   on generated files can be added. 
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
 *     - 'module_files': added by this function. A flat array of filenames that have been generated. 
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
      drush_print_r($hook_function_declarations[$hook_name]['dependencies']);
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
