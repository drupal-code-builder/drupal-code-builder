<?php $data =
array (
  'primary' => 
  array (
    'current_user' => 
    array (
      'id' => 'current_user',
      'label' => 'Current active user',
      'static_method' => 'currentUser',
      'class' => '\\Drupal\\Core\\Session\\AccountProxy',
      'interface' => '\\Drupal\\Core\\Session\\AccountProxyInterface',
      'description' => 'The current active user',
    ),
    'entity_type.manager' => 
    array (
      'id' => 'entity_type.manager',
      'label' => 'Entity type manager',
      'static_method' => 'entityTypeManager',
      'class' => '\\Drupal\\Core\\Entity\\EntityTypeManager',
      'interface' => '\\Drupal\\Core\\Entity\\EntityTypeManagerInterface',
      'description' => 'The entity type manager',
    ),
    'module_handler' => 
    array (
      'id' => 'module_handler',
      'label' => 'Module handler',
      'static_method' => 'moduleHandler',
      'class' => '\\Drupal\\Core\\Extension\\ModuleHandler',
      'interface' => '\\Drupal\\Core\\Extension\\ModuleHandlerInterface',
      'description' => 'The module handler',
    ),
  ),
  'all' => 
  array (
    'cache.discovery' => 
    array (
      'id' => 'cache.discovery',
      'label' => 'Cache backend',
      'static_method' => '',
      'class' => '\\Drupal\\Core\\Cache\\CacheBackendInterface',
      'interface' => '',
      'description' => 'The Cache backend service',
      'variable_name' => 'cache_backend',
    ),
    'current_user' => 
    array (
      'id' => 'current_user',
      'label' => 'Current active user',
      'static_method' => 'currentUser',
      'class' => '\\Drupal\\Core\\Session\\AccountProxy',
      'interface' => '\\Drupal\\Core\\Session\\AccountProxyInterface',
      'description' => 'The current active user',
      'variable_name' => 'current_user',
    ),
    'entity_type.manager' => 
    array (
      'id' => 'entity_type.manager',
      'label' => 'Entity type manager',
      'static_method' => 'entityTypeManager',
      'class' => '\\Drupal\\Core\\Entity\\EntityTypeManager',
      'interface' => '\\Drupal\\Core\\Entity\\EntityTypeManagerInterface',
      'description' => 'The entity type manager',
      'variable_name' => 'entity_type_manager',
    ),
    'module_handler' => 
    array (
      'id' => 'module_handler',
      'label' => 'Module handler',
      'static_method' => 'moduleHandler',
      'class' => '\\Drupal\\Core\\Extension\\ModuleHandler',
      'interface' => '\\Drupal\\Core\\Extension\\ModuleHandlerInterface',
      'description' => 'The module handler',
      'variable_name' => 'module_handler',
    ),
    'storage:node' => 
    array (
      'id' => 'storage:node',
      'label' => 'Content storage',
      'static_method' => '',
      'interface' => '\\Drupal\\Core\\Entity\\EntityStorageInterface',
      'description' => 'The node storage handler',
      'variable_name' => 'node_storage',
      'real_service' => 'entity_type.manager',
      'service_method' => 'getStorage',
    ),
  ),
);