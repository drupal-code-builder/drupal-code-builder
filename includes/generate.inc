<?php

/**
 * @file
 *   Module builder code generating code.
 *
 *   Note that info file generating code happens in generate_info_VERSION, 
 *   as it's version-specific.
 */
 
/**
 * Generate info code. New OO version!
 */
function module_builder_generate_info_oo($module_data) {
  $class = module_builder_get_class('info');
  $generator = new $class($module_data);
  $code = $generator->build();
  
  return $code;
}

/**
 * Helper function to get the desired class.
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

  function code_body() {
    // Get old style variable names.
    $module_data = $this->module_data;    
    $hook_data = $this->hook_data;
    $code = '';
    foreach ($hook_data as $hook_name => $hook) {
      
      // Display PHP doc.
      $code .= "\n" . module_builder_code_hook_doxy($hook_name);

      // function declaration: put in the module name, add closing brace, decode html entities
      $declaration = str_replace('hook', $module_data['module_root_name'], $hook['declaration']) . ' {';
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
      $code .= "}\n\n";
    } // foreach hook

    // Replace variables
    $variables = array(
      '%module' => $module_data['module_root_name'],
      '%description' => str_replace("'", "\'", $module_data['module_short_description']),
      '%name' => !empty($module_data['module_readable_name']) ? str_replace("'", "\'", $module_data['module_readable_name']) : $module_data['module_root_name'],
      '%help' => !empty($module_data['module_help_text']) ? str_replace("'", "\'", $module_data['module_help_text']) : t('TODO: Create admin help text.'),
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
}

/**
 * Generator class for module info file for Drupal 5.
 */
class ModuleBuilderGeneratorInfo5 extends ModuleBuilderGeneratorInfo {
  function code_body() {
    $module_data = $this->module_data;
    
    $info .= 'name = ' . $module_data['module_readable_name'] . "\n";
    $info .= 'description = '. $module_data['module_short_description'] ."\n";

    if (!empty($module_data['module_dependencies'])) {
      $info .= 'dependencies = '. $module_data['module_dependencies'] ."\n";
    }

    if (!empty($module_data['module_package'])) {
      $info .= 'package = '. $module_data['module_package'] ."\n";
    }

    return $info;    
  }
}

/**
 * Generator class for module info file for Drupal 6.
 */
class ModuleBuilderGeneratorInfo6 extends ModuleBuilderGeneratorInfo {
  function code_body() {
    $module_data = $this->module_data;

    $info .= 'name = ' . $module_data['module_readable_name'] . "\n";
    $info .= 'description = '. $module_data['module_short_description'] ."\n";

    if (!empty($module_data['module_dependencies'])) {
      foreach (explode(' ', $module_data['module_dependencies']) as $dep) {
        $info .= 'dependencies[] = '. $dep ."\n";
      }
    }

    if (!empty($module_data['module_package'])) {
      $info .= 'package = '. $module_data['module_package'] ."\n";
    }
    $info .= "core = 6.x\n";

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

    $info .= 'name = ' . $module_data['module_readable_name'] . "\n";
    $info .= 'description = '. $module_data['module_short_description'] ."\n";

    if (!empty($module_data['module_dependencies'])) {
      foreach (explode(' ', $module_data['module_dependencies']) as $dep) {
        $info .= 'dependencies[] = '. $dep ."\n";
      }
    }

    if (!empty($module_data['module_package'])) {
      $info .= 'package = '. $module_data['module_package'] ."\n";
    }
    $info .= "core = 7.x\n";

    if (is_array($module_data['module_files'])) {
      foreach ($module_data['module_files'] as $file) {
        $info .= 'files[] = '. $file ."\n";      
      }
    }

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
 *     - 'module_files': added by this function. A flat array of filenames that have been generated. 
 * @param $bare
 *   If true, omit header and footers and output only hook code.
 * @return
 *   An array of code, indexed by filename. Eg,
 *     'modulename.module' => CODE
 */
function module_builder_generate_module(&$module_data, $bare = FALSE) {
  // Get a set of hook declarations and function body templates for the hooks we want.
  // $hook_data is of the form:
  //   'hook_foo' => array( 'declaration' => DATA, 'template' => DATA )
  $hook_file_data = module_builder_get_templates($module_data);
  if (is_null($hook_file_data)) {
    return NULL;
  }
  
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
 * Get the doxygen header for a given hook.
 * This does not return with an initial newline so the doc block may be inserted into existing code.
 *
 * @param
 *   The long hook name, eg 'hook_menu'.
 */
function module_builder_code_hook_doxy($hook_name) {
  return <<<EOT
/**
 * Implementation of $hook_name().
 */

EOT;
}

/**
 * Helper function for module_builder_generate_module
 *
 * Returns an array of hook data and templates for the requested hooks.
 * This is handled live rather than in process.inc to allow the user to alter
 * their custom hook templates.
 *
 * @return
 *   An array of the form:
 *  'destination file' => array(
 *    'hook_foo' => array( 'declaration' => DATA, 'template' => DATA )
 */
function module_builder_get_templates($module_data) {
  // begin assembling data
  // build an array $hook_data of all the stuff we know about hooks
  // of the form:
  //  'hook_foo' => array( 'declaration' => DATA, 'template' => DATA )
  $hook_data = array();

  // Check for custom functions file, else use default
  $path = module_builder_get_path('templates');

  if (file_exists("$path/hooks-custom.template")) {
    $template_file = file_get_contents("$path/hooks-custom.template");
  }
  else {
    $template_file = file_get_contents("$path/hooks.template");
  }

  // Get array of our hook function body templates from our own / custom template files.
  // This is not necessarily all hooks that exist -- not all have template entries.
  // This array is in the same order as they occur in the files and already in the format wanted.
  // Include generating component file.
  module_builder_include('process');
  $template_data = module_builder_parse_template($template_file);

  // print_r($hook_data); ok!

  // Check for node hooks; these will overwrite core hooks if found.
  if (isset($module_data['hooks']['hook_node_info'])) {
    if (file_exists("$path/node_hooks-custom.template")) {
      $template_file = file_get_contents("$path/node_hooks-custom.template");
    }
    else {
      $template_file = file_get_contents("$path/node_hooks.template");
    }
    $custom_hooks = module_builder_parse_template($template_file);
    foreach ($custom_hooks as $hook_name => $hook_template) {
      // add or clobber our existing templates
      $template_data[$hook_name] = $hook_template;
    }
  }

  // $template_data is now an array of the form:
  //  [hook name] => array('template' => DATA)
  // in a pretty order which we want to hold on to.
  
  //print_r($template_data);
  //dsm($template_data);

  // Get array of the hook function declarations from the downloaded hook data.
  // This is a complete list of all hooks that exist.
  // In the form: 'hook_foo' => array('declaration', 'destination')
  // This array is the order they are in the files from d.org: alphabetical apparently.
  // We don't care for this order!
  $hook_function_declarations = module_builder_get_hook_declarations();
  // If we get NULL then no hook data exists: return NULL again.
  if (is_null($hook_function_declarations)) {
    return NULL;
  }

  //print_r($hook_function_declarations);
  
  // Remove all hooks we don't care about.
  // First filter out the keys with 0 values that come from UI form.
  $requested_hooks = array_filter($module_data['hooks']);
  $hook_function_declarations = array_intersect_key($hook_function_declarations, $requested_hooks);
  $template_data              = array_intersect_key($template_data, $requested_hooks);

  //print_r($requested_hooks);

  // Start hierarchical building hook data.
  // Make the destination filenames, and add an item for each destination file.
  foreach ($hook_function_declarations as $hook_name => $hook) {
    $destination = str_replace('%module', $module_data['module_root_name'], $hook['destination']);
    $hook_function_declarations[$hook_name]['destination'] = $destination;
    $hook_data[$destination] = array();
  }

  // Now iterate over the template data so we use its order
  // and grab data from the declarations array
  // and put it all into the final data array
  foreach (array_keys($template_data) as $hook_name) {
    $destination = $hook_function_declarations[$hook_name]['destination'];
    $hook_data[$destination][$hook_name]['declaration'] = $hook_function_declarations[$hook_name]['declaration'];
    $hook_data[$destination][$hook_name]['template']    = $template_data[$hook_name]['template'];
  }

  //dsm($hook_data);

  // Not all hooks have template data
  foreach ($hook_function_declarations as $hook_name => $hook) {
    $destination = $hook['destination'];
    if (!isset($hook_data[$destination][$hook_name]['declaration'])) {
      $hook_data[$destination][$hook_name]['declaration'] = $hook_function_declarations[$hook_name]['declaration'];
    }
  }


  //dsm($hook_data);

  // $hook_data is now a complete representation of all we know about the requested hooks
  return $hook_data;
}
