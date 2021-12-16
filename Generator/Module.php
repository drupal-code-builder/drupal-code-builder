<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use MutableTypedData\Definition\OptionDefinition;
use MutableTypedData\Definition\VariantDefinition;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use MutableTypedData\Definition\DefaultDefinition;

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
  public $component_data;

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
   * {@inheritdoc}
   */
  public static function configurationDefinition(): PropertyDefinition {
    return PropertyDefinition::create('complex')
      ->setLabel("Module settings")
      ->setProperties([
        'service_namespace' => PropertyDefinition::create('string')
          ->setLabel("Service class namespace")
          ->setDescription("The relative namespace within a module in which to place services. Do not include '\\' at either end. Leave empty to place in the module namespace.")
          ->setLiteralDefault(''),
        'service_linebreaks' => PropertyDefinition::create('boolean')
          ->setLabel("Linebreak between service definitions")
          ->setDescription("Whether to put a blank line between each service definition in a services.yml file.")
          ->setLiteralDefault(FALSE),
        'service_parameters_linebreaks' => PropertyDefinition::create('boolean')
          ->setLabel("Service parameters on separate lines")
          ->setDescription("Whether to put service parameters in a services.yml file each on their own line.")
          ->setLiteralDefault(FALSE),
        'entity_handler_namespace' => PropertyDefinition::create('string')
          ->setLabel("Entity handler class namespace")
          ->setDescription("The relative namespace within a module in which to place entity handlers. Do not include '\\' at either end. Leave empty to place in the module namespace.")
          ->setLiteralDefault('Entity\Handler'),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->getProperty('root_name')
      ->setLabel('Module machine name')
      ->setLiteralDefault('my_module');

    $definition->addProperties([
      'base' => PropertyDefinition::create('string')
        ->setInternal(TRUE)
        ->setLiteralDefault('module')
        ->setRequired(TRUE),
      // TODO: move to RootComponent.
      'readable_name' => PropertyDefinition::create('string')
        ->setLabel('Module readable name')
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("machineToLabel(get('..:root_name'))")
            ->setDependencies('..:root_name')
        ),
      'short_description' => PropertyDefinition::create('string')
        ->setLabel('Module .info file description')
        ->setLiteralDefault('TODO: Description of module')
        ->setRequired(TRUE),
      'module_package' => PropertyDefinition::create('string')
        ->setLabel('Module .info file package')
        ->setLiteralDefault(''),
      'module_dependencies' => PropertyDefinition::create('string')
        ->setLabel('Module dependencies')
        ->setDescription('The machine names of the modules this module should have as dependencies.')
        ->setMultiple(TRUE)
        // We need a value for this, as other generators acquire it.
        ->setLiteralDefault([]),
      // If this is given, then hook_help() is automatically added to the list
      // of required hooks.
      'module_help_text' => PropertyDefinition::create('string')
        ->setLabel('Module help text')
        ->setDescription('The text to show on the site help page. This automatically adds hook_help().'),
      'settings_form' => static::getLazyDataDefinitionForGeneratorType('AdminSettingsForm')
        ->setLabel("Admin settings form")
        ->setDescription("A form for setting the module's general settings. Also produces a permission and a router item."),
      'forms' => static::getLazyDataDefinitionForGeneratorType('Form')
        ->setLabel("Forms")
        ->setDescription("The forms for this module to provide.")
        ->setMultiple(TRUE),
      'services' => static::getLazyDataDefinitionForGeneratorType('Service')
        ->setLabel("Services")
        ->setDescription('The services for this module to provide.')
        ->setMultiple(TRUE),
      'service_provider' => static::getLazyDataDefinitionForGeneratorType('ServiceProvider', 'boolean')
        ->setLabel("Service provider")
        ->setDescription('A service provider alters existing services or defines services dynamically.'),
      'permissions' => static::getLazyDataDefinitionForGeneratorType('Permission')
        ->setLabel("Permissions")
        ->setDescription('The permissions for this module to provide.')
        ->setMultiple(TRUE),
      // 'module_hook_presets' => PropertyDefinition::create('string')
      //   ->setLabel( 'Hook preset groups')
      //   ->setMultiple(TRUE)
      //   'required' => FALSE,
      //   'format' => 'array',
      //   'options' => function(&$property_info) {
      //     /// ARGH how to make format that is good for both UI and drush?
      //     $mb_task_handler_report_presets = \DrupalCodeBuilder\Factory::getTask('ReportHookPresets');
      //     $hook_presets = $mb_task_handler_report_presets->getHookPresets();

      //     // Stash the hook presets in the property info so the processing
      //     // callback doesn't have to repeat the work.
      //     // TODO: this is not working! See the processing callback.
      //     $property_info['_presets'] = $hook_presets;

      //     $options = [];
      //     foreach ($hook_presets as $name => $info) {
      //       $options[$name] = $info['label'];
      //     }
      //     return $options;
      //   },
      //   // The processing callback alters the component data in place, and may
      //   // in fact alter another value.
      //   // TODO: restore this as validation!
      //   'XXprocessing' => function($value, &$component_data, $property_name, &$property_info) {
      //     // TODO: the options aren't there, as generateComponent() only gets
      //     // given data, not the component info array. However, it's probably
      //     // better to re-compute these lazily rather than do them all.
      //     $mb_task_handler_report_presets = \DrupalCodeBuilder\Factory::getTask('ReportHookPresets');
      //     $hook_presets = $mb_task_handler_report_presets->getHookPresets();

      //     foreach ($value as $given_preset_name) {
      //       if (!isset($hook_presets[$given_preset_name])) {
      //         throw new \DrupalCodeBuilder\Exception\InvalidInputException(strtr("Undefined hook preset group !name", [
      //           '!name' => htmlspecialchars($given_preset_name, ENT_QUOTES, 'UTF-8'),
      //         ]));
      //       }
      //       // DX: check the preset is properly defined.
      //       if (!is_array($hook_presets[$given_preset_name]['hooks'])) {
      //         throw new \DrupalCodeBuilder\Exception\InvalidInputException(strtr("Incorrectly defined hook preset group !name", [
      //           '!name' => htmlspecialchars($given_preset_name, ENT_QUOTES, 'UTF-8'),
      //         ]));
      //       }

      //       // Add the preset hooks list to the hooks array in the component
      //       // data. The 'hooks' property processing will handle them.
      //       $hooks = $hook_presets[$given_preset_name]['hooks'];
      //       $component_data['hooks'] = array_merge($component_data['hooks'], $hooks);
      //       //drush_print_r($component_data['hooks']);
      //     }
      //   },
      // ],
      'hooks' => PropertyDefinition::create('string')
        ->setLabel('Hook implementations')
        ->setMultiple(TRUE)
        ->setOptions(...array_map(
          function($hook_data_item) {
            return OptionDefinition::create(
              $hook_data_item['name'],
              $hook_data_item['name'],
              $hook_data_item['description'] ?? ''
            );
          },
          // ARGH PITA that we have to zap out the keys here for the splat to
          // work!
          // TODO: make the API actually be what we need here!
          array_values(\DrupalCodeBuilder\Factory::getTask('ReportHookData')->getHookDeclarations())
        )),
      //   // TODO: restore this as validation.
      //   'XXprocessing' => function($value, &$component_data, $property_name, &$property_info) {
      //     $mb_task_handler_report_hooks = \DrupalCodeBuilder\Factory::getTask('ReportHookData');
      //     // Get the flat list of hooks, standardized to lower case.
      //     $hook_definitions = array_change_key_case($mb_task_handler_report_hooks->getHookDeclarations());

      //     $hooks = array();
      //     foreach ($component_data['hooks'] as $hook_name) {
      //       // Standardize to lowercase.
      //       $hook_name = strtolower($hook_name);

      //       // By default, accept the short definition of hooks, ie 'boot' for 'hook_boot'.
      //       if (isset($hook_definitions["hook_$hook_name"])) {
      //         $hooks["hook_$hook_name"] = TRUE;
      //       }
      //       // Also fall back to allowing full names. This is handy if you're copy-pasting
      //       // from an existing module and want the same hooks.
      //       // In theory there won't be any clashes; only hook_hook_info() is weird.
      //       elseif (isset($hook_definitions[$hook_name])) {
      //         $hooks[$hook_name] = TRUE;
      //       }
      //     }

      //     // Filter out empty values, in case Drupal UI forms haven't done so.
      //     $hooks = array_filter($hooks);

      //     // Set the processed hooks list back into the component data.
      //     $component_data['hooks'] = $hooks;
      //   }
      // ),
      'content_entity_types' => static::getLazyDataDefinitionForGeneratorType('ContentEntityType')
        ->setLabel('Content entity types')
        ->setMultiple(TRUE),
      'config_entity_types' => static::getLazyDataDefinitionForGeneratorType('ConfigEntityType')
        ->setLabel('Config entity types')
        ->setMultiple(TRUE),
      'plugins' => static::getLazyDataDefinitionForGeneratorType('Plugin')
        ->setLabel('Plugins')
        ->setMultiple(TRUE),
      'plugin_types' => static::getLazyDataDefinitionForGeneratorType('PluginType')
        ->setLabel('Plugin types')
        ->setMultiple(TRUE),
      'theme_hooks' => static::getLazyDataDefinitionForGeneratorType('ThemeHook', 'string')
        ->setLabel("Theme hooks")
        ->setDescription("The name of theme hooks, without the leading 'theme_'.")
        ->setMultiple(TRUE),
      'router_items' => static::getLazyDataDefinitionForGeneratorType('RouterItem')
        ->setLabel("Routes")
        ->setMultiple(TRUE),
      'library' => static::getLazyDataDefinitionForGeneratorType('Library')
        ->setLabel('Libraries')
        ->setDescription("A collection of CSS and JS assets, declared in a libraries.yml file.")
        ->setMultiple(TRUE),
      'drush_commands' => static::getLazyDataDefinitionForGeneratorType('DrushCommand')
        ->setLabel("Drush commands")
        ->setMultiple(TRUE),
      'api' => static::getLazyDataDefinitionForGeneratorType('API', 'boolean')
        ->setLabel("api.php file")
        ->setDescription('An api.php file documents hooks and callbacks that this module invents.'),
      'readme' => static::getLazyDataDefinitionForGeneratorType('Readme', 'boolean')
        ->setLabel("README file")
        ->setLiteralDefault(TRUE),
      // 'phpunit_tests' go here, but can't be added at this point because it
      // would cause circularity with TestModule.
      // TODO: lazy load generator type property definitions?

      // The following defaults are for ease of developing.
      // Uncomment them to reduce the amount of typing needed for testing.
      //'hooks' => 'init',
      //'router_items' => 'path/foo path/bar',
      // The following properties shouldn't be offered as UI options.
      'camel_case_name' => PropertyDefinition::create('string')
        ->setLabel('Module human-readable name')
        ->setInternal(TRUE)
        ->setRequired(TRUE)
        ->setDefault(
          DefaultDefinition::create()
            ->setExpression("machineToClass(get('..:root_name'))")
            ->setDependencies('..:root_name')
        ),
    ]);

    return $definition;
  }

  /**
   * Alter the definition.
   *
   * This is just to allow easy skipping of this by TestModule.
   *
   * @param \DrupalCodeBuilder\Definition\PropertyDefinition $definition
   *   The definition from this class.
   */
  public static function rootComponentPropertyDefinitionAlter(PropertyDefinition $definition): void {
    $definition
      ->setLabel('Module')
      ->setName('module');

    $definition->addPropertyAfter('readme', static::getLazyDataDefinitionForGeneratorType('PHPUnitTest')
      ->setName('phpunit_tests')
      ->setLabel("PHPUnit test case class")
      ->setMultiple(TRUE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = [];

    // Modules always have a .info file.
    // TODO: this was an experiment for how to do required components with
    // DataItems and it's not very nice DX. Figure out a better way.
    $definition = $this->classHandler->getStandaloneComponentPropertyDefinition('Info');

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);
    $components['info'] = $data;

    // $components['info'] = [
    //   'component_type' => 'Info',
    // ];

    // Turn the hooks property into the Hooks component.
    if (!$this->component_data->hooks->isEmpty()) {
      $components['hooks'] = [
        'component_type' => 'Hooks',
        'hooks' => $this->component_data['hooks'],
      ];
    }

    // Add hook_help if help text is given.
    // TODO dirty hack because TestModule child class doesn't have this
    // property!
    if ($this->component_data->hasProperty('module_help_text') && !$this->component_data->module_help_text->isEmpty()) {
      if (isset($components['hooks'])) {
        // TODO: needs test!
        $components['hooks']['hooks'][] = 'hook_help';
      }
      else {
        $components['hooks'] = [
          'component_type' => 'Hooks',
          'hooks' => [
            'hook_help',
          ],
        ];
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

    return [
      '%module'       => $module_data['root_name'],
      '%readable'     => str_replace("'", "\'", $module_data->readable_name->value),
      '%Module'       => CaseString::title($module_data['readable_name'])->title(),
      '%sentence'     => CaseString::title($module_data['readable_name'])->sentence(),
      '%lower'        => strtolower($module_data['readable_name']),
      '%description'  => str_replace("'", "\'", $module_data['short_description']),
      '%help'         => !empty($module_data['module_help_text']) ? str_replace('"', '\"', $module_data['module_help_text']) : 'TODO: Create admin help text.',
    ];
  }

}
