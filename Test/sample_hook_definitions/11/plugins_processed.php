<?php $data =
array (
  'block' => 
  array (
    'type_id' => 'block',
    'service_id' => 'plugin.manager.block',
    'service_class_name' => 'Drupal\\Core\\Block\\BlockManager',
    'service_component_namespace' => 'Drupal\\Core\\Block',
    'type_label' => 'block',
    'alter_hook_name' => 'block_alter',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\AttributeDiscoveryWithAnnotations',
    'subdir' => 'Plugin/Block',
    'plugin_interface' => 'Drupal\\Core\\Block\\BlockPluginInterface',
    'plugin_definition_annotation_name' => 'Drupal\\Core\\Block\\Annotation\\Block',
    'plugin_definition_attribute_name' => 'Drupal\\Core\\Block\\Attribute\\Block',
    'yaml_file_suffix' => NULL,
    'annotation_id_only' => false,
    'plugin_label_property' => 'admin_label',
    'yaml_properties' => 
    array (
    ),
    'base_class' => 'Drupal\\Core\\Block\\BlockBase',
    'base_class_has_di' => false,
    'config_schema_prefix' => 'block.settings.',
    'plugin_properties' => 
    array (
      'id' => 
      array (
        'name' => 'id',
        'description' => 'The plugin ID.',
        'type' => 'string',
      ),
      'admin_label' => 
      array (
        'name' => 'admin_label',
        'description' => 'The administrative label of the block.',
        'type' => '\\Drupal\\Core\\StringTranslation\\TranslatableMarkup',
      ),
      'category' => 
      array (
        'name' => 'category',
        'description' => '(optional) The category in the admin UI where the block will be listed.',
        'type' => '\\Drupal\\Core\\StringTranslation\\TranslatableMarkup',
      ),
      'context_definitions' => 
      array (
        'name' => 'context_definitions',
        'description' => '(optional) An array of context definitions describing the context used by the plugin. The array is keyed by context names.',
        'type' => 'array',
      ),
      'deriver' => 
      array (
        'name' => 'deriver',
        'description' => '(optional) The deriver class.',
        'type' => 'string',
      ),
      'forms' => 
      array (
        'name' => 'forms',
        'description' => '(optional) An array of form class names keyed by a string.',
        'type' => 'array',
      ),
    ),
    'plugin_interface_methods' => 
    array (
      'label' => 
      array (
        'name' => 'label',
        'declaration' => 'public function label();',
        'description' => 'Returns the user-facing block label.',
      ),
      'access' => 
      array (
        'name' => 'access',
        'declaration' => 'public function access(\\Drupal\\Core\\Session\\AccountInterface $account, $return_as_object = FALSE);',
        'description' => 'Indicates whether the block should be shown.',
      ),
      'build' => 
      array (
        'name' => 'build',
        'declaration' => 'public function build();',
        'description' => 'Builds and returns the renderable array for this block plugin.',
      ),
      'setConfigurationValue' => 
      array (
        'name' => 'setConfigurationValue',
        'declaration' => 'public function setConfigurationValue($key, $value);',
        'description' => 'Sets a particular value in the block settings.',
      ),
      'blockForm' => 
      array (
        'name' => 'blockForm',
        'declaration' => 'public function blockForm($form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Returns the configuration form elements specific to this block plugin.',
      ),
      'blockValidate' => 
      array (
        'name' => 'blockValidate',
        'declaration' => 'public function blockValidate($form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Adds block type-specific validation for the block form.',
      ),
      'blockSubmit' => 
      array (
        'name' => 'blockSubmit',
        'declaration' => 'public function blockSubmit($form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Adds block type-specific submission handling for the block form.',
      ),
      'getMachineNameSuggestion' => 
      array (
        'name' => 'getMachineNameSuggestion',
        'declaration' => 'public function getMachineNameSuggestion();',
        'description' => 'Suggests a machine name to identify an instance of this block.',
      ),
      'buildConfigurationForm' => 
      array (
        'name' => 'buildConfigurationForm',
        'declaration' => 'public function buildConfigurationForm(array $form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Form constructor.',
      ),
      'validateConfigurationForm' => 
      array (
        'name' => 'validateConfigurationForm',
        'declaration' => 'public function validateConfigurationForm(array &$form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Form validation handler.',
      ),
      'submitConfigurationForm' => 
      array (
        'name' => 'submitConfigurationForm',
        'declaration' => 'public function submitConfigurationForm(array &$form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Form submission handler.',
      ),
      'getCacheContexts' => 
      array (
        'name' => 'getCacheContexts',
        'declaration' => 'public function getCacheContexts();',
        'description' => 'The cache contexts associated with this object.',
      ),
      'getCacheTags' => 
      array (
        'name' => 'getCacheTags',
        'declaration' => 'public function getCacheTags();',
        'description' => 'The cache tags associated with this object.',
      ),
      'getCacheMaxAge' => 
      array (
        'name' => 'getCacheMaxAge',
        'declaration' => 'public function getCacheMaxAge();',
        'description' => 'The maximum age for which this object may be cached.',
      ),
    ),
  ),
  'element_info' => 
  array (
    'type_id' => 'element_info',
    'service_id' => 'plugin.manager.element_info',
    'service_class_name' => 'Drupal\\Core\\Render\\ElementInfoManager',
    'service_component_namespace' => 'Drupal\\Core\\Render',
    'type_label' => 'element_info',
    'alter_hook_name' => 'element_plugin_alter',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\AttributeDiscoveryWithAnnotations',
    'subdir' => 'Element',
    'plugin_interface' => 'Drupal\\Core\\Render\\Element\\ElementInterface',
    'plugin_definition_annotation_name' => 'Drupal\\Core\\Render\\Annotation\\RenderElement',
    'plugin_definition_attribute_name' => 'Drupal\\Core\\Render\\Attribute\\RenderElement',
    'yaml_file_suffix' => NULL,
    'annotation_id_only' => true,
    'plugin_label_property' => NULL,
    'yaml_properties' => 
    array (
    ),
    'base_class' => 'Drupal\\Core\\Render\\Element\\RenderElementBase',
    'base_class_has_di' => false,
    'plugin_properties' => 
    array (
      'id' => 
      array (
        'name' => 'id',
        'description' => 'The attribute class ID.',
        'type' => 'string',
      ),
      'deriver' => 
      array (
        'name' => 'deriver',
        'description' => '(optional) The deriver class.',
        'type' => 'string',
      ),
    ),
    'plugin_interface_methods' => 
    array (
      'getInfo' => 
      array (
        'name' => 'getInfo',
        'declaration' => 'public function getInfo();',
        'description' => 'Returns the element properties for this element.',
      ),
      'setAttributes' => 
      array (
        'name' => 'setAttributes',
        'declaration' => 'public static function setAttributes(&$element, $class = []);',
        'description' => 'Sets a form element\'s class attribute.',
      ),
    ),
  ),
  'field.formatter' => 
  array (
    'type_id' => 'field.formatter',
    'service_id' => 'plugin.manager.field.formatter',
    'service_class_name' => 'Drupal\\Core\\Field\\FormatterPluginManager',
    'service_component_namespace' => 'Drupal\\Core\\Field',
    'type_label' => 'field.formatter',
    'alter_hook_name' => 'field_formatter_info_alter',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\AttributeDiscoveryWithAnnotations',
    'subdir' => 'Plugin/Field/FieldFormatter',
    'plugin_interface' => 'Drupal\\Core\\Field\\FormatterInterface',
    'plugin_definition_annotation_name' => 'Drupal\\Core\\Field\\Annotation\\FieldFormatter',
    'plugin_definition_attribute_name' => 'Drupal\\Core\\Field\\Attribute\\FieldFormatter',
    'yaml_file_suffix' => NULL,
    'annotation_id_only' => false,
    'plugin_label_property' => 'label',
    'yaml_properties' => 
    array (
    ),
    'base_class' => 'Drupal\\Core\\Field\\FormatterBase',
    'base_class_has_di' => true,
    'config_schema_prefix' => 'field.formatter.settings.',
    'constructor_fixed_parameters' => 
    array (
      0 => 
      array (
        'extraction' => '$plugin_id',
        'type' => 'string',
        'name' => 'plugin_id',
      ),
      1 => 
      array (
        'extraction' => '$plugin_definition',
        'type' => 'mixed',
        'name' => 'plugin_definition',
      ),
      2 => 
      array (
        'extraction' => '$configuration[\'field_definition\']',
        'type' => 'Drupal\\Core\\Field\\FieldDefinitionInterface',
        'name' => 'field_definition',
      ),
      3 => 
      array (
        'extraction' => '$configuration[\'settings\']',
        'type' => 'array',
        'name' => 'settings',
      ),
      4 => 
      array (
        'extraction' => '$configuration[\'label\']',
        'type' => 'string',
        'name' => 'label',
      ),
      5 => 
      array (
        'extraction' => '$configuration[\'view_mode\']',
        'type' => 'string',
        'name' => 'view_mode',
      ),
      6 => 
      array (
        'extraction' => '$configuration[\'third_party_settings\']',
        'type' => 'array',
        'name' => 'third_party_settings',
      ),
    ),
    'plugin_properties' => 
    array (
      'id' => 
      array (
        'name' => 'id',
        'description' => 'The plugin ID.',
        'type' => 'string',
      ),
      'label' => 
      array (
        'name' => 'label',
        'description' => '(optional) The human-readable name of the formatter type.',
        'type' => '\\Drupal\\Core\\StringTranslation\\TranslatableMarkup',
      ),
      'description' => 
      array (
        'name' => 'description',
        'description' => '(optional) A short description of the formatter type.',
        'type' => '\\Drupal\\Core\\StringTranslation\\TranslatableMarkup',
      ),
      'field_types' => 
      array (
        'name' => 'field_types',
        'description' => '(optional) An array of field types the formatter supports.',
        'type' => 'array',
      ),
      'weight' => 
      array (
        'name' => 'weight',
        'description' => '(optional) An integer to determine the weight of this formatter. Weight is relative to other formatters in the Field UI when selecting a formatter for a given field instance.',
        'type' => 'int',
      ),
      'deriver' => 
      array (
        'name' => 'deriver',
        'description' => '(optional) The deriver class.',
        'type' => 'string',
      ),
    ),
    'plugin_interface_methods' => 
    array (
      'settingsForm' => 
      array (
        'name' => 'settingsForm',
        'declaration' => 'public function settingsForm(array $form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Returns a form to configure settings for the formatter.',
      ),
      'settingsSummary' => 
      array (
        'name' => 'settingsSummary',
        'declaration' => 'public function settingsSummary();',
        'description' => 'Returns a short summary for the current formatter settings.',
      ),
      'prepareView' => 
      array (
        'name' => 'prepareView',
        'declaration' => 'public function prepareView(array $entities_items);',
        'description' => 'Allows formatters to load information for field values being displayed.',
      ),
      'view' => 
      array (
        'name' => 'view',
        'declaration' => 'public function view(\\Drupal\\Core\\Field\\FieldItemListInterface $items, $langcode = NULL);',
        'description' => 'Builds a renderable array for a fully themed field.',
      ),
      'viewElements' => 
      array (
        'name' => 'viewElements',
        'declaration' => 'public function viewElements(\\Drupal\\Core\\Field\\FieldItemListInterface $items, $langcode);',
        'description' => 'Builds a renderable array for a field value.',
      ),
      'isApplicable' => 
      array (
        'name' => 'isApplicable',
        'declaration' => 'public static function isApplicable(\\Drupal\\Core\\Field\\FieldDefinitionInterface $field_definition);',
        'description' => 'Returns if the formatter can be used for the provided field.',
      ),
      'defaultSettings' => 
      array (
        'name' => 'defaultSettings',
        'declaration' => 'public static function defaultSettings();',
        'description' => 'Defines the default settings for this plugin.',
      ),
      'getSettings' => 
      array (
        'name' => 'getSettings',
        'declaration' => 'public function getSettings();',
        'description' => 'Returns the array of settings, including defaults for missing settings.',
      ),
      'getSetting' => 
      array (
        'name' => 'getSetting',
        'declaration' => 'public function getSetting($key);',
        'description' => 'Returns the value of a setting, or its default value if absent.',
      ),
      'setSettings' => 
      array (
        'name' => 'setSettings',
        'declaration' => 'public function setSettings(array $settings);',
        'description' => 'Sets the settings for the plugin.',
      ),
      'setSetting' => 
      array (
        'name' => 'setSetting',
        'declaration' => 'public function setSetting($key, $value);',
        'description' => 'Sets the value of a setting for the plugin.',
      ),
      'onDependencyRemoval' => 
      array (
        'name' => 'onDependencyRemoval',
        'declaration' => 'public function onDependencyRemoval(array $dependencies);',
        'description' => 'Informs the plugin that some configuration it depends on will be deleted.',
      ),
    ),
  ),
  'filter' => 
  array (
    'type_id' => 'filter',
    'service_id' => 'plugin.manager.filter',
    'service_class_name' => 'Drupal\\filter\\FilterPluginManager',
    'service_component_namespace' => 'Drupal\\filter',
    'type_label' => 'filter',
    'alter_hook_name' => 'filter_info_alter',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\AttributeDiscoveryWithAnnotations',
    'subdir' => 'Plugin/Filter',
    'plugin_interface' => 'Drupal\\filter\\Plugin\\FilterInterface',
    'plugin_definition_annotation_name' => 'Drupal\\filter\\Annotation\\Filter',
    'plugin_definition_attribute_name' => 'Drupal\\filter\\Attribute\\Filter',
    'yaml_file_suffix' => NULL,
    'annotation_id_only' => false,
    'plugin_label_property' => 'title',
    'yaml_properties' => 
    array (
    ),
    'base_class' => 'Drupal\\filter\\Plugin\\FilterBase',
    'base_class_has_di' => false,
    'config_schema_prefix' => 'filter_settings.',
    'plugin_properties' => 
    array (
      'id' => 
      array (
        'name' => 'id',
        'description' => 'The plugin ID.',
        'type' => 'string',
      ),
      'title' => 
      array (
        'name' => 'title',
        'description' => 'The human-readable name of the filter. This is used as an administrative summary of what the filter does.',
        'type' => '\\Drupal\\Core\\StringTranslation\\TranslatableMarkup',
      ),
      'type' => 
      array (
        'name' => 'type',
        'description' => 'The filter type. Values are defined in \\Drupal\\filter\\Plugin\\FilterInterface.',
        'type' => 'int',
      ),
      'description' => 
      array (
        'name' => 'description',
        'description' => '(optional) Additional administrative information about the filter\'s behavior.',
        'type' => '\\Drupal\\Core\\StringTranslation\\TranslatableMarkup',
      ),
      'weight' => 
      array (
        'name' => 'weight',
        'description' => '(optional) A default weight for the filter in new text formats.',
        'type' => 'int',
      ),
      'status' => 
      array (
        'name' => 'status',
        'description' => '(optional) Whether this filter is enabled or disabled by default.',
        'type' => 'bool',
      ),
      'settings' => 
      array (
        'name' => 'settings',
        'description' => '(optional) The default settings for the filter.',
        'type' => 'array',
      ),
      'deriver' => 
      array (
        'name' => 'deriver',
        'description' => '',
        'type' => 'string',
      ),
    ),
    'plugin_interface_methods' => 
    array (
      'getType' => 
      array (
        'name' => 'getType',
        'declaration' => 'public function getType();',
        'description' => 'Returns the processing type of this filter plugin.',
      ),
      'getLabel' => 
      array (
        'name' => 'getLabel',
        'declaration' => 'public function getLabel();',
        'description' => 'Returns the administrative label for this filter plugin.',
      ),
      'getDescription' => 
      array (
        'name' => 'getDescription',
        'declaration' => 'public function getDescription();',
        'description' => 'Returns the administrative description for this filter plugin.',
      ),
      'settingsForm' => 
      array (
        'name' => 'settingsForm',
        'declaration' => 'public function settingsForm(array $form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Generates a filter\'s settings form.',
      ),
      'prepare' => 
      array (
        'name' => 'prepare',
        'declaration' => 'public function prepare($text, $langcode);',
        'description' => 'Prepares the text for processing.',
      ),
      'process' => 
      array (
        'name' => 'process',
        'declaration' => 'public function process($text, $langcode);',
        'description' => 'Performs the filter processing.',
      ),
      'getHTMLRestrictions' => 
      array (
        'name' => 'getHTMLRestrictions',
        'declaration' => 'public function getHTMLRestrictions();',
        'description' => 'Returns HTML allowed by this filter\'s configuration.',
      ),
      'tips' => 
      array (
        'name' => 'tips',
        'declaration' => 'public function tips($long = FALSE);',
        'description' => 'Generates a filter\'s tip.',
      ),
    ),
  ),
  'image.effect' => 
  array (
    'type_id' => 'image.effect',
    'service_id' => 'plugin.manager.image.effect',
    'service_class_name' => 'Drupal\\image\\ImageEffectManager',
    'service_component_namespace' => 'Drupal\\image',
    'type_label' => 'image.effect',
    'alter_hook_name' => 'image_effect_info_alter',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\AttributeDiscoveryWithAnnotations',
    'subdir' => 'Plugin/ImageEffect',
    'plugin_interface' => 'Drupal\\image\\ImageEffectInterface',
    'plugin_definition_annotation_name' => 'Drupal\\image\\Annotation\\ImageEffect',
    'plugin_definition_attribute_name' => 'Drupal\\image\\Attribute\\ImageEffect',
    'yaml_file_suffix' => NULL,
    'annotation_id_only' => false,
    'plugin_label_property' => 'label',
    'yaml_properties' => 
    array (
    ),
    'base_class' => 'Drupal\\image\\ImageEffectBase',
    'base_class_has_di' => true,
    'config_schema_prefix' => 'image.effect.',
    'construction' => 
    array (
      0 => 
      array (
        'type' => 'Psr\\Log\\LoggerInterface',
        'name' => 'logger',
        'extraction' => '$container->get(\'logger.factory\')->get(\'image\')',
      ),
    ),
    'plugin_properties' => 
    array (
      'id' => 
      array (
        'name' => 'id',
        'description' => 'The plugin ID.',
        'type' => 'string',
      ),
      'label' => 
      array (
        'name' => 'label',
        'description' => 'The human-readable name of the image effect.',
        'type' => '\\Drupal\\Core\\StringTranslation\\TranslatableMarkup',
      ),
      'description' => 
      array (
        'name' => 'description',
        'description' => '(optional) A brief description of the image effect. This will be shown when adding or configuring this image effect.',
        'type' => '\\Drupal\\Core\\StringTranslation\\TranslatableMarkup',
      ),
      'deriver' => 
      array (
        'name' => 'deriver',
        'description' => '(optional) The deriver class.',
        'type' => 'string',
      ),
    ),
    'plugin_interface_methods' => 
    array (
      'applyEffect' => 
      array (
        'name' => 'applyEffect',
        'declaration' => 'public function applyEffect(\\Drupal\\Core\\Image\\ImageInterface $image);',
        'description' => 'Applies an image effect to the image object.',
      ),
      'transformDimensions' => 
      array (
        'name' => 'transformDimensions',
        'declaration' => 'public function transformDimensions(array &$dimensions, $uri);',
        'description' => 'Determines the dimensions of the styled image.',
      ),
      'getDerivativeExtension' => 
      array (
        'name' => 'getDerivativeExtension',
        'declaration' => 'public function getDerivativeExtension($extension);',
        'description' => 'Returns the extension of the derivative after applying this image effect.',
      ),
      'getSummary' => 
      array (
        'name' => 'getSummary',
        'declaration' => 'public function getSummary();',
        'description' => 'Returns a render array summarizing the configuration of the image effect.',
      ),
      'label' => 
      array (
        'name' => 'label',
        'declaration' => 'public function label();',
        'description' => 'Returns the image effect label.',
      ),
      'getUuid' => 
      array (
        'name' => 'getUuid',
        'declaration' => 'public function getUuid();',
        'description' => 'Returns the unique ID representing the image effect.',
      ),
      'getWeight' => 
      array (
        'name' => 'getWeight',
        'declaration' => 'public function getWeight();',
        'description' => 'Returns the weight of the image effect.',
      ),
      'setWeight' => 
      array (
        'name' => 'setWeight',
        'declaration' => 'public function setWeight($weight);',
        'description' => 'Sets the weight for this image effect.',
      ),
    ),
  ),
  'menu.link' => 
  array (
    'type_id' => 'menu.link',
    'service_id' => 'plugin.manager.menu.link',
    'service_class_name' => 'Drupal\\Core\\Menu\\MenuLinkManager',
    'service_component_namespace' => 'Drupal\\Core\\Menu',
    'type_label' => 'menu.link',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\YamlDiscovery',
    'subdir' => NULL,
    'plugin_interface' => NULL,
    'plugin_definition_annotation_name' => NULL,
    'plugin_definition_attribute_name' => NULL,
    'yaml_file_suffix' => 'links.menu',
    'annotation_id_only' => NULL,
    'plugin_label_property' => NULL,
    'yaml_properties' => 
    array (
      'menu_name' => 'tools',
      'route_name' => '',
      'route_parameters' => 
      array (
      ),
      'url' => '',
      'title' => '',
      'description' => '',
      'parent' => '',
      'weight' => 0,
      'options' => 
      array (
      ),
      'expanded' => 0,
      'enabled' => 1,
      'provider' => '',
      'metadata' => 
      array (
      ),
      'class' => 'Drupal\\Core\\Menu\\MenuLinkDefault',
      'form_class' => 'Drupal\\Core\\Menu\\Form\\MenuLinkDefaultForm',
    ),
    'base_class' => 'Drupal\\Core\\Menu\\MenuLinkBase',
    'base_class_has_di' => false,
    'plugin_properties' => 
    array (
    ),
    'plugin_interface_methods' => 
    array (
    ),
  ),
  'menu.local_action' => 
  array (
    'type_id' => 'menu.local_action',
    'service_id' => 'plugin.manager.menu.local_action',
    'service_class_name' => 'Drupal\\Core\\Menu\\LocalActionManager',
    'service_component_namespace' => 'Drupal\\Core\\Menu',
    'type_label' => 'menu.local_action',
    'alter_hook_name' => 'menu_local_actions_alter',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\YamlDiscovery',
    'subdir' => NULL,
    'plugin_interface' => NULL,
    'plugin_definition_annotation_name' => NULL,
    'plugin_definition_attribute_name' => NULL,
    'yaml_file_suffix' => 'links.action',
    'annotation_id_only' => NULL,
    'plugin_label_property' => NULL,
    'yaml_properties' => 
    array (
      'title' => '',
      'weight' => NULL,
      'route_name' => NULL,
      'route_parameters' => 
      array (
      ),
      'options' => 
      array (
      ),
      'appears_on' => 
      array (
      ),
      'class' => 'Drupal\\Core\\Menu\\LocalActionDefault',
    ),
    'base_class' => 'Drupal\\Core\\Menu\\LocalActionDefault',
    'base_class_has_di' => true,
    'construction' => 
    array (
      0 => 
      array (
        'type' => 'Drupal\\Core\\Routing\\RouteProviderInterface',
        'name' => 'route_provider',
        'extraction' => '$container->get(\'router.route_provider\')',
      ),
    ),
    'plugin_properties' => 
    array (
    ),
    'plugin_interface_methods' => 
    array (
    ),
  ),
  'menu.local_task' => 
  array (
    'type_id' => 'menu.local_task',
    'service_id' => 'plugin.manager.menu.local_task',
    'service_class_name' => 'Drupal\\Core\\Menu\\LocalTaskManager',
    'service_component_namespace' => 'Drupal\\Core\\Menu',
    'type_label' => 'menu.local_task',
    'alter_hook_name' => 'local_tasks_alter',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\YamlDiscovery',
    'subdir' => NULL,
    'plugin_interface' => NULL,
    'plugin_definition_annotation_name' => NULL,
    'plugin_definition_attribute_name' => NULL,
    'yaml_file_suffix' => 'links.task',
    'annotation_id_only' => NULL,
    'plugin_label_property' => NULL,
    'yaml_properties' => 
    array (
      'route_name' => '',
      'route_parameters' => 
      array (
      ),
      'title' => '',
      'base_route' => '',
      'parent_id' => NULL,
      'weight' => NULL,
      'options' => 
      array (
      ),
      'class' => 'Drupal\\Core\\Menu\\LocalTaskDefault',
    ),
    'base_class' => 'Drupal\\Core\\Menu\\LocalTaskDefault',
    'base_class_has_di' => false,
    'plugin_properties' => 
    array (
    ),
    'plugin_interface_methods' => 
    array (
    ),
  ),
  'validation.constraint' => 
  array (
    'type_id' => 'validation.constraint',
    'service_id' => 'validation.constraint',
    'service_class_name' => 'Drupal\\Core\\Validation\\ConstraintManager',
    'service_component_namespace' => 'Drupal\\Core\\Validation',
    'type_label' => 'validation.constraint',
    'alter_hook_name' => 'validation_constraint_alter',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\AttributeDiscoveryWithAnnotations',
    'subdir' => 'Plugin/Validation/Constraint',
    'plugin_interface' => '',
    'plugin_definition_annotation_name' => 'Drupal\\Core\\Validation\\Annotation\\Constraint',
    'plugin_definition_attribute_name' => 'Drupal\\Core\\Validation\\Attribute\\Constraint',
    'yaml_file_suffix' => NULL,
    'annotation_id_only' => false,
    'plugin_label_property' => 'label',
    'yaml_properties' => 
    array (
    ),
    'base_class' => 'Symfony\\Component\\Validator\\Constraint',
    'base_class_has_di' => false,
    'plugin_properties' => 
    array (
      'id' => 
      array (
        'name' => 'id',
        'description' => 'The constraint plugin ID.',
        'type' => 'string',
      ),
      'label' => 
      array (
        'name' => 'label',
        'description' => '(optional) The human-readable name of the constraint plugin.',
        'type' => '\\Drupal\\Core\\StringTranslation\\TranslatableMarkup',
      ),
      'type' => 
      array (
        'name' => 'type',
        'description' => '(optional) DataType plugin IDs for which this constraint applies. Valid values are any types registered by the typed data API, or an array of multiple type names. For supporting all types, FALSE may be specified. The key defaults to an empty array, which indicates no types are supported.',
        'type' => 'array|string|false',
      ),
      'deriver' => 
      array (
        'name' => 'deriver',
        'description' => '(optional) The deriver class.',
        'type' => 'string',
      ),
    ),
    'plugin_interface_methods' => 
    array (
    ),
  ),
  'views.area' => 
  array (
    'type_id' => 'views.area',
    'service_id' => 'plugin.manager.views.area',
    'service_class_name' => 'Drupal\\views\\Plugin\\ViewsHandlerManager',
    'service_component_namespace' => 'Drupal\\views',
    'type_label' => 'views.area',
    'alter_hook_name' => 'views_plugins_area_alter',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\AttributeDiscoveryWithAnnotations',
    'subdir' => 'Plugin/views/area',
    'plugin_interface' => 'Drupal\\views\\Plugin\\views\\ViewsHandlerInterface',
    'plugin_definition_annotation_name' => 'Drupal\\views\\Annotation\\ViewsArea',
    'plugin_definition_attribute_name' => 'Drupal\\views\\Attribute\\ViewsArea',
    'yaml_file_suffix' => NULL,
    'annotation_id_only' => true,
    'plugin_label_property' => NULL,
    'yaml_properties' => 
    array (
    ),
    'base_class' => 'Drupal\\views\\Plugin\\views\\area\\AreaPluginBase',
    'base_class_has_di' => true,
    'plugin_properties' => 
    array (
      'id' => 
      array (
        'name' => 'id',
        'description' => 'The attribute class ID.',
        'type' => 'string',
      ),
    ),
    'plugin_interface_methods' => 
    array (
      'preQuery' => 
      array (
        'name' => 'preQuery',
        'declaration' => 'public function preQuery();',
        'description' => 'Run before the view is built.',
      ),
      'getEntityType' => 
      array (
        'name' => 'getEntityType',
        'declaration' => 'public function getEntityType();',
        'description' => 'Determines the entity type used by this handler.',
      ),
      'broken' => 
      array (
        'name' => 'broken',
        'declaration' => 'public function broken();',
        'description' => 'Determines if the handler is considered \'broken\'.',
      ),
      'ensureMyTable' => 
      array (
        'name' => 'ensureMyTable',
        'declaration' => 'public function ensureMyTable();',
        'description' => 'Ensures that the main table for this handler is in the query.',
      ),
      'access' => 
      array (
        'name' => 'access',
        'declaration' => 'public function access(\\Drupal\\Core\\Session\\AccountInterface $account);',
        'description' => 'Check whether given user has access to this handler.',
      ),
      'getJoin' => 
      array (
        'name' => 'getJoin',
        'declaration' => 'public function getJoin();',
        'description' => 'Get the join object that should be used for this handler.',
      ),
      'sanitizeValue' => 
      array (
        'name' => 'sanitizeValue',
        'declaration' => 'public function sanitizeValue($value, $type = NULL);',
        'description' => 'Sanitize the value for output.',
      ),
      'getTableJoin' => 
      array (
        'name' => 'getTableJoin',
        'declaration' => 'public static function getTableJoin($table, $base_table);',
        'description' => 'Fetches a handler to join one table to a primary table from the data cache.',
      ),
      'getField' => 
      array (
        'name' => 'getField',
        'declaration' => 'public function getField($field = NULL);',
        'description' => 'Shortcut to get a handler\'s raw field value.',
      ),
      'postExecute' => 
      array (
        'name' => 'postExecute',
        'declaration' => 'public function postExecute(&$values);',
        'description' => 'Run after the view is executed, before the result is cached.',
      ),
      'showExposeForm' => 
      array (
        'name' => 'showExposeForm',
        'declaration' => 'public function showExposeForm(&$form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Shortcut to display the exposed options form.',
      ),
      'setRelationship' => 
      array (
        'name' => 'setRelationship',
        'declaration' => 'public function setRelationship();',
        'description' => 'Sets up any needed relationship.',
      ),
      'adminLabel' => 
      array (
        'name' => 'adminLabel',
        'declaration' => 'public function adminLabel($short = FALSE);',
        'description' => 'Return a string representing this handler\'s name in the UI.',
      ),
      'breakString' => 
      array (
        'name' => 'breakString',
        'declaration' => 'public static function breakString($str, $force_int = FALSE);',
        'description' => 'Breaks x,y,z and x+y+z into an array.',
      ),
      'adminSummary' => 
      array (
        'name' => 'adminSummary',
        'declaration' => 'public function adminSummary();',
        'description' => 'Provide text for the administrative summary.',
      ),
      'getProvider' => 
      array (
        'name' => 'getProvider',
        'declaration' => 'public function getProvider();',
        'description' => 'Returns the plugin provider.',
      ),
      'pluginTitle' => 
      array (
        'name' => 'pluginTitle',
        'declaration' => 'public function pluginTitle();',
        'description' => 'Return the human readable name of the display.',
      ),
      'usesOptions' => 
      array (
        'name' => 'usesOptions',
        'declaration' => 'public function usesOptions();',
        'description' => 'Returns the usesOptions property.',
      ),
      'filterByDefinedOptions' => 
      array (
        'name' => 'filterByDefinedOptions',
        'declaration' => 'public function filterByDefinedOptions(array &$storage);',
        'description' => 'Filter out stored options depending on the defined options.',
      ),
      'validateOptionsForm' => 
      array (
        'name' => 'validateOptionsForm',
        'declaration' => 'public function validateOptionsForm(&$form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Validate the options form.',
      ),
      'summaryTitle' => 
      array (
        'name' => 'summaryTitle',
        'declaration' => 'public function summaryTitle();',
        'description' => 'Returns the summary of the settings in the display.',
      ),
      'preRenderAddFieldsetMarkup' => 
      array (
        'name' => 'preRenderAddFieldsetMarkup',
        'declaration' => 'public static function preRenderAddFieldsetMarkup(array $form);',
        'description' => 'Moves form elements into fieldsets for presentation purposes.',
      ),
      'init' => 
      array (
        'name' => 'init',
        'declaration' => 'public function init(\\Drupal\\views\\ViewExecutable $view, \\Drupal\\views\\Plugin\\views\\display\\DisplayPluginBase $display, ?array &$options = NULL);',
        'description' => 'Initialize the plugin.',
      ),
      'submitOptionsForm' => 
      array (
        'name' => 'submitOptionsForm',
        'declaration' => 'public function submitOptionsForm(&$form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Handle any special handling on the validate form.',
      ),
      'globalTokenForm' => 
      array (
        'name' => 'globalTokenForm',
        'declaration' => 'public function globalTokenForm(&$form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Adds elements for available core tokens to a form.',
      ),
      'getAvailableGlobalTokens' => 
      array (
        'name' => 'getAvailableGlobalTokens',
        'declaration' => 'public function getAvailableGlobalTokens($prepared = FALSE, array $types = []);',
        'description' => 'Returns an array of available token replacements.',
      ),
      'preRenderFlattenData' => 
      array (
        'name' => 'preRenderFlattenData',
        'declaration' => 'public static function preRenderFlattenData($form);',
        'description' => 'Flattens the structure of form elements.',
      ),
      'globalTokenReplace' => 
      array (
        'name' => 'globalTokenReplace',
        'declaration' => 'public function globalTokenReplace($string = \'\', array $options = []);',
        'description' => 'Returns a string with any core tokens replaced.',
      ),
      'destroy' => 
      array (
        'name' => 'destroy',
        'declaration' => 'public function destroy();',
        'description' => 'Clears a plugin.',
      ),
      'validate' => 
      array (
        'name' => 'validate',
        'declaration' => 'public function validate();',
        'description' => 'Validate that the plugin is correct and can be saved.',
      ),
      'query' => 
      array (
        'name' => 'query',
        'declaration' => 'public function query();',
        'description' => 'Add anything to the query that we might need to.',
      ),
      'unpackOptions' => 
      array (
        'name' => 'unpackOptions',
        'declaration' => 'public function unpackOptions(&$storage, $options, $definition = NULL, $all = TRUE, $check = TRUE);',
        'description' => 'Unpacks options over our existing defaults.',
      ),
      'buildOptionsForm' => 
      array (
        'name' => 'buildOptionsForm',
        'declaration' => 'public function buildOptionsForm(&$form, \\Drupal\\Core\\Form\\FormStateInterface $form_state);',
        'description' => 'Provide a form to edit options for this plugin.',
      ),
      'themeFunctions' => 
      array (
        'name' => 'themeFunctions',
        'declaration' => 'public function themeFunctions();',
        'description' => 'Provide a full list of possible theme templates used by this style.',
      ),
    ),
  ),
);