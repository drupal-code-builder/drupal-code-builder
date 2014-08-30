<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\Module.
 */

namespace ModuleBuider\Generator;

/**
 * Component generator: module.
 *
 * This is a root generator: that is, it's one that may act as the initial
 * requested component given to Task\Generate. (In theory, this could also get
 * requested by something else, for example if we wanted Tests to be able to
 * request a testing module, but that's for another day.)
 *
 * Conceptual hierarchy of generators beneath this in the request tree:
 *  - Hooks
 *    - HookImplementation (multiple)
 *  - RouterItem
 *    - HookMenu (which is itself a HookImplementation!)
 *    - Routing (D8 only)
 *  - PHPFile (multiple)
 *  - Info
 *  - Readme
 *  - API
 *  - Tests
 *  - AdminSettingsForm
 *
 * This generator looks in $module data to determine which of these generators
 * to add. Generators can be requested by name, with various extra special
 * values which are documented in this class's __construct().
 */
class Module extends RootComponent {

  /**
   * The sanity level this generator requires to operate.
   */
  public $sanity_level = 'hook_data';

  /**
   * The data for the component.
   *
   * This is initially the given data for generating the module, but other
   * generators may add data to it during the generating process.
   */
  public $component_data = array();

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the module. The properties are as
   *   follows:
   *    - 'base': The type of component: 'module'.
   *    - 'module_root_name': The machine name for the module.
   *    - 'module_readable_name': The human readable name for the module.
   *    - 'module_short_description': The module's description text.
   *    - 'module_help_text': Help text for the module. If this is given, then
   *       hook_help() is automatically added to the list of required hooks.
   *    - 'hooks': An associative array whose keys are full hook names
   *      (eg 'hook_menu'), where requested hooks have a value of TRUE.
   *      Unwanted hooks may also be included as keys provided their value is
   *      FALSE.
   *    - 'module_dependencies': A string of module dependencies, separated by
   *       spaces, e.g. 'forum views'.
   *    - 'module_package': The module package.
   *    - 'component_folder': (optional) The destination folder to write the
   *      module files to.
   *    - 'module_files': ??? OBSOLETE!? added by this function. A flat array
   *      of filenames that have been generated.
   *    - 'requested_build': An array whose keys are names of subcomponents to
   *       build. Component names are defined in requiredComponents(), and include:
   *       - 'all': everything we can do.
   *       - 'code': PHP code files.
   *       - 'info': the info file.
   *       - 'module': the .module file.
   *       - 'install': the .install file.
   *       - 'tests': test file.
   *       - 'api': api.php hook documentation file.
   *       - FILE ID: requests a particular code file, by the abbreviated name.
   *         This is the filename without the initial 'MODULE.' or the '.inc'
   *         extension.
   *    - 'requested_components': An array of components to build (in addition
   *      to any that are added automatically). This should in the same form
   *      as the return from requiredComponents(), thus keyed by component name,
   *      with values either a component type or an array of data.
   *  Properties added by generators during the process:
   *    - 'hook_file_data': Added by the Hooks generator. Keyed by the component
   *      names of the ModuleCodeFile type components that Hooks adds.
   */
  function __construct($component_name, $component_data = array()) {
    parent::__construct($component_name, $component_data);
  }

  /**
   * Define the component data this component needs to function.
   */
  protected function componentDataDefinition() {
    $component_data_definition = array(
      'module_root_name' => array(
        'label' => 'Module machine name',
        'default' => 'my_module',
        'required' => TRUE,
      ),
      'module_readable_name' => array(
        'label' => 'Module readable name',
        'default' => function($component_data) {
          return ucwords(str_replace('_', ' ', $component_data['module_root_name']));
        },
        'required' => FALSE,
      ),
      'module_short_description' => array(
        'label' => 'Module .info file description',
        'default' => 'TODO: Description of module',
        'required' => FALSE,
      ),
      'module_package' => array(
        'label' => 'Module .info file package',
        'default' => NULL,
        'required' => FALSE,
      ),
      'module_dependencies' => array(
        'label' => 'Module dependencies (space separated list)',
        'default' => NULL,
        'required' => FALSE,
      ),
      // If this is given, then hook_help() is automatically added to the list
      // of required hooks.
      'module_help_text' => array(
        'label' => 'Module help text (adds hook_help())',
        'default' => NULL,
        'required' => FALSE,
      ),
      'module_hook_presets' => array(
        'label' => 'Hook preset groups',
        'required' => FALSE,
        'format' => 'array',
        'options' => function(&$property_info) {
          /// ARGH how to make format that is good for both UI and drush?
          $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');
          $mb_task_handler_report_presets = $mb_factory->getTask('ReportHookPresets');
          $hook_presets = $mb_task_handler_report_presets->getHookPresets();

          // Stash the hook presets in the property info so the processing
          // callback doesn't have to repeat the work.
          $property_info['_presets'] = $hook_presets;

          $options = array();
          foreach ($hook_presets as $name => $info) {
            $options[$name] = $info['label'];
          }
          return $options;
        },
        // The processing callback alters the component data in place, and may
        // in fact alter another value.
        'processing' => function($value, &$component_data, &$property_info) {
          // Get the presets from where the 'options' callback left them.
          $hook_presets = $property_info['_presets'];

          foreach ($value as $given_preset_name) {
            if (!isset($hook_presets[$given_preset_name])) {
              throw new \ModuleBuilderException("Undefined hook preset group $given_preset_name.");
            }
            // DX: check the preset is properly defined.
            if (!is_array($hook_presets[$given_preset_name]['hooks'])) {
              throw new \ModuleBuilderException("Incorrectly defined hook preset group $given_preset_name.");
            }

            // Add the preset hooks list to the hooks array in the component
            // data.
            $hooks = $hook_presets[$given_preset_name]['hooks'];
            $component_data['hooks'] = array_merge($component_data['hooks'], $hooks);
            drush_print_r($component_data['hooks']);
          }
        },
      ),
      'hooks' => array(
        'label' => 'Hook implementations',
        'required' => FALSE,
        'format' => 'array',
        'options' => function(&$property_info) {
          $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');
          $mb_task_handler_report_hooks = $mb_factory->getTask('ReportHookData');

          $hook_options = $mb_task_handler_report_hooks->listHookNamesOptions();

          return $hook_options;
        },
        'processing' => function($value, &$component_data, &$property_info) {
          $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrush');
          $mb_task_handler_report_hooks = $mb_factory->getTask('ReportHookData');
          // Get the flat list of hooks, standardized to lower case.
          $hook_definitions = array_change_key_case($mb_task_handler_report_hooks->getHookDeclarations());

          $hooks = array();
          foreach ($component_data['hooks'] as $hook_name) {
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

          $component_data['hooks'] = $hooks;
        }
      ),
      'settings_form' => array(
        'label' => "Admin settings form",
        'required' => FALSE,
        'format' => 'boolean',
        'component' => 'AdminSettingsForm',
      ),
      'router_items' => array(
        'label' => "required router paths, eg 'path/foo'",
        'required' => FALSE,
        'format' => 'array',
        // This tells the system that this is a request for generator
        // components, and the input data should be placed in a nested array in
        // the module data.
        'component' => 'RouterItem',
      ),

      // The following defaults are for ease of developing.
      // Uncomment them to reduce the amount of typing needed for testing.
      //'hooks' => 'init',
      //'router_items' => 'path/foo path/bar',
      // The following properties shouldn't be offered as UI options.
      'module_camel_case_name' =>  array(
        // Indicates that this does not need to be obtained from the user, as it
        // is computed from other properties.
        'computed' => TRUE,
        'default' => function($component_data) {
          $pieces = explode('_', $component_data['module_root_name']);
          $pieces = array_map('ucfirst', $pieces);
          return implode('', $pieces);
        },
      ),
    );

    return $component_data_definition;
  }

  /**
   * Get the Drupal name for this component, e.g. the module's name.
   */
  public function getComponentSystemName() {
    return $this->component_data['module_root_name'];
  }

  /**
   * Declares the subcomponents for this component.
   *
   * These are not necessarily child classes, just components this needs.
   *
   * TODO: handle version stuff here? Or better to have it transparent in the
   * factory function?
   *
   * @return
   *  An array of subcomponent types.
   */
  protected function requiredComponents() {
    // Add in defaults. This can't be done in __construct() because root
    // generators actually don't get their component data till later. WTF!
    $this->component_data += array(
      'requested_components' => array(),
    );

    // The requested build list is a hairy old thing, so figure that out first
    // and in a helper.
    $build_list_components = $this->buildListComponents();

    $components = $build_list_components + $this->component_data['requested_components'];

    return $components;
  }

  /**
   * Helper to get a component list from the build list.
   *
   * The build list is a complex thing that is best left alone!
   */
  protected function buildListComponents() {
    $module_data = $this->component_data;

    // A module needs:
    //  - info file
    //  - hooks & callbacks: abstract component, which then produces:
    //    -- files
    //  - other abstract components which we don't do yet: form, entity type.
    //    -- (Node these will want to merge into the main module file!!!)
    //  - tests
    //    -- files
    //
    // $module_data['requested_build'] is an array of stuff. The values that
    // matter here are 'all', 'info', 'code', 'readme'.
    // For anything else, the hooks component takes care of further filtering.

    // Start by defining the subcomponents we know how to handle.
    // Keys are names, values are types (to be used to build class names).
    $components = array(
      'hooks' => 'hooks',
      // Component type case must match the filename for case-sensitive
      // filesystems (i.e. not OS X).
      'api' => 'API',
      'readme' => 'readme',
      'tests' => 'tests',
      // Info must go last, as it needs to know what files are being generated.
      'info' => 'info',
    );

    // Create a build list.
    // Take the keys, as all values are set to TRUE, and standardize to lower
    // case for comparisons.
    $build_list = array_keys($module_data['requested_build']);
    array_walk($build_list, function(&$s) {
      $s = strtolower($s);
    });
    $build_list = array_combine($build_list, $build_list);

    // Preliminary: if 'all' was requested, we must ensure the module file,
    // even if it has no hooks.
    // TODO: this is not the case on D8!
    if (isset($build_list['all'])) {
      $components['%module.module'] = 'ModuleCodeFile';
    }

    // Case 1: everything was requested: return everything!
    if (isset($build_list['all'])) {
      return $components;
    }

    // Make a list of component names to compare with what was requested.
    $component_list = array_keys($components);
    // Standardize to lower case for comparison.
    array_walk($component_list, function(&$s) {
      $s = strtolower($s);
    });
    //drush_print_r($component_list);
    //drush_print_r($build_list);

    // Get the components that were requested.
    $intersection_components = array_intersect($component_list, $build_list);
    // Get the requested components that we don't understand.
    $unknown_build_list = array_diff($build_list, $component_list);

    // Case 2: there are no unknown components. Return just what we were asked
    // for.
    if (empty($unknown_build_list)) {
      return $intersection_components;
    }

    // Case 3: there are some requested components we don't know anything about.
    // We assume that these are abbreviated filenames for the attention of
    // ModuleBuider\Generator\Hooks; therefore we must ensure 'hooks' is in the
    // list.
    $intersection_components['hooks'] = 'hooks';

    // TODO: if the components create files containing classes, we probably
    // need to add the 'info' component, BUT we need to add to the .info file
    // rather than rewrite it! This requires (as does template.php) a system for
    // adding to existing files.

    return $intersection_components;
  }

  // No need to declare collectFiles(): parent class will have something that
  // does nothing apart from recurse.

}
