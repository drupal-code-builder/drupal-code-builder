<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\DeferredGeneratorDefinition;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
use DrupalCodeBuilder\File\DrupalExtension;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use MutableTypedData\Data\DataItem;
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
   * {@inheritdoc}
   */
  const BASE = 'module';

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
   * {@inheritdoc}
   */
  public static function configurationDefinition(): PropertyDefinition {
    return PropertyDefinition::create('complex')
      ->setLabel("Module settings")
      ->setProperties([
        'property_promotion' => PropertyDefinition::create('boolean')
          ->setLabel("Use constructor property promotion")
          ->setDescription('This is <a href="https://www.drupal.org/project/drupal/issues/3278431">proposed as an addition</a> to Drupal coding standards.')
          ->setLiteralDefault(FALSE),
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
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition
      ->setLabel('Module');

    $definition->getProperty('root_name')
      ->setLabel('Module machine name')
      ->setLiteralDefault('my_module');

    $definition->addProperties([
      // The human readable name for the module.
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
      // A string of module dependencies, separated by spaces, e.g. 'forum
      // views'.
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
      'settings_form' => MergingGeneratorDefinition::createFromGeneratorType('AdminSettingsForm')
        ->setLabel("Admin settings form")
        ->setDescription("A form for setting the module's general settings. Also produces a permission and a router item."),
      'forms' => MergingGeneratorDefinition::createFromGeneratorType('Form')
        ->setLabel("Forms")
        ->setDescription("The forms for this module to provide.")
        ->setMultiple(TRUE),
      'services' => MergingGeneratorDefinition::createFromGeneratorType('Service')
        ->setLabel("Services")
        ->setDescription('The services for this module to provide.')
        ->setMultiple(TRUE),
      'event_subscribers' => MergingGeneratorDefinition::createFromGeneratorType('ServiceEventSubscriber')
        ->setLabel("Event subscribers")
        ->setDescription('Services that subscribe to events.')
        ->setMultiple(TRUE),
      'events' => MergingGeneratorDefinition::createFromGeneratorType('Event')
        ->setLabel("Events")
        ->setDescription('Events that can be subscribed to.')
        ->setMultiple(TRUE),
      'service_provider' => DeferredGeneratorDefinition::createFromGeneratorType('ServiceProvider', 'boolean')
        ->setLabel("Service provider")
        ->setDescription('A service provider alters existing services or defines services dynamically.'),
      'permissions' => MergingGeneratorDefinition::createFromGeneratorType('Permission')
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
      // TODO: When procedural hooks are removed from core, this property will
      // need to be removed, and so the Module class won't have it, but
      // Module11 (or whatever) will add it in.
      'hook_implementation_type' => PropertyDefinition::create('string')
        ->setLabel("Hook implementation type")
        ->setDescription("The type of hook implementation to generate.")
        ->setRequired(TRUE)
        ->setOptionsArray([
          'procedural' => 'Functions in procedural files, such as .module',
          'oo' => 'Class methods on a Hooks class',
          'oo_legacy' => 'Both types, with legacy support for Drupal core < 11.1',
        ])
        ->setLiteralDefault('oo_legacy'),
      'hook_classes' => MergingGeneratorDefinition::createFromGeneratorType('HooksClass')
        ->setLabel('Hook classes')
        ->setDescription('Classes that hold hook implementation methods. Will also generate legacy procedural functions if Hook implementation type is set to do so.')
        ->setMultiple(TRUE),
      'hooks' => PropertyDefinition::create('string')
        ->setLabel('Hook implementations')
        ->setMultiple(TRUE)
        ->setOptionSetDefinition(\DrupalCodeBuilder\Factory::getTask('ReportHookData')),
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
      'content_entity_types' => MergingGeneratorDefinition::createFromGeneratorType('ContentEntityType')
        ->setLabel('Content entity types')
        ->setMultiple(TRUE),
      'config_entity_types' => MergingGeneratorDefinition::createFromGeneratorType('ConfigEntityType')
        ->setLabel('Config entity types')
        ->setMultiple(TRUE),
      'plugins' => MergingGeneratorDefinition::createFromGeneratorType('Plugin')
        ->setLabel('Plugins')
        ->setMultiple(TRUE),
      'plugin_types' => MergingGeneratorDefinition::createFromGeneratorType('PluginType')
        ->setLabel('Plugin types')
        ->setMultiple(TRUE),
      'theme_hooks' => DeferredGeneratorDefinition::createFromGeneratorType('ThemeHook', 'string')
        ->setLabel("Theme hooks")
        ->setDescription("The name of theme hooks, without the leading 'theme_'.")
        ->setMultiple(TRUE),
      'router_items' => MergingGeneratorDefinition::createFromGeneratorType('RouterItem')
        ->setLabel("Routes")
        ->setMultiple(TRUE),
      'dynamic_routes' =>  MergingGeneratorDefinition::createFromGeneratorType('DynamicRouteProvider')
        ->setLabel('Dynamic route providers')
        ->setMultiple(TRUE),
      'library' => MergingGeneratorDefinition::createFromGeneratorType('Library')
        ->setLabel('Libraries')
        ->setDescription("A collection of CSS and JS assets, declared in a libraries.yml file.")
        ->setMultiple(TRUE),
      'drush_commands' => MergingGeneratorDefinition::createFromGeneratorType('DrushCommand')
        ->setLabel("Drush commands")
        ->setMultiple(TRUE),
      'api' => DeferredGeneratorDefinition::createFromGeneratorType('API', 'boolean')
        ->setLabel("api.php file")
        ->setDescription("An api.php file documents hooks and callbacks that this module invents. This will detect any hook invocations in this module's existing code."),
      'readme' => DeferredGeneratorDefinition::createFromGeneratorType('Readme', 'boolean')
        ->setLabel("README file")
        ->setLiteralDefault(TRUE),
      'phpunit_tests' => MergingGeneratorDefinition::createFromGeneratorType('PHPUnitTest')
        ->setLabel("PHPUnit test case class")
        ->setMultiple(TRUE),
      // 'phpunit_tests' go here, but can't be added at this point because it
      // would cause circularity with TestModule.
      // TODO: lazy load generator type property definitions?
    ]);
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

    $definition->addPropertyAfter('readme', MergingGeneratorDefinition::createFromGeneratorType('PHPUnitTest')
      ->setName('phpunit_tests')
      ->setLabel("PHPUnit test case class")
      ->setMultiple(TRUE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function adoptRootComponent(DrupalExtension $existing_extension): DataItem {
    $info_file_data = $existing_extension->getFileYaml($existing_extension->name . '.info.yml');

    $value = [
      'base' => $existing_extension->type,
      'root_name' => $existing_extension->name,
      'readable_name' => $info_file_data['name'],
      'short_description' => $info_file_data['description'] ?? '',
      'module_package' => $info_file_data['package'] ?? '',
      'module_dependencies' => $info_file_data['dependencies'] ?? [],
      'lifecycle' => $info_file_data['lifecycle'] ?? NULL,
    ];

    $data = DrupalCodeBuilderDataItemFactory::createFromProvider(static::class);

    $data->import($value);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    // Modules always have a .info file.
    $components['info'] = [
      'component_type' => 'InfoModule',
    ];

    // Turn the hooks property into the Hooks component. We add this even if no
    // hooks were selected in the input data, because other components may
    // request hooks, and requesting the component here ensures it has a value
    // for hook_implementation_type, which deeper requesters won't have. The
    // requester's Hooks component will merge with this one, and because it
    // won't have a value for 'hook_implementation_type', the merge will take
    // the value from here.
    $components['hooks'] = [
      'component_type' => 'Hooks',
      'hooks' => $this->component_data->hooks->values(),
      'hook_implementation_type' => $this->component_data->hook_implementation_type->value,
    ];

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
          'hook_implementation_type' => $this->component_data->hook_implementation_type->value,
        ];
      }
    }

    // Add a section to the README about dependencies.
    // Skip this in TestModule child class, which doesn't have the readme
    // property.
    if ($this->component_data->hasProperty('readme')) {
      $contrib_dependencies = [];
      foreach ($this->component_data->module_dependencies as $dependency) {
        if (str_contains($dependency->value, ':') && !str_starts_with($dependency->value, 'drupal:')) {
          [$dependency_project, ] = explode(':', $dependency->value);
          // TODO: We can't feasibly fetch the project name from Drupal. Attempt
          // to get a module readable name if the module exists locally.
          // Key by project in case there are dependencies on multiple modules
          // in the same d.org project.
          $contrib_dependencies[$dependency_project] = "- [$dependency_project](https://www.drupal.org/project/$dependency_project)";
        }
      }

      if ($contrib_dependencies) {
        $text = [
          'This module requires the following modules:',
          '',
        ];
        $text = array_merge($text, array_values($contrib_dependencies));
        $text[] = '';
      }
      else {
        $text = [
          'This module requires no modules outside of Drupal core.',
          '',
        ];
      }

      // Force a README if there are dependencies to document there. Otherwise,
      // only say there are no dependencies if a README has been set.
      if ($contrib_dependencies || $this->component_data->readme->value) {
        $components['readme_dependencies'] = [
          'component_type' => 'ReadmeSection',
          'title' => 'Requirements',
          'text' => $text,
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
      '%base'         => $module_data->base->value,
      // Deprecated; remove.
      '%module'       => $module_data['root_name'],
      '%extension'    => $module_data->root_name->value,
      '%readable'     => str_replace("'", "\'", $module_data->readable_name->value),
      '%Module'       => CaseString::title($module_data['readable_name'])->title(),
      '%sentence'     => CaseString::title($module_data['readable_name'])->sentence(),
      '%lower'        => strtolower($module_data['readable_name']),
      '%Pascal'       => CaseString::snake($module_data->root_name->value)->pascal(),
      '%description'  => str_replace("'", "\'", $module_data['short_description']),
      '%help'         => !empty($module_data['module_help_text']) ? str_replace('"', '\"', $module_data['module_help_text']) : 'TODO: Create admin help text.',
    ];
  }

}
