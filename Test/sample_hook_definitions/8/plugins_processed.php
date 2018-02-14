<?php $data =
array (
  'block' => 
  array (
    'type_id' => 'block',
    'service_id' => 'plugin.manager.block',
    'service_class_name' => 'Drupal\\Core\\Block\\BlockManager',
    'service_component_namespace' => 'Drupal\\Core\\Block',
    'type_label' => 'block',
    'subdir' => 'Plugin/Block',
    'plugin_interface' => 'Drupal\\Core\\Block\\BlockPluginInterface',
    'plugin_definition_annotation_name' => 'Drupal\\Core\\Block\\Annotation\\Block',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\AnnotatedClassDiscovery',
    'base_class' => 'Drupal\\Core\\Block\\BlockBase',
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
        'type' => '\\Drupal\\Core\\Annotation\\Translation',
      ),
      'category' => 
      array (
        'name' => 'category',
        'description' => 'The category in the admin UI where the block will be listed.',
        'type' => '\\Drupal\\Core\\Annotation\\Translation',
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
  'field.formatter' => 
  array (
    'type_id' => 'field.formatter',
    'service_id' => 'plugin.manager.field.formatter',
    'service_class_name' => 'Drupal\\Core\\Field\\FormatterPluginManager',
    'service_component_namespace' => 'Drupal\\Core\\Field',
    'type_label' => 'field.formatter',
    'subdir' => 'Plugin/Field/FieldFormatter',
    'plugin_interface' => 'Drupal\\Core\\Field\\FormatterInterface',
    'plugin_definition_annotation_name' => 'Drupal\\Core\\Field\\Annotation\\FieldFormatter',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\AnnotatedClassDiscovery',
    'base_class' => 'Drupal\\Core\\Field\\FormatterBase',
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
        'description' => 'The human-readable name of the formatter type.',
        'type' => '\\Drupal\\Core\\Annotation\\Translation',
      ),
      'description' => 
      array (
        'name' => 'description',
        'description' => 'A short description of the formatter type.',
        'type' => '\\Drupal\\Core\\Annotation\\Translation',
      ),
      'class' => 
      array (
        'name' => 'class',
        'description' => 'The name of the field formatter class.',
        'type' => 'string',
      ),
      'field_types' => 
      array (
        'name' => 'field_types',
        'description' => 'An array of field types the formatter supports.',
        'type' => 'array',
      ),
      'weight' => 
      array (
        'name' => 'weight',
        'description' => 'An integer to determine the weight of this formatter relative to other',
        'type' => 'int optional',
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
  'image.effect' => 
  array (
    'type_id' => 'image.effect',
    'service_id' => 'plugin.manager.image.effect',
    'service_class_name' => 'Drupal\\image\\ImageEffectManager',
    'service_component_namespace' => 'Drupal\\image',
    'type_label' => 'image.effect',
    'subdir' => 'Plugin/ImageEffect',
    'plugin_interface' => 'Drupal\\image\\ImageEffectInterface',
    'plugin_definition_annotation_name' => 'Drupal\\image\\Annotation\\ImageEffect',
    'discovery' => 'Drupal\\Core\\Plugin\\Discovery\\AnnotatedClassDiscovery',
    'base_class' => 'Drupal\\image\\ImageEffectBase',
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
        'type' => '\\Drupal\\Core\\Annotation\\Translation',
      ),
      'description' => 
      array (
        'name' => 'description',
        'description' => 'A brief description of the image effect.',
        'type' => '\\Drupal\\Core\\Annotation\\Translation (optional)',
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
);