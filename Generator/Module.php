<?php

namespace DrupalCodeBuilder\Generator;

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
  public static $sanity_level = 'component_data_processed';

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
   *    - 'root_name': The machine name for the module.
   *    - 'readable_name': The human readable name for the module.
   *    - 'short_description': The module's description text.
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
   */
  function __construct($component_data) {
    parent::__construct($component_data);
  }

  /**
   * Define the component data this component needs to function.
   */
  public static function componentDataDefinition() {
    $component_data_definition = parent::componentDataDefinition();

    $component_data_definition['root_name'] = [
      'label' => 'Module machine name',
      'default' => 'my_module',
    ] + $component_data_definition['root_name'];

    $component_data_definition += [
      'base' => [
        'internal' => TRUE,
        'default' => 'module',
        'process_default' => TRUE,
      ],
      'root_name' => array(
        'label' => 'Module machine name',
        'default' => 'my_module',
        'required' => TRUE,
      ),
      'readable_name' => array(
        'label' => 'Module readable name',
        'default' => function($component_data) {
          return ucwords(str_replace('_', ' ', $component_data['root_name']));
        },
        'required' => FALSE,
        'process_default' => TRUE,
      ),
      'short_description' => array(
        'label' => 'Module .info file description',
        'default' => 'TODO: Description of module',
        'required' => FALSE,
        'process_default' => TRUE,
      ),
      'module_package' => array(
        'label' => 'Module .info file package',
        'default' => '',
        'required' => FALSE,
        'process_default' => TRUE,
      ),
      'module_dependencies' => array(
        'label' => 'Module dependencies',
        'description' => 'The machine names of the modules this module should have as dependencies.',
        'default' => array(),
        'required' => FALSE,
        // We need a value for this, as other generators acquire it.
        'process_default' => TRUE,
        'format' => 'array',
      ),
      // If this is given, then hook_help() is automatically added to the list
      // of required hooks.
      'module_help_text' => array(
        'label' => 'Module help text',
        'description' => 'The text to show on the site help page. This automatically adds hook_help().',
        'default' => NULL,
        'required' => FALSE,
      ),
      'settings_form' => array(
        'label' => "Admin settings form",
        'description' => "A form for setting the module's general settings. Also produces a permission and a router item.",
        'required' => FALSE,
        'format' => 'compound',
        'cardinality' => 1,
        'component_type' => 'AdminSettingsForm',
      ),
      'forms' => array(
        'label' => "Forms",
        'description' => "The forms for this module to provide.",
        'required' => FALSE,
        'format' => 'compound',
        'component_type' => 'Form',
      ),
      'services' => array(
        'label' => "Services",
        'description' => 'The services for this module to provide.',
        'required' => FALSE,
        'format' => 'compound',
        'component_type' => 'Service',
      ),
      'permissions' => array(
        'label' => "Permissions",
        'description' => 'The permissions for this module to provide.',
        'required' => FALSE,
        'format' => 'compound',
        'component_type' => 'Permission',
      ),
      'module_hook_presets' => array(
        'label' => 'Hook preset groups',
        'required' => FALSE,
        'format' => 'array',
        'options' => function(&$property_info) {
          /// ARGH how to make format that is good for both UI and drush?
          $mb_task_handler_report_presets = \DrupalCodeBuilder\Factory::getTask('ReportHookPresets');
          $hook_presets = $mb_task_handler_report_presets->getHookPresets();

          // Stash the hook presets in the property info so the processing
          // callback doesn't have to repeat the work.
          // TODO: this is not working! See the processing callback.
          $property_info['_presets'] = $hook_presets;

          $options = array();
          foreach ($hook_presets as $name => $info) {
            $options[$name] = $info['label'];
          }
          return $options;
        },
        // The processing callback alters the component data in place, and may
        // in fact alter another value.
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          // TODO: the options aren't there, as generateComponent() only gets
          // given data, not the component info array. However, it's probably
          // better to re-compute these lazily rather than do them all.
          $mb_task_handler_report_presets = \DrupalCodeBuilder\Factory::getTask('ReportHookPresets');
          $hook_presets = $mb_task_handler_report_presets->getHookPresets();

          foreach ($value as $given_preset_name) {
            if (!isset($hook_presets[$given_preset_name])) {
              throw new \DrupalCodeBuilder\Exception\InvalidInputException(strtr("Undefined hook preset group !name", array(
                '!name' => htmlspecialchars($given_preset_name, ENT_QUOTES, 'UTF-8'),
              )));
            }
            // DX: check the preset is properly defined.
            if (!is_array($hook_presets[$given_preset_name]['hooks'])) {
              throw new \DrupalCodeBuilder\Exception\InvalidInputException(strtr("Incorrectly defined hook preset group !name", array(
                '!name' => htmlspecialchars($given_preset_name, ENT_QUOTES, 'UTF-8'),
              )));
            }

            // Add the preset hooks list to the hooks array in the component
            // data. The 'hooks' property processing will handle them.
            $hooks = $hook_presets[$given_preset_name]['hooks'];
            $component_data['hooks'] = array_merge($component_data['hooks'], $hooks);
            //drush_print_r($component_data['hooks']);
          }
        },
      ),
      'hooks' => array(
        'label' => 'Hook implementations',
        'required' => FALSE,
        'format' => 'array',
        'options' => function(&$property_info) {
          $mb_task_handler_report_hooks = \DrupalCodeBuilder\Factory::getTask('ReportHookData');

          $hook_options = $mb_task_handler_report_hooks->listHookNamesOptions();

          return $hook_options;
        },
        'options_structured' => function(&$property_info) {
          $mb_task_handler_report_hooks = \DrupalCodeBuilder\Factory::getTask('ReportHookData');

          $hook_options = $mb_task_handler_report_hooks->listHookOptionsStructured();

          return $hook_options;
        },
        'processing' => function($value, &$component_data, $property_name, &$property_info) {
          $mb_task_handler_report_hooks = \DrupalCodeBuilder\Factory::getTask('ReportHookData');
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

          // Filter out empty values, in case Drupal UI forms haven't done so.
          $hooks = array_filter($hooks);

          // Set the processed hooks list back into the component data.
          $component_data['hooks'] = $hooks;
        }
      ),
      'content_entity_types' => array(
        'label' => 'Content entity types',
        'required' => FALSE,
        'format' => 'compound',
        // This tells the system that this is a request for generator
        // components, and the input data should be placed in a nested array in
        // the module data.
        'component_type' => 'ContentEntityType',
      ),
      'config_entity_types' => array(
        'label' => 'Config entity types',
        'required' => FALSE,
        'format' => 'compound',
        'component_type' => 'ConfigEntityType',
      ),
      // TODO: come up with a way to generalize this if more plugin discovery
      // types become common.
      // TODO: rename this to 'plugins_annotated'.
      'plugins' => array(
        'label' => 'Plugins (annotated class)',
        'required' => FALSE,
        'format' => 'compound',
        // This tells the system that this is a request for generator
        // components, and the input data should be placed in a nested array in
        // the module data.
        'component_type' => 'Plugin',
      ),
      'plugins_yaml' => [
        'label' => 'Plugins (YAML)',
        'required' => FALSE,
        'format' => 'compound',
        'component_type' => 'PluginYAML',
      ],
      'plugin_types' => array(
        'label' => 'Plugin types',
        'required' => FALSE,
        'format' => 'compound',
        'component_type' => 'PluginType',
      ),
      'theme_hooks' => array(
        'label' => "Theme hooks",
        'description' => "The name of theme hooks, without the leading 'theme_'.",
        'required' => FALSE,
        'format' => 'array',
        'component_type' => 'ThemeHook',
      ),
      'router_items' => array(
        'label' => "Routes",
        'required' => FALSE,
        'format' => 'compound',
        'component_type' => 'RouterItem',
      ),
      'library' => [
        'label' => "Library",
        'description' => 'A collection of CSS and JS assets, declared in a libraries.yml file.',
        'required' => FALSE,
        'format' => 'compound',
        'component_type' => 'Library',
      ],
      'api' => array(
        'label' => "api.php file",
        'description' => 'An api.php file documents hooks and callbacks that this module invents.',
        'required' => FALSE,
        'default' => FALSE,
        'format' => 'boolean',
        'component_type' => 'API',
      ),
      'readme' => array(
        'label' => "README file",
        'required' => FALSE,
        'default' => TRUE,
        'format' => 'boolean',
        'component_type' => 'Readme',
      ),
      'phpunit_tests' => array(
        'label' => "PHPUnit test case class",
        'format' => 'compound',
        'component_type' => 'PHPUnitTest',
        'required' => FALSE,
      ),
      'tests' => array(
        'label' => "Simpletest test case class",
        'description' => 'NOTICE: These are deprecated in Drupal 8.',
        'required' => FALSE,
        'default' => FALSE,
        'format' => 'boolean',
        'component_type' => 'Tests',
      ),

      // The following defaults are for ease of developing.
      // Uncomment them to reduce the amount of typing needed for testing.
      //'hooks' => 'init',
      //'router_items' => 'path/foo path/bar',
      // The following properties shouldn't be offered as UI options.
      'camel_case_name' =>  array(
        // Indicates that this does not need to be obtained from the user, as it
        // is computed from other properties.
        'computed' => TRUE,
        'default' => function($component_data) {
          $pieces = explode('_', $component_data['root_name']);
          $pieces = array_map('ucfirst', $pieces);
          return implode('', $pieces);
        },
      ),
    ];

    return $component_data_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents() {
    $components = array();

    // Turn the hooks property into the Hooks component.
    if (!empty($this->component_data['hooks'])) {
      $components['hooks'] = array(
        'component_type' => 'Hooks',
        'hooks' => $this->component_data['hooks'],
      );
    }

    // Modules always have a .info file.
    $components['info'] = [
      'component_type' => 'Info',
    ];

    // Add hook_help if help text is given.
    if (!empty($this->component_data['module_help_text'])) {
      if (isset($components['hooks'])) {
        $components['hooks']['hooks']['hook_help'] = TRUE;
      }
      else {
        $components['hooks'] = array(
          'component_type' => 'Hooks',
          'hooks' => array(
            'hook_help' => TRUE,
          ),
        );
      }
    }

    return $components;
  }

  /**
   * Filter the file info array to just the requested build list.
   *
   * WARNING: the keys in the $files array will be changing soon!
   *
   * @param &$files
   *  The array of built file info.
   * @param $build_list
   *  The build list parameter from the original Generate component data.
   * @param $component_data
   *  The original component data.
   */
  public function applyBuildListFilter(&$files, $build_list, $component_data) {
    // Case 1: everything was requested: don't filter!
    if (isset($build_list['all'])) {
      return;
    }

    $build_list = array_keys($build_list);

    foreach ($files as $key => $file_info) {
      if (!array_intersect($file_info['build_list_tags'], $build_list)) {
        unset($files[$key]);
      }
    }
  }


  /**
   * Provides replacement strings for tokens in code body.
   *
   * @return
   *  An array of tokens to replacements, suitable for use by strtr().
   */
  function getReplacements() {
    // Get old style variable names.
    $module_data = $this->component_data;

    return array(
      '%module'       => $module_data['root_name'],
      '%Module'       => ucfirst($module_data['readable_name']),
      '%description'  => str_replace("'", "\'", $module_data['short_description']),
      '%name'         => !empty($module_data['readable_name']) ? str_replace("'", "\'", $module_data['readable_name']) : $module_data['root_name'],
      '%help'         => !empty($module_data['module_help_text']) ? str_replace('"', '\"', $module_data['module_help_text']) : 'TODO: Create admin help text.',
      '%readable'     => str_replace("'", "\'", $module_data['readable_name']),
    );
  }

}
