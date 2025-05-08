<?php $data =
array (
  'block' => 
  array (
    'hook_block_view_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_block_view_alter',
      'definition' => 'function hook_block_view_alter(array &$build, \\Drupal\\Core\\Block\\BlockPluginInterface $block)',
      'description' => 'Alter the result of \\Drupal\\Core\\Block\\BlockBase::build().',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'block',
      'core' => true,
      'original_file_path' => 'core/modules/block/block.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/block.api.php',
      'body' => '
  // Remove the contextual links on all blocks that provide them.
  if (isset($build[\'#contextual_links\'])) {
    unset($build[\'#contextual_links\']);
  }
',
    ),
    'hook_block_view_BASE_BLOCK_ID_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_block_view_BASE_BLOCK_ID_alter',
      'definition' => 'function hook_block_view_BASE_BLOCK_ID_alter(array &$build, \\Drupal\\Core\\Block\\BlockPluginInterface $block)',
      'description' => 'Provide a block plugin specific block_view alteration.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'block',
      'core' => true,
      'original_file_path' => 'core/modules/block/block.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/block.api.php',
      'body' => '
  // Change the title of the specific block.
  $build[\'#title\'] = t(\'New title of the block\');
',
    ),
    'hook_block_build_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_block_build_alter',
      'definition' => 'function hook_block_build_alter(array &$build, \\Drupal\\Core\\Block\\BlockPluginInterface $block)',
      'description' => 'Alter the result of \\Drupal\\Core\\Block\\BlockBase::build().',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'block',
      'core' => true,
      'original_file_path' => 'core/modules/block/block.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/block.api.php',
      'body' => '
  // Add the \'user\' cache context to some blocks.
  if ($block->label() === \'some condition\') {
    $build[\'#cache\'][\'contexts\'][] = \'user\';
  }
',
    ),
    'hook_block_build_BASE_BLOCK_ID_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_block_build_BASE_BLOCK_ID_alter',
      'definition' => 'function hook_block_build_BASE_BLOCK_ID_alter(array &$build, \\Drupal\\Core\\Block\\BlockPluginInterface $block)',
      'description' => 'Provide a block plugin specific block_build alteration.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'block',
      'core' => true,
      'original_file_path' => 'core/modules/block/block.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/block.api.php',
      'body' => '
  // Explicitly enable placeholdering of the specific block.
  $build[\'#create_placeholder\'] = TRUE;
',
    ),
    'hook_block_access' => 
    array (
      'type' => 'hook',
      'name' => 'hook_block_access',
      'definition' => 'function hook_block_access(\\Drupal\\block\\Entity\\Block $block, $operation, \\Drupal\\Core\\Session\\AccountInterface $account)',
      'description' => 'Control access to a block instance.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'block',
      'core' => true,
      'original_file_path' => 'core/modules/block/block.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/block.api.php',
      'body' => '
  // Example code that would prevent displaying the \'Powered by Drupal\' block in
  // a region different than the footer.
  if ($operation == \'view\' && $block->getPluginId() == \'system_powered_by_block\') {
    return \\Drupal\\Core\\Access\\AccessResult::forbiddenIf($block->getRegion() != \'footer\')->addCacheableDependency($block);
  }

  // No opinion.
  return \\Drupal\\Core\\Access\\AccessResult::neutral();
',
    ),
    'hook_block_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_block_alter',
      'definition' => 'function hook_block_alter(&$definitions)',
      'description' => 'Allow modules to alter the block plugin definitions.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'block',
      'core' => true,
      'original_file_path' => 'core/modules/block/block.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/block.api.php',
      'body' => '
  foreach ($definitions as $id => $definition) {
    if (str_starts_with($id, \'system_menu_block:\')) {
      // Replace $definition properties: id, deriver, class, provider to ones
      // provided by this custom module.
    }
  }
',
    ),
  ),
  'core:entity' => 
  array (
    'hook_entity_access' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_access',
      'definition' => 'function hook_entity_access(\\Drupal\\Core\\Entity\\EntityInterface $entity, $operation, \\Drupal\\Core\\Session\\AccountInterface $account)',
      'description' => 'Control entity operation access.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // No opinion.
  return \\Drupal\\Core\\Access\\AccessResult::neutral();
',
    ),
    'hook_ENTITY_TYPE_access' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_access',
      'definition' => 'function hook_ENTITY_TYPE_access(\\Drupal\\Core\\Entity\\EntityInterface $entity, $operation, \\Drupal\\Core\\Session\\AccountInterface $account)',
      'description' => 'Control entity operation access for a specific entity type.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // No opinion.
  return \\Drupal\\Core\\Access\\AccessResult::neutral();
',
    ),
    'hook_entity_create_access' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_create_access',
      'definition' => 'function hook_entity_create_access(\\Drupal\\Core\\Session\\AccountInterface $account, array $context, $entity_bundle)',
      'description' => 'Control entity create access.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // No opinion.
  return \\Drupal\\Core\\Access\\AccessResult::neutral();
',
    ),
    'hook_ENTITY_TYPE_create_access' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_create_access',
      'definition' => 'function hook_ENTITY_TYPE_create_access(\\Drupal\\Core\\Session\\AccountInterface $account, array $context, $entity_bundle)',
      'description' => 'Control entity create access for a specific entity type.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // No opinion.
  return \\Drupal\\Core\\Access\\AccessResult::neutral();
',
    ),
    'hook_entity_type_build' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_type_build',
      'definition' => 'function hook_entity_type_build(array &$entity_types)',
      'description' => 'Add to entity type definitions.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  /** @var \\Drupal\\Core\\Entity\\EntityTypeInterface[] $entity_types */
  // Add a form for a custom node form without overriding the default
  // node form. To override the default node form, use hook_entity_type_alter().
  $entity_types[\'node\']->setFormClass(\'my_module_foo\', \'Drupal\\my_module\\NodeFooForm\');
',
    ),
    'hook_entity_type_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_type_alter',
      'definition' => 'function hook_entity_type_alter(array &$entity_types)',
      'description' => 'Alter the entity type definitions.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  /** @var \\Drupal\\Core\\Entity\\EntityTypeInterface[] $entity_types */
  // Set the controller class for nodes to an alternate implementation of the
  // Drupal\\Core\\Entity\\EntityStorageInterface interface.
  $entity_types[\'node\']->setStorageClass(\'Drupal\\my_module\\MyCustomNodeStorage\');
',
    ),
    'hook_entity_view_mode_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_view_mode_info_alter',
      'definition' => 'function hook_entity_view_mode_info_alter(&$view_modes)',
      'description' => 'Alter the view modes for entity types.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $view_modes[\'user\'][\'full\'][\'status\'] = TRUE;
',
    ),
    'hook_entity_bundle_info' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_bundle_info',
      'definition' => 'function hook_entity_bundle_info()',
      'description' => 'Describe the bundles for entity types.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $bundles[\'user\'][\'user\'][\'label\'] = t(\'User\');
  return $bundles;
',
    ),
    'hook_entity_bundle_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_bundle_info_alter',
      'definition' => 'function hook_entity_bundle_info_alter(&$bundles)',
      'description' => 'Alter the bundles for entity types.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $bundles[\'user\'][\'user\'][\'label\'] = t(\'Full account\');
  // Override the bundle class for the "article" node type in a custom module.
  $bundles[\'node\'][\'article\'][\'class\'] = \'Drupal\\my_module\\Entity\\Article\';
',
    ),
    'hook_entity_bundle_create' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_bundle_create',
      'definition' => 'function hook_entity_bundle_create($entity_type_id, $bundle)',
      'description' => 'Act on entity_bundle_create().',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // When a new bundle is created, the menu needs to be rebuilt to add the
  // Field UI menu item tabs.
  \\Drupal::service(\'router.builder\')->setRebuildNeeded();
',
    ),
    'hook_entity_bundle_delete' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_bundle_delete',
      'definition' => 'function hook_entity_bundle_delete($entity_type_id, $bundle)',
      'description' => 'Act on entity_bundle_delete().',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Remove the settings associated with the bundle in my_module.settings.
  $config = \\Drupal::config(\'my_module.settings\');
  $bundle_settings = $config->get(\'bundle_settings\');
  if (isset($bundle_settings[$entity_type_id][$bundle])) {
    unset($bundle_settings[$entity_type_id][$bundle]);
    $config->set(\'bundle_settings\', $bundle_settings);
  }
',
    ),
    'hook_entity_create' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_create',
      'definition' => 'function hook_entity_create(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Acts when creating a new entity.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  \\Drupal::logger(\'example\')->info(\'Entity created: @label\', [\'@label\' => $entity->label()]);
',
    ),
    'hook_ENTITY_TYPE_create' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_create',
      'definition' => 'function hook_ENTITY_TYPE_create(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Acts when creating a new entity of a specific type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  \\Drupal::logger(\'example\')->info(\'ENTITY_TYPE created: @label\', [\'@label\' => $entity->label()]);
',
    ),
    'hook_entity_revision_create' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_revision_create',
      'definition' => 'function hook_entity_revision_create(\\Drupal\\Core\\Entity\\EntityInterface $new_revision, \\Drupal\\Core\\Entity\\EntityInterface $entity, $keep_untranslatable_fields)',
      'description' => 'Respond to entity revision creation.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Retain the value from an untranslatable field, which are by default
  // synchronized from the default revision.
  $new_revision->set(\'untranslatable_field\', $entity->get(\'untranslatable_field\'));
',
    ),
    'hook_ENTITY_TYPE_revision_create' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_revision_create',
      'definition' => 'function hook_ENTITY_TYPE_revision_create(\\Drupal\\Core\\Entity\\EntityInterface $new_revision, \\Drupal\\Core\\Entity\\EntityInterface $entity, $keep_untranslatable_fields)',
      'description' => 'Respond to entity revision creation.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Retain the value from an untranslatable field, which are by default
  // synchronized from the default revision.
  $new_revision->set(\'untranslatable_field\', $entity->get(\'untranslatable_field\'));
',
    ),
    'hook_entity_preload' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_preload',
      'definition' => 'function hook_entity_preload(array $ids, $entity_type_id)',
      'description' => 'Act on an array of entity IDs before they are loaded.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $entities = [];

  foreach ($ids as $id) {
    $entities[] = my_module_swap_revision($id);
  }

  return $entities;
',
    ),
    'hook_entity_load' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_load',
      'definition' => 'function hook_entity_load(array $entities, $entity_type_id)',
      'description' => 'Act on entities when loaded.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  foreach ($entities as $entity) {
    $entity->foo = my_module_add_something($entity);
  }
',
    ),
    'hook_ENTITY_TYPE_load' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_load',
      'definition' => 'function hook_ENTITY_TYPE_load($entities)',
      'description' => 'Act on entities of a specific type when loaded.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  foreach ($entities as $entity) {
    $entity->foo = my_module_add_something($entity);
  }
',
    ),
    'hook_entity_storage_load' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_storage_load',
      'definition' => 'function hook_entity_storage_load(array $entities, $entity_type)',
      'description' => 'Act on content entities when loaded from the storage.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  foreach ($entities as $entity) {
    $entity->foo = my_module_add_something_uncached($entity);
  }
',
    ),
    'hook_ENTITY_TYPE_storage_load' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_storage_load',
      'definition' => 'function hook_ENTITY_TYPE_storage_load(array $entities)',
      'description' => 'Act on content entities of a given type when loaded from the storage.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  foreach ($entities as $entity) {
    $entity->foo = my_module_add_something_uncached($entity);
  }
',
    ),
    'hook_entity_presave' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_presave',
      'definition' => 'function hook_entity_presave(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Act on an entity before it is created or updated.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if ($entity instanceof \\Drupal\\Core\\Entity\\ContentEntityInterface && $entity->isTranslatable()) {
    $route_match = \\Drupal::routeMatch();
    \\Drupal::service(\'content_translation.synchronizer\')->synchronizeFields($entity, $entity->language()->getId(), $route_match->getParameter(\'source_langcode\'));
  }
',
    ),
    'hook_ENTITY_TYPE_presave' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_presave',
      'definition' => 'function hook_ENTITY_TYPE_presave(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Act on a specific type of entity before it is created or updated.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if ($entity->isTranslatable()) {
    $route_match = \\Drupal::routeMatch();
    \\Drupal::service(\'content_translation.synchronizer\')->synchronizeFields($entity, $entity->language()->getId(), $route_match->getParameter(\'source_langcode\'));
  }
',
    ),
    'hook_entity_insert' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_insert',
      'definition' => 'function hook_entity_insert(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Respond to creation of a new entity.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Insert the new entity into a fictional table of all entities.
  \\Drupal::database()->insert(\'example_entity\')
    ->fields([
      \'type\' => $entity->getEntityTypeId(),
      \'id\' => $entity->id(),
      \'created\' => \\Drupal::time()->getRequestTime(),
      \'updated\' => \\Drupal::time()->getRequestTime(),
    ])
    ->execute();
',
    ),
    'hook_ENTITY_TYPE_insert' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_insert',
      'definition' => 'function hook_ENTITY_TYPE_insert(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Respond to creation of a new entity of a particular type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Insert the new entity into a fictional table of this type of entity.
  \\Drupal::database()->insert(\'example_entity\')
    ->fields([
      \'id\' => $entity->id(),
      \'created\' => \\Drupal::time()->getRequestTime(),
      \'updated\' => \\Drupal::time()->getRequestTime(),
    ])
    ->execute();
',
    ),
    'hook_entity_update' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_update',
      'definition' => 'function hook_entity_update(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Respond to updates to an entity.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Update the entity\'s entry in a fictional table of all entities.
  \\Drupal::database()->update(\'example_entity\')
    ->fields([
      \'updated\' => \\Drupal::time()->getRequestTime(),
    ])
    ->condition(\'type\', $entity->getEntityTypeId())
    ->condition(\'id\', $entity->id())
    ->execute();
',
    ),
    'hook_ENTITY_TYPE_update' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_update',
      'definition' => 'function hook_ENTITY_TYPE_update(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Respond to updates to an entity of a particular type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Update the entity\'s entry in a fictional table of this type of entity.
  \\Drupal::database()->update(\'example_entity\')
    ->fields([
      \'updated\' => \\Drupal::time()->getRequestTime(),
    ])
    ->condition(\'id\', $entity->id())
    ->execute();
',
    ),
    'hook_entity_translation_create' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_translation_create',
      'definition' => 'function hook_entity_translation_create(\\Drupal\\Core\\Entity\\EntityInterface $translation)',
      'description' => 'Acts when creating a new entity translation.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  \\Drupal::logger(\'example\')->info(\'Entity translation created: @label\', [\'@label\' => $translation->label()]);
',
    ),
    'hook_ENTITY_TYPE_translation_create' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_translation_create',
      'definition' => 'function hook_ENTITY_TYPE_translation_create(\\Drupal\\Core\\Entity\\EntityInterface $translation)',
      'description' => 'Acts when creating a new entity translation of a specific type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  \\Drupal::logger(\'example\')->info(\'ENTITY_TYPE translation created: @label\', [\'@label\' => $translation->label()]);
',
    ),
    'hook_entity_translation_insert' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_translation_insert',
      'definition' => 'function hook_entity_translation_insert(\\Drupal\\Core\\Entity\\EntityInterface $translation)',
      'description' => 'Respond to creation of a new entity translation.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $variables = [
    \'@language\' => $translation->language()->getName(),
    \'@label\' => $translation->getUntranslated()->label(),
  ];
  \\Drupal::logger(\'example\')->notice(\'The @language translation of @label has just been stored.\', $variables);
',
    ),
    'hook_ENTITY_TYPE_translation_insert' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_translation_insert',
      'definition' => 'function hook_ENTITY_TYPE_translation_insert(\\Drupal\\Core\\Entity\\EntityInterface $translation)',
      'description' => 'Respond to creation of a new entity translation of a particular type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $variables = [
    \'@language\' => $translation->language()->getName(),
    \'@label\' => $translation->getUntranslated()->label(),
  ];
  \\Drupal::logger(\'example\')->notice(\'The @language translation of @label has just been stored.\', $variables);
',
    ),
    'hook_entity_translation_delete' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_translation_delete',
      'definition' => 'function hook_entity_translation_delete(\\Drupal\\Core\\Entity\\EntityInterface $translation)',
      'description' => 'Respond to entity translation deletion.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $variables = [
    \'@language\' => $translation->language()->getName(),
    \'@label\' => $translation->label(),
  ];
  \\Drupal::logger(\'example\')->notice(\'The @language translation of @label has just been deleted.\', $variables);
',
    ),
    'hook_ENTITY_TYPE_translation_delete' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_translation_delete',
      'definition' => 'function hook_ENTITY_TYPE_translation_delete(\\Drupal\\Core\\Entity\\EntityInterface $translation)',
      'description' => 'Respond to entity translation deletion of a particular type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $variables = [
    \'@language\' => $translation->language()->getName(),
    \'@label\' => $translation->label(),
  ];
  \\Drupal::logger(\'example\')->notice(\'The @language translation of @label has just been deleted.\', $variables);
',
    ),
    'hook_entity_predelete' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_predelete',
      'definition' => 'function hook_entity_predelete(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Act before entity deletion.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $connection = \\Drupal::database();
  // Count references to this entity in a custom table before they are removed
  // upon entity deletion.
  $id = $entity->id();
  $type = $entity->getEntityTypeId();
  $count = \\Drupal::database()->select(\'example_entity_data\')
    ->condition(\'type\', $type)
    ->condition(\'id\', $id)
    ->countQuery()
    ->execute()
    ->fetchField();

  // Log the count in a table that records this statistic for deleted entities.
  $connection->merge(\'example_deleted_entity_statistics\')
    ->keys([\'type\' => $type, \'id\' => $id])
    ->fields([\'count\' => $count])
    ->execute();
',
    ),
    'hook_ENTITY_TYPE_predelete' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_predelete',
      'definition' => 'function hook_ENTITY_TYPE_predelete(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Act before entity deletion of a particular entity type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $connection = \\Drupal::database();
  // Count references to this entity in a custom table before they are removed
  // upon entity deletion.
  $id = $entity->id();
  $type = $entity->getEntityTypeId();
  $count = \\Drupal::database()->select(\'example_entity_data\')
    ->condition(\'type\', $type)
    ->condition(\'id\', $id)
    ->countQuery()
    ->execute()
    ->fetchField();

  // Log the count in a table that records this statistic for deleted entities.
  $connection->merge(\'example_deleted_entity_statistics\')
    ->keys([\'type\' => $type, \'id\' => $id])
    ->fields([\'count\' => $count])
    ->execute();
',
    ),
    'hook_entity_delete' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_delete',
      'definition' => 'function hook_entity_delete(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Respond to entity deletion.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Delete the entity\'s entry from a fictional table of all entities.
  \\Drupal::database()->delete(\'example_entity\')
    ->condition(\'type\', $entity->getEntityTypeId())
    ->condition(\'id\', $entity->id())
    ->execute();
',
    ),
    'hook_ENTITY_TYPE_delete' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_delete',
      'definition' => 'function hook_ENTITY_TYPE_delete(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Respond to entity deletion of a particular type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Delete the entity\'s entry from a fictional table of all entities.
  \\Drupal::database()->delete(\'example_entity\')
    ->condition(\'type\', $entity->getEntityTypeId())
    ->condition(\'id\', $entity->id())
    ->execute();
',
    ),
    'hook_entity_revision_delete' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_revision_delete',
      'definition' => 'function hook_entity_revision_delete(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Respond to entity revision deletion.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $referenced_files_by_field = _editor_get_file_uuids_by_field($entity);
  foreach ($referenced_files_by_field as $field => $uuids) {
    _editor_delete_file_usage($uuids, $entity, 1);
  }
',
    ),
    'hook_ENTITY_TYPE_revision_delete' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_revision_delete',
      'definition' => 'function hook_ENTITY_TYPE_revision_delete(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Respond to entity revision deletion of a particular type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $referenced_files_by_field = _editor_get_file_uuids_by_field($entity);
  foreach ($referenced_files_by_field as $field => $uuids) {
    _editor_delete_file_usage($uuids, $entity, 1);
  }
',
    ),
    'hook_entity_view' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_view',
      'definition' => 'function hook_entity_view(array &$build, \\Drupal\\Core\\Entity\\EntityInterface $entity, \\Drupal\\Core\\Entity\\Display\\EntityViewDisplayInterface $display, $view_mode)',
      'description' => 'Act on entities being assembled before rendering.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Only do the extra work if the component is configured to be displayed.
  // This assumes a \'my_module_addition\' extra field has been defined for the
  // entity bundle in hook_entity_extra_field_info().
  if ($display->getComponent(\'my_module_addition\')) {
    $build[\'my_module_addition\'] = [
      \'#markup\' => my_module_addition($entity),
      \'#theme\' => \'my_module_my_additional_field\',
    ];
  }
',
    ),
    'hook_ENTITY_TYPE_view' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_view',
      'definition' => 'function hook_ENTITY_TYPE_view(array &$build, \\Drupal\\Core\\Entity\\EntityInterface $entity, \\Drupal\\Core\\Entity\\Display\\EntityViewDisplayInterface $display, $view_mode)',
      'description' => 'Act on entities of a particular type being assembled before rendering.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Only do the extra work if the component is configured to be displayed.
  // This assumes a \'my_module_addition\' extra field has been defined for the
  // entity bundle in hook_entity_extra_field_info().
  if ($display->getComponent(\'my_module_addition\')) {
    $build[\'my_module_addition\'] = [
      \'#markup\' => my_module_addition($entity),
      \'#theme\' => \'my_module_my_additional_field\',
    ];
  }
',
    ),
    'hook_entity_view_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_view_alter',
      'definition' => 'function hook_entity_view_alter(array &$build, \\Drupal\\Core\\Entity\\EntityInterface $entity, \\Drupal\\Core\\Entity\\Display\\EntityViewDisplayInterface $display)',
      'description' => 'Alter the results of the entity build array.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if ($build[\'#view_mode\'] == \'full\' && isset($build[\'an_additional_field\'])) {
    // Change its weight.
    $build[\'an_additional_field\'][\'#weight\'] = -10;

    // Add a #post_render callback to act on the rendered HTML of the entity.
    // The object must implement \\Drupal\\Core\\Security\\TrustedCallbackInterface.
    $build[\'#post_render\'][] = \'\\Drupal\\my_module\\NodeCallback::postRender\';
  }
',
    ),
    'hook_ENTITY_TYPE_view_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_view_alter',
      'definition' => 'function hook_ENTITY_TYPE_view_alter(array &$build, \\Drupal\\Core\\Entity\\EntityInterface $entity, \\Drupal\\Core\\Entity\\Display\\EntityViewDisplayInterface $display)',
      'description' => 'Alter the results of the entity build array for a particular entity type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if ($build[\'#view_mode\'] == \'full\' && isset($build[\'an_additional_field\'])) {
    // Change its weight.
    $build[\'an_additional_field\'][\'#weight\'] = -10;

    // Add a #post_render callback to act on the rendered HTML of the entity.
    $build[\'#post_render\'][] = \'my_module_node_post_render\';
  }
',
    ),
    'hook_entity_prepare_view' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_prepare_view',
      'definition' => 'function hook_entity_prepare_view($entity_type_id, array $entities, array $displays, $view_mode)',
      'description' => 'Act on entities as they are being prepared for view.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Load a specific node into the user object for later theming.
  if (!empty($entities) && $entity_type_id == \'user\') {
    // Only do the extra work if the component is configured to be
    // displayed. This assumes a \'my_module_addition\' extra field has been
    // defined for the entity bundle in hook_entity_extra_field_info().
    $ids = [];
    foreach ($entities as $id => $entity) {
      if ($displays[$entity->bundle()]->getComponent(\'my_module_addition\')) {
        $ids[] = $id;
      }
    }
    if ($ids) {
      $nodes = my_module_get_user_nodes($ids);
      foreach ($ids as $id) {
        $entities[$id]->user_node = $nodes[$id];
      }
    }
  }
',
    ),
    'hook_entity_view_mode_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_view_mode_alter',
      'definition' => 'function hook_entity_view_mode_alter(&$view_mode, \\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Change the view mode of an entity that is being displayed.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // For nodes, change the view mode when it is teaser.
  if ($entity->getEntityTypeId() == \'node\' && $view_mode == \'teaser\') {
    $view_mode = \'my_custom_view_mode\';
  }
',
    ),
    'hook_ENTITY_TYPE_view_mode_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_view_mode_alter',
      'definition' => 'function hook_ENTITY_TYPE_view_mode_alter(string &$view_mode, \\Drupal\\Core\\Entity\\EntityInterface $entity): void',
      'description' => 'Change the view mode of a specific entity type currently being displayed.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Change the view mode to teaser.
  if ($view_mode == \'full\') {
    $view_mode = \'teaser\';
  }
',
    ),
    'hook_ENTITY_TYPE_build_defaults_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_build_defaults_alter',
      'definition' => 'function hook_ENTITY_TYPE_build_defaults_alter(array &$build, \\Drupal\\Core\\Entity\\EntityInterface $entity, $view_mode)',
      'description' => 'Alter entity renderable values before cache checking during rendering.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '

',
    ),
    'hook_entity_build_defaults_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_build_defaults_alter',
      'definition' => 'function hook_entity_build_defaults_alter(array &$build, \\Drupal\\Core\\Entity\\EntityInterface $entity, $view_mode)',
      'description' => 'Alter entity renderable values before cache checking during rendering.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '

',
    ),
    'hook_entity_view_display_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_view_display_alter',
      'definition' => 'function hook_entity_view_display_alter(\\Drupal\\Core\\Entity\\Display\\EntityViewDisplayInterface $display, array $context)',
      'description' => 'Alter the settings used for displaying an entity.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Leave field labels out of the search index.
  if ($context[\'entity_type\'] == \'node\' && $context[\'view_mode\'] == \'search_index\') {
    foreach ($display->getComponents() as $name => $options) {
      if (isset($options[\'label\'])) {
        $options[\'label\'] = \'hidden\';
        $display->setComponent($name, $options);
      }
    }
  }
',
    ),
    'hook_entity_display_build_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_display_build_alter',
      'definition' => 'function hook_entity_display_build_alter(&$build, $context)',
      'description' => 'Alter the render array generated by an EntityDisplay for an entity.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  /** @var \\Drupal\\Core\\Entity\\\\Drupal\\Core\\Entity\\ContentEntityInterface $entity */
  $entity = $context[\'entity\'];
  if ($entity->getEntityTypeId() === \'my_entity\' && $entity->bundle() === \'display_build_alter_bundle\') {
    $build[\'entity_display_build_alter\'][\'#markup\'] = \'Content added in hook_entity_display_build_alter for entity id \' . $entity->id();
  }
',
    ),
    'hook_entity_prepare_form' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_prepare_form',
      'definition' => 'function hook_entity_prepare_form(\\Drupal\\Core\\Entity\\EntityInterface $entity, $operation, \\Drupal\\Core\\Form\\FormStateInterface $form_state)',
      'description' => 'Acts on an entity object about to be shown on an entity form.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if ($operation == \'edit\') {
    $entity->label->value = \'Altered label\';
    $form_state->set(\'label_altered\', TRUE);
  }
',
    ),
    'hook_ENTITY_TYPE_prepare_form' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_prepare_form',
      'definition' => 'function hook_ENTITY_TYPE_prepare_form(\\Drupal\\Core\\Entity\\EntityInterface $entity, $operation, \\Drupal\\Core\\Form\\FormStateInterface $form_state)',
      'description' => 'Acts on a particular type of entity object about to be in an entity form.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if ($operation == \'edit\') {
    $entity->label->value = \'Altered label\';
    $form_state->set(\'label_altered\', TRUE);
  }
',
    ),
    'hook_entity_form_mode_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_form_mode_alter',
      'definition' => 'function hook_entity_form_mode_alter(&$form_mode, \\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Change the form mode used to build an entity form.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Change the form mode for users with Administrator role.
  if ($entity->getEntityTypeId() == \'user\' && $entity->hasRole(\'administrator\')) {
    $form_mode = \'my_custom_form_mode\';
  }
',
    ),
    'hook_ENTITY_TYPE_form_mode_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_form_mode_alter',
      'definition' => 'function hook_ENTITY_TYPE_form_mode_alter(string &$form_mode, \\Drupal\\Core\\Entity\\EntityInterface $entity): void',
      'description' => 'Change the form mode of a specific entity type currently being displayed.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Change the form mode for nodes with \'article\' bundle.
  if ($entity->bundle() == \'article\') {
    $form_mode = \'custom_article_form_mode\';
  }
',
    ),
    'hook_entity_form_display_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_form_display_alter',
      'definition' => 'function hook_entity_form_display_alter(\\Drupal\\Core\\Entity\\Display\\EntityFormDisplayInterface $form_display, array $context)',
      'description' => 'Alter the settings used for displaying an entity form.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Hide the \'user_picture\' field from the register form.
  if ($context[\'entity_type\'] == \'user\' && $context[\'form_mode\'] == \'register\') {
    $form_display->setComponent(\'user_picture\', [
      \'region\' => \'hidden\',
    ]);
  }
',
    ),
    'hook_entity_base_field_info' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_base_field_info',
      'definition' => 'function hook_entity_base_field_info(\\Drupal\\Core\\Entity\\EntityTypeInterface $entity_type)',
      'description' => 'Provides custom base field definitions for a content entity type.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if ($entity_type->id() == \'node\') {
    $fields = [];
    $fields[\'my_module_text\'] = \\Drupal\\Core\\Field\\BaseFieldDefinition::create(\'string\')
      ->setLabel(t(\'The text\'))
      ->setDescription(t(\'A text property added by my_module.\'))
      ->setComputed(TRUE)
      ->setClass(\'\\Drupal\\my_module\\EntityComputedText\');

    return $fields;
  }
',
    ),
    'hook_entity_base_field_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_base_field_info_alter',
      'definition' => 'function hook_entity_base_field_info_alter(&$fields, \\Drupal\\Core\\Entity\\EntityTypeInterface $entity_type)',
      'description' => 'Alter base field definitions for a content entity type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Alter the my_module_text field to use a custom class.
  if ($entity_type->id() == \'node\' && !empty($fields[\'my_module_text\'])) {
    $fields[\'my_module_text\']->setClass(\'\\Drupal\\another_module\\EntityComputedText\');
  }
',
    ),
    'hook_entity_bundle_field_info' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_bundle_field_info',
      'definition' => 'function hook_entity_bundle_field_info(\\Drupal\\Core\\Entity\\EntityTypeInterface $entity_type, $bundle, array $base_field_definitions)',
      'description' => 'Provides field definitions for a specific bundle within an entity type.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Add a property only to nodes of the \'article\' bundle.
  if ($entity_type->id() == \'node\' && $bundle == \'article\') {
    $fields = [];
    $storage_definitions = my_module_entity_field_storage_info($entity_type);
    $fields[\'my_module_bundle_field\'] = \\Drupal\\Core\\Field\\FieldDefinition::createFromFieldStorageDefinition($storage_definitions[\'my_module_bundle_field\'])
      ->setLabel(t(\'Bundle Field\'));
    return $fields;
  }

',
    ),
    'hook_entity_bundle_field_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_bundle_field_info_alter',
      'definition' => 'function hook_entity_bundle_field_info_alter(&$fields, \\Drupal\\Core\\Entity\\EntityTypeInterface $entity_type, $bundle)',
      'description' => 'Alter bundle field definitions.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if ($entity_type->id() == \'node\' && $bundle == \'article\' && !empty($fields[\'my_module_text\'])) {
    // Alter the my_module_text field to use a custom class.
    $fields[\'my_module_text\']->setClass(\'\\Drupal\\another_module\\EntityComputedText\');
  }
',
    ),
    'hook_entity_field_storage_info' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_field_storage_info',
      'definition' => 'function hook_entity_field_storage_info(\\Drupal\\Core\\Entity\\EntityTypeInterface $entity_type)',
      'description' => 'Provides field storage definitions for a content entity type.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if (\\Drupal::entityTypeManager()->getStorage($entity_type->id()) instanceof \\Drupal\\Core\\Entity\\DynamicallyFieldableEntityStorageInterface) {
    // Query by filtering on the ID as this is more efficient than filtering
    // on the entity_type property directly.
    $ids = \\Drupal::entityQuery(\'field_storage_config\')
      ->condition(\'id\', $entity_type->id() . \'.\', \'STARTS_WITH\')
      ->execute();
    // Fetch all fields and key them by field name.
    $field_storages = FieldStorageConfig::loadMultiple($ids);
    $result = [];
    foreach ($field_storages as $field_storage) {
      $result[$field_storage->getName()] = $field_storage;
    }

    return $result;
  }
',
    ),
    'hook_entity_field_storage_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_field_storage_info_alter',
      'definition' => 'function hook_entity_field_storage_info_alter(&$fields, \\Drupal\\Core\\Entity\\EntityTypeInterface $entity_type)',
      'description' => 'Alter field storage definitions for a content entity type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Alter the max_length setting.
  if ($entity_type->id() == \'node\' && !empty($fields[\'my_module_text\'])) {
    $fields[\'my_module_text\']->setSetting(\'max_length\', 128);
  }
',
    ),
    'hook_entity_operation' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_operation',
      'definition' => 'function hook_entity_operation(\\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Declares entity operations.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $operations = [];
  $operations[\'translate\'] = [
    \'title\' => t(\'Translate\'),
    \'url\' => \\Drupal\\Core\\Url::fromRoute(\'foo_module.entity.translate\'),
    \'weight\' => 50,
  ];

  return $operations;
',
    ),
    'hook_entity_operation_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_operation_alter',
      'definition' => 'function hook_entity_operation_alter(array &$operations, \\Drupal\\Core\\Entity\\EntityInterface $entity)',
      'description' => 'Alter entity operations.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Alter the title and weight.
  $operations[\'translate\'][\'title\'] = t(\'Translate @entity_type\', [
    \'@entity_type\' => $entity->getEntityTypeId(),
  ]);
  $operations[\'translate\'][\'weight\'] = 99;
',
    ),
    'hook_entity_field_access' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_field_access',
      'definition' => 'function hook_entity_field_access($operation, \\Drupal\\Core\\Field\\FieldDefinitionInterface $field_definition, \\Drupal\\Core\\Session\\AccountInterface $account, ?\\Drupal\\Core\\Field\\FieldItemListInterface $items = NULL)',
      'description' => 'Control access to fields.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if ($field_definition->getName() == \'field_of_interest\' && $operation == \'edit\') {
    return \\Drupal\\Core\\Access\\AccessResult::allowedIfHasPermission($account, \'update field of interest\');
  }
  return \\Drupal\\Core\\Access\\AccessResult::neutral();
',
    ),
    'hook_entity_field_access_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_field_access_alter',
      'definition' => 'function hook_entity_field_access_alter(array &$grants, array $context)',
      'description' => 'Alter the default access behavior for a given field.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  /** @var \\Drupal\\Core\\Field\\FieldDefinitionInterface $field_definition */
  $field_definition = $context[\'field_definition\'];
  if ($field_definition->getName() == \'field_of_interest\' && $grants[\'node\']->isForbidden()) {
    // Override node module\'s restriction to no opinion (neither allowed nor
    // forbidden). We don\'t want to provide our own access hook, we only want to
    // take out node module\'s part in the access handling of this field. We also
    // don\'t want to switch node module\'s grant to
    // AccessResultInterface::isAllowed() , because the grants of other modules
    // should still decide on their own if this field is accessible or not
    $grants[\'node\'] = \\Drupal\\Core\\Access\\AccessResult::neutral()->inheritCacheability($grants[\'node\']);
  }
',
    ),
    'hook_entity_field_values_init' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_field_values_init',
      'definition' => 'function hook_entity_field_values_init(\\Drupal\\Core\\Entity\\FieldableEntityInterface $entity)',
      'description' => 'Acts when initializing a fieldable entity object.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if ($entity instanceof \\Drupal\\Core\\Entity\\\\Drupal\\Core\\Entity\\ContentEntityInterface && !$entity->foo->value) {
    $entity->foo->value = \'some_initial_value\';
  }
',
    ),
    'hook_ENTITY_TYPE_field_values_init' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ENTITY_TYPE_field_values_init',
      'definition' => 'function hook_ENTITY_TYPE_field_values_init(\\Drupal\\Core\\Entity\\FieldableEntityInterface $entity)',
      'description' => 'Acts when initializing a fieldable entity object.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if (!$entity->foo->value) {
    $entity->foo->value = \'some_initial_value\';
  }
',
    ),
    'hook_entity_extra_field_info' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_extra_field_info',
      'definition' => 'function hook_entity_extra_field_info()',
      'description' => 'Exposes "pseudo-field" components on content entities.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $extra = [];
  $module_language_enabled = \\Drupal::moduleHandler()->moduleExists(\'language\');
  $description = t(\'Node module element\');

  foreach (\\Drupal\\node\\Entity\\NodeType::loadMultiple() as $bundle) {

    // Add also the \'language\' select if Language module is enabled and the
    // bundle has multilingual support.
    // Visibility of the ordering of the language selector is the same as on the
    // node/add form.
    if ($module_language_enabled) {
      $configuration = \\Drupal\\language\\Entity\\ContentLanguageSettings::loadByEntityTypeBundle(\'node\', $bundle->id());
      if ($configuration->isLanguageAlterable()) {
        $extra[\'node\'][$bundle->id()][\'form\'][\'language\'] = [
          \'label\' => t(\'Language\'),
          \'description\' => $description,
          \'weight\' => 0,
        ];
      }
    }
    $extra[\'node\'][$bundle->id()][\'display\'][\'language\'] = [
      \'label\' => t(\'Language\'),
      \'description\' => $description,
      \'weight\' => 0,
      \'visible\' => FALSE,
    ];
  }

  return $extra;
',
    ),
    'hook_entity_extra_field_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_extra_field_info_alter',
      'definition' => 'function hook_entity_extra_field_info_alter(&$info)',
      'description' => 'Alter "pseudo-field" components on content entities.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  // Force node title to always be at the top of the list by default.
  foreach (\\Drupal\\node\\Entity\\NodeType::loadMultiple() as $bundle) {
    if (isset($info[\'node\'][$bundle->id()][\'form\'][\'title\'])) {
      $info[\'node\'][$bundle->id()][\'form\'][\'title\'][\'weight\'] = -20;
    }
  }
',
    ),
    'hook_entity_query_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_query_alter',
      'definition' => 'function hook_entity_query_alter(\\Drupal\\Core\\Entity\\Query\\QueryInterface $query): void',
      'description' => 'Alter an entity query.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  if ($query->hasTag(\'entity_reference\')) {
    $entityType = \\Drupal::entityTypeManager()->getDefinition($query->getEntityTypeId());
    $query->sort($entityType->getKey(\'id\'), \'desc\');
  }
',
    ),
    'hook_entity_query_ENTITY_TYPE_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_query_ENTITY_TYPE_alter',
      'definition' => 'function hook_entity_query_ENTITY_TYPE_alter(\\Drupal\\Core\\Entity\\Query\\QueryInterface $query): void',
      'description' => 'Alter an entity query for a specific entity type.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $query->condition(\'id\', \'1\', \'<>\');
',
    ),
    'hook_entity_query_tag__TAG_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_query_tag__TAG_alter',
      'definition' => 'function hook_entity_query_tag__TAG_alter(\\Drupal\\Core\\Entity\\Query\\QueryInterface $query): void',
      'description' => 'Alter an entity query that has a specific tag.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $entityType = \\Drupal::entityTypeManager()->getDefinition($query->getEntityTypeId());
  $query->sort($entityType->getKey(\'id\'), \'desc\');
',
    ),
    'hook_entity_query_tag__ENTITY_TYPE__TAG_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_entity_query_tag__ENTITY_TYPE__TAG_alter',
      'definition' => 'function hook_entity_query_tag__ENTITY_TYPE__TAG_alter(\\Drupal\\Core\\Entity\\Query\\QueryInterface $query): void',
      'description' => 'Alter an entity query for a specific entity type that has a specific tag.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:entity',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Entity/entity.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_entity.api.php',
      'body' => '
  $query->condition(\'id\', \'1\', \'<>\');
',
    ),
  ),
  'core:form' => 
  array (
    'callback_batch_operation' => 
    array (
      'type' => 'callback',
      'name' => 'callback_batch_operation',
      'definition' => 'function callback_batch_operation($multiple_params, &$context)',
      'description' => 'Perform a single batch operation.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
        0 => 'callback_batch_finished',
        1 => 'callback_batch_finished',
      ),
      'group' => 'core:form',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Form/form.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_form.api.php',
      'body' => '
  $node_storage = \\Drupal::entityTypeManager()->getStorage(\'node\');
  $database = \\Drupal::database();

  if (!isset($context[\'sandbox\'][\'progress\'])) {
    $context[\'sandbox\'][\'progress\'] = 0;
    $context[\'sandbox\'][\'current_node\'] = 0;
    $context[\'sandbox\'][\'max\'] = $database->query(\'SELECT COUNT(DISTINCT [nid]) FROM {node}\')->fetchField();
  }

  // For this example, we decide that we can safely process
  // 5 nodes at a time without a timeout.
  $limit = 5;

  // With each pass through the callback, retrieve the next group of nids.
  $result = $database->queryRange("SELECT [nid] FROM {node} WHERE [nid] > :nid ORDER BY [nid] ASC", 0, $limit, [\':nid\' => $context[\'sandbox\'][\'current_node\']]);
  foreach ($result as $row) {

    // Here we actually perform our processing on the current node.
    $node_storage->resetCache([$row[\'nid\']]);
    $node = $node_storage->load($row[\'nid\']);
    $node->value1 = $options1;
    $node->value2 = $options2;
    node_save($node);

    // Store some result for post-processing in the finished callback.
    $context[\'results\'][] = $node->title;

    // Update our progress information.
    $context[\'sandbox\'][\'progress\']++;
    $context[\'sandbox\'][\'current_node\'] = $node->nid;
    $context[\'message\'] = t(\'Now processing %node\', [\'%node\' => $node->title]);
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context[\'sandbox\'][\'progress\'] != $context[\'sandbox\'][\'max\']) {
    $context[\'finished\'] = $context[\'sandbox\'][\'progress\'] / $context[\'sandbox\'][\'max\'];
  }
',
    ),
    'callback_batch_finished' => 
    array (
      'type' => 'callback',
      'name' => 'callback_batch_finished',
      'definition' => 'function callback_batch_finished($success, $results, $operations, $elapsed)',
      'description' => 'Complete a batch process.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
        0 => 'callback_batch_operation',
      ),
      'group' => 'core:form',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Form/form.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_form.api.php',
      'body' => '
  if ($success) {
    // Here we do something meaningful with the results.
    $message = t("@count items were processed (@elapsed).", [
      \'@count\' => count($results),
      \'@elapsed\' => $elapsed,
    ]);
    $list = [
      \'#theme\' => \'item_list\',
      \'#items\' => $results,
    ];
    $message .= \\Drupal::service(\'renderer\')->render($list);
    \\Drupal::messenger()->addStatus($message);
  }
  else {
    // An error occurred.
    // $operations contains the operations that remained unprocessed.
    $error_operation = reset($operations);
    $message = t(\'An error occurred while processing %error_operation with arguments: @arguments\', [
      \'%error_operation\' => $error_operation[0],
      \'@arguments\' => print_r($error_operation[1], TRUE),
    ]);
    \\Drupal::messenger()->addError($message);
  }
',
    ),
    'hook_ajax_render_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_ajax_render_alter',
      'definition' => 'function hook_ajax_render_alter(array &$data)',
      'description' => 'Alter the Ajax command data that is sent to the client.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:form',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Form/form.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_form.api.php',
      'body' => '
  // Inject any new status messages into the content area.
  $status_messages = [\'#type\' => \'status_messages\'];
  $command = new \\Drupal\\Core\\Ajax\\PrependCommand(\'#block-system-main .content\', \\Drupal::service(\'renderer\')->renderRoot($status_messages));
  $data[] = $command->render();
',
    ),
    'hook_form_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_form_alter',
      'definition' => 'function hook_form_alter(&$form, \\Drupal\\Core\\Form\\FormStateInterface $form_state, $form_id)',
      'description' => 'Perform alterations before a form is rendered.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:form',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Form/form.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_form.api.php',
      'body' => '
  if (isset($form[\'type\']) && $form[\'type\'][\'#value\'] . \'_node_settings\' == $form_id) {
    $upload_enabled_types = \\Drupal::config(\'my_module.settings\')->get(\'upload_enabled_types\');
    $form[\'workflow\'][\'upload_\' . $form[\'type\'][\'#value\']] = [
      \'#type\' => \'radios\',
      \'#title\' => t(\'Attachments\'),
      \'#default_value\' => in_array($form[\'type\'][\'#value\'], $upload_enabled_types) ? 1 : 0,
      \'#options\' => [t(\'Disabled\'), t(\'Enabled\')],
    ];
    // Add a custom submit handler to save the array of types back to the config file.
    $form[\'actions\'][\'submit\'][\'#submit\'][] = \'my_module_upload_enabled_types_submit\';
  }
',
    ),
    'hook_form_FORM_ID_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_form_FORM_ID_alter',
      'definition' => 'function hook_form_FORM_ID_alter(&$form, \\Drupal\\Core\\Form\\FormStateInterface $form_state, $form_id)',
      'description' => 'Provide a form-specific alteration instead of the global hook_form_alter().',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:form',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Form/form.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_form.api.php',
      'body' => '
  // Modification for the form with the given form ID goes here. For example, if
  // FORM_ID is "user_register_form" this code would run only on the user
  // registration form.

  // Add a checkbox to registration form about agreeing to terms of use.
  $form[\'terms_of_use\'] = [
    \'#type\' => \'checkbox\',
    \'#title\' => t("I agree with the website\'s terms and conditions."),
    \'#required\' => TRUE,
  ];
',
    ),
    'hook_form_BASE_FORM_ID_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_form_BASE_FORM_ID_alter',
      'definition' => 'function hook_form_BASE_FORM_ID_alter(&$form, \\Drupal\\Core\\Form\\FormStateInterface $form_state, $form_id)',
      'description' => 'Provide a form-specific alteration for shared (\'base\') forms.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:form',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Form/form.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_form.api.php',
      'body' => '
  // Modification for the form with the given BASE_FORM_ID goes here. For
  // example, if BASE_FORM_ID is "node_form", this code would run on every
  // node form, regardless of node type.

  // Add a checkbox to the node form about agreeing to terms of use.
  $form[\'terms_of_use\'] = [
    \'#type\' => \'checkbox\',
    \'#title\' => t("I agree with the website\'s terms and conditions."),
    \'#required\' => TRUE,
  ];
',
    ),
    'hook_batch_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_batch_alter',
      'definition' => 'function hook_batch_alter(&$batch)',
      'description' => 'Alter batch information before a batch is processed.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:form',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Form/form.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_form.api.php',
      'body' => '
',
    ),
  ),
  'core:module' => 
  array (
    'hook_hook_info' => 
    array (
      'type' => 'hook',
      'name' => 'hook_hook_info',
      'definition' => 'function hook_hook_info()',
      'description' => 'Defines one or more hooks that are exposed by a module.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  $hooks[\'token_info\'] = [
    \'group\' => \'tokens\',
  ];
  $hooks[\'tokens\'] = [
    \'group\' => \'tokens\',
  ];
  return $hooks;
',
    ),
    'hook_module_implements_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_module_implements_alter',
      'definition' => 'function hook_module_implements_alter(&$implementations, $hook)',
      'description' => 'Alter the registry of modules implementing a hook.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  if ($hook == \'form_alter\') {
    // Move my_module_form_alter() to the end of the list.
    // \\Drupal::moduleHandler()->getImplementationInfo()
    // iterates through $implementations with a foreach loop which PHP iterates
    // in the order that the items were added, so to move an item to the end of
    // the array, we remove it and then add it.
    $group = $implementations[\'my_module\'];
    unset($implementations[\'my_module\']);
    $implementations[\'my_module\'] = $group;
  }
',
    ),
    'hook_system_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_system_info_alter',
      'definition' => 'function hook_system_info_alter(array &$info, \\Drupal\\Core\\Extension\\Extension $file, $type)',
      'description' => 'Alter the information parsed from module and theme .info.yml files.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  // Only fill this in if the .info.yml file does not define a \'datestamp\'.
  if (empty($info[\'datestamp\'])) {
    $info[\'datestamp\'] = $file->getFileInfo()->getMTime();
  }
',
    ),
    'hook_module_preinstall' => 
    array (
      'type' => 'hook',
      'name' => 'hook_module_preinstall',
      'definition' => 'function hook_module_preinstall($module, bool $is_syncing)',
      'description' => 'Perform necessary actions before a module is installed.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  my_module_cache_clear();
',
    ),
    'hook_modules_installed' => 
    array (
      'type' => 'hook',
      'name' => 'hook_modules_installed',
      'definition' => 'function hook_modules_installed($modules, $is_syncing)',
      'description' => 'Perform necessary actions after modules are installed.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  if (in_array(\'lousy_module\', $modules)) {
    \\Drupal::state()->set(\'my_module.lousy_module_compatibility\', TRUE);
  }
  if (!$is_syncing) {
    \\Drupal::service(\'my_module.service\')->doSomething($modules);
  }
',
    ),
    'hook_install' => 
    array (
      'type' => 'hook',
      'name' => 'hook_install',
      'definition' => 'function hook_install($is_syncing)',
      'description' => 'Perform setup tasks when the module is installed.',
      'destination' => '%module.install',
      'has_return' => false,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  // Set general module variables.
  \\Drupal::state()->set(\'my_module.foo\', \'bar\');
',
    ),
    'hook_module_preuninstall' => 
    array (
      'type' => 'hook',
      'name' => 'hook_module_preuninstall',
      'definition' => 'function hook_module_preuninstall($module, bool $is_syncing)',
      'description' => 'Perform necessary actions before a module is uninstalled.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  my_module_cache_clear();
',
    ),
    'hook_modules_uninstalled' => 
    array (
      'type' => 'hook',
      'name' => 'hook_modules_uninstalled',
      'definition' => 'function hook_modules_uninstalled($modules, $is_syncing)',
      'description' => 'Perform necessary actions after modules are uninstalled.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  if (in_array(\'lousy_module\', $modules)) {
    \\Drupal::state()->delete(\'my_module.lousy_module_compatibility\');
  }
  my_module_cache_rebuild();
  if (!$is_syncing) {
    \\Drupal::service(\'my_module.service\')->doSomething($modules);
  }
',
    ),
    'hook_uninstall' => 
    array (
      'type' => 'hook',
      'name' => 'hook_uninstall',
      'definition' => 'function hook_uninstall($is_syncing)',
      'description' => 'Remove any information that the module sets.',
      'destination' => '%module.install',
      'has_return' => false,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  // Delete remaining general module variables.
  \\Drupal::state()->delete(\'my_module.foo\');
',
    ),
    'hook_install_tasks' => 
    array (
      'type' => 'hook',
      'name' => 'hook_install_tasks',
      'definition' => 'function hook_install_tasks(&$install_state)',
      'description' => 'Return an array of tasks to be performed by an installation profile.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  // Here, we define a variable to allow tasks to indicate that a particular,
  // processor-intensive batch process needs to be triggered later on in the
  // installation.
  $my_profile_needs_batch_processing = \\Drupal::state()->get(\'my_profile.needs_batch_processing\', FALSE);
  $tasks = [
    // This is an example of a task that defines a form which the user who is
    // installing the site will be asked to fill out. To implement this task,
    // your profile would define a function named my_profile_data_import_form()
    // as a normal form API callback function, with associated validation and
    // submit handlers. In the submit handler, in addition to saving whatever
    // other data you have collected from the user, you might also call
    // \\Drupal::state()->set(\'my_profile.needs_batch_processing\', TRUE) if the
    // user has entered data which requires that batch processing will need to
    // occur later on.
    \'my_profile_data_import_form\' => [
      \'display_name\' => t(\'Data import options\'),
      \'type\' => \'form\',
    ],
    // Similarly, to implement this task, your profile would define a function
    // named my_profile_settings_form() with associated validation and submit
    // handlers. This form might be used to collect and save additional
    // information from the user that your profile needs. There are no extra
    // steps required for your profile to act as an "installation wizard"; you
    // can simply define as many tasks of type \'form\' as you wish to execute,
    // and the forms will be presented to the user, one after another.
    \'my_profile_settings_form\' => [
      \'display_name\' => t(\'Additional options\'),
      \'type\' => \'form\',
    ],
    // This is an example of a task that performs batch operations. To
    // implement this task, your profile would define a function named
    // my_profile_batch_processing() which returns a batch API array definition
    // that the installer will use to execute your batch operations. Due to the
    // \'my_profile.needs_batch_processing\' variable used here, this task will be
    // hidden and skipped unless your profile set it to TRUE in one of the
    // previous tasks.
    \'my_profile_batch_processing\' => [
      \'display_name\' => t(\'Import additional data\'),
      \'display\' => $my_profile_needs_batch_processing,
      \'type\' => \'batch\',
      \'run\' => $my_profile_needs_batch_processing ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ],
    // This is an example of a task that will not be displayed in the list that
    // the user sees. To implement this task, your profile would define a
    // function named my_profile_final_site_setup(), in which additional,
    // automated site setup operations would be performed. Since this is the
    // last task defined by your profile, you should also use this function to
    // call \\Drupal::state()->delete(\'my_profile.needs_batch_processing\') and
    // clean up the state that was used above. If you want the user to pass
    // to the final Drupal installation tasks uninterrupted, return no output
    // from this function. Otherwise, return themed output that the user will
    // see (for example, a confirmation page explaining that your profile\'s
    // tasks are complete, with a link to reload the current page and therefore
    // pass on to the final Drupal installation tasks when the user is ready to
    // do so).
    \'my_profile_final_site_setup\' => [],
  ];
  return $tasks;
',
    ),
    'hook_install_tasks_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_install_tasks_alter',
      'definition' => 'function hook_install_tasks_alter(&$tasks, $install_state)',
      'description' => 'Alter the full list of installation tasks.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  // Replace the entire site configuration form provided by Drupal core
  // with a custom callback function defined by this installation profile.
  $tasks[\'install_configure_form\'][\'function\'] = \'my_profile_install_configure_form\';
',
    ),
    'hook_update_N' => 
    array (
      'type' => 'hook',
      'name' => 'hook_update_N',
      'definition' => 'function hook_update_N(&$sandbox)',
      'description' => 'Perform a single update between minor versions.',
      'destination' => '%module.install',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  // For non-batch updates, the signature can simply be:
  // function hook_update_N() {

  // Example function body for adding a field to a database table, which does
  // not require a batch operation:
  $spec = [
    \'type\' => \'varchar\',
    \'description\' => "New Col",
    \'length\' => 20,
    \'not null\' => FALSE,
  ];
  $schema = \\Drupal\\Core\\Database\\Database::getConnection()->schema();
  $schema->addField(\'my_table\', \'newcol\', $spec);

  // Example of what to do if there is an error during your update.
  if ($some_error_condition_met) {
    throw new \\Drupal\\Core\\Utility\\UpdateException(\'Something went wrong; here is what you should do.\');
  }

  // Example function body for a batch update. In this example, the values in
  // a database field are updated.
  if (!isset($sandbox[\'progress\'])) {
    // This must be the first run. Initialize the sandbox.
    $sandbox[\'progress\'] = 0;
    $sandbox[\'current_pk\'] = 0;
    $sandbox[\'max\'] = \\Drupal\\Core\\Database\\Database::getConnection()->query(\'SELECT COUNT([my_primary_key]) FROM {my_table}\')->fetchField();
  }

  // Update in chunks of 20.
  $records = \\Drupal\\Core\\Database\\Database::getConnection()->select(\'my_table\', \'m\')
    ->fields(\'m\', [\'my_primary_key\', \'other_field\'])
    ->condition(\'my_primary_key\', $sandbox[\'current_pk\'], \'>\')
    ->range(0, 20)
    ->orderBy(\'my_primary_key\', \'ASC\')
    ->execute();
  foreach ($records as $record) {
    // Here, you would make an update something related to this record. In this
    // example, some text is added to the other field.
    \\Drupal\\Core\\Database\\Database::getConnection()->update(\'my_table\')
      ->fields([\'other_field\' => $record->other_field . \'-suffix\'])
      ->condition(\'my_primary_key\', $record->my_primary_key)
      ->execute();

    $sandbox[\'progress\']++;
    $sandbox[\'current_pk\'] = $record->my_primary_key;
  }

  $sandbox[\'#finished\'] = empty($sandbox[\'max\']) ? 1 : ($sandbox[\'progress\'] / $sandbox[\'max\']);

  // To display a message to the user when the update is completed, return it.
  // If you do not want to display a completion message, return nothing.
  return t(\'All foo bars were updated with the new suffix\');
',
    ),
    'hook_post_update_NAME' => 
    array (
      'type' => 'hook',
      'name' => 'hook_post_update_NAME',
      'definition' => 'function hook_post_update_NAME(&$sandbox)',
      'description' => 'Executes an update which is intended to update data, like entities.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  // Example of updating some content.
  $node = \\Drupal\\node\\Entity\\Node::load(123);
  $node->setTitle(\'foo\');
  $node->save();

  $result = t(\'Node %nid saved\', [\'%nid\' => $node->id()]);

  // Example of updating some config.
  if (\\Drupal::moduleHandler()->moduleExists(\'taxonomy\')) {
    // Update the dependencies of all Vocabulary configuration entities.
    \\Drupal::classResolver(\\Drupal\\Core\\Config\\Entity\\ConfigEntityUpdater::class)->update($sandbox, \'taxonomy_vocabulary\');
  }

  return $result;
',
    ),
    'hook_removed_post_updates' => 
    array (
      'type' => 'hook',
      'name' => 'hook_removed_post_updates',
      'definition' => 'function hook_removed_post_updates()',
      'description' => 'Return an array of removed hook_post_update_NAME() function names.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  return [
    \'my_module_post_update_foo\' => \'8.x-2.0\',
    \'my_module_post_update_bar\' => \'8.x-3.0\',
    \'my_module_post_update_baz\' => \'8.x-3.0\',
  ];
',
    ),
    'hook_update_dependencies' => 
    array (
      'type' => 'hook',
      'name' => 'hook_update_dependencies',
      'definition' => 'function hook_update_dependencies()',
      'description' => 'Return an array of information about module update dependencies.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  // Indicate that the my_module_update_8001() function provided by this module
  // must run after the another_module_update_8003() function provided by the
  // \'another_module\' module.
  $dependencies[\'my_module\'][8001] = [
    \'another_module\' => 8003,
  ];
  // Indicate that the my_module_update_8002() function provided by this module
  // must run before the yet_another_module_update_8005() function provided by
  // the \'yet_another_module\' module. (Note that declaring dependencies in this
  // direction should be done only in rare situations, since it can lead to the
  // following problem: If a site has already run the yet_another_module
  // module\'s database updates before it updates its codebase to pick up the
  // newest my_module code, then the dependency declared here will be ignored.)
  $dependencies[\'yet_another_module\'][8005] = [
    \'my_module\' => 8002,
  ];
  return $dependencies;
',
    ),
    'hook_update_last_removed' => 
    array (
      'type' => 'hook',
      'name' => 'hook_update_last_removed',
      'definition' => 'function hook_update_last_removed()',
      'description' => 'Return a number which is no longer available as hook_update_N().',
      'destination' => '%module.install',
      'has_return' => true,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  // We\'ve removed the 8.x-1.x version of my_module, including database updates.
  // The next update function is my_module_update_8200().
  return 8103;
',
    ),
    'hook_updater_info' => 
    array (
      'type' => 'hook',
      'name' => 'hook_updater_info',
      'definition' => 'function hook_updater_info()',
      'description' => 'Provide information on Updaters (classes that can update Drupal).',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  return [
    \'module\' => [
      \'class\' => \'Drupal\\Core\\Updater\\Module\',
      \'name\' => t(\'Update modules\'),
      \'weight\' => 0,
    ],
    \'theme\' => [
      \'class\' => \'Drupal\\Core\\Updater\\Theme\',
      \'name\' => t(\'Update themes\'),
      \'weight\' => 0,
    ],
  ];
',
    ),
    'hook_updater_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_updater_info_alter',
      'definition' => 'function hook_updater_info_alter(&$updaters)',
      'description' => 'Alter the Updater information array.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  // Adjust weight so that the theme Updater gets a chance to handle a given
  // update task before module updaters.
  $updaters[\'theme\'][\'weight\'] = -1;
',
    ),
    'hook_requirements' => 
    array (
      'type' => 'hook',
      'name' => 'hook_requirements',
      'definition' => 'function hook_requirements($phase)',
      'description' => 'Check installation requirements and do status reporting.',
      'destination' => '%module.install',
      'has_return' => true,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  $requirements = [];

  // Report Drupal version
  if ($phase == \'runtime\') {
    $requirements[\'drupal\'] = [
      \'title\' => t(\'Drupal\'),
      \'value\' => \\Drupal::VERSION,
      \'severity\' => REQUIREMENT_INFO,
    ];
  }

  // Test PHP version
  $requirements[\'php\'] = [
    \'title\' => t(\'PHP\'),
    \'value\' => ($phase == \'runtime\') ? \\Drupal\\Core\\Link::fromTextAndUrl(phpversion(), \\Drupal\\Core\\Url::fromRoute(\'system.php\'))->toString() : phpversion(),
  ];
  if (version_compare(phpversion(), \\Drupal::MINIMUM_PHP) < 0) {
    $requirements[\'php\'][\'description\'] = t(\'Your PHP installation is too old. Drupal requires at least PHP %version.\', [\'%version\' => \\Drupal::MINIMUM_PHP]);
    $requirements[\'php\'][\'severity\'] = REQUIREMENT_ERROR;
  }

  // Report cron status
  if ($phase == \'runtime\') {
    $cron_last = \\Drupal::state()->get(\'system.cron_last\');

    if (is_numeric($cron_last)) {
      $requirements[\'cron\'][\'value\'] = t(\'Last run @time ago\', [\'@time\' => \\Drupal::service(\'date.formatter\')->formatTimeDiffSince($cron_last)]);
    }
    else {
      $requirements[\'cron\'] = [
        \'description\' => t(\'Cron has not run. It appears cron jobs have not been setup on your system. Check the help pages for <a href=":url">configuring cron jobs</a>.\', [\':url\' => \'https://www.drupal.org/docs/administering-a-drupal-site/cron-automated-tasks/cron-automated-tasks-overview\']),
        \'severity\' => REQUIREMENT_ERROR,
        \'value\' => t(\'Never run\'),
      ];
    }

    $requirements[\'cron\'][\'description\'] .= \' \' . t(\'You can <a href=":cron">run cron manually</a>.\', [\':cron\' => \\Drupal\\Core\\Url::fromRoute(\'system.run_cron\')->toString()]);

    $requirements[\'cron\'][\'title\'] = t(\'Cron maintenance tasks\');
  }

  return $requirements;
',
    ),
    'hook_requirements_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_requirements_alter',
      'definition' => 'function hook_requirements_alter(array &$requirements): void',
      'description' => 'Alters requirements data.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:module',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Extension/module.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_module.api.php',
      'body' => '
  // Change the title from \'PHP\' to \'PHP version\'.
  $requirements[\'php\'][\'title\'] = t(\'PHP version\');

  // Decrease the \'update status\' requirement severity from warning to info.
  $requirements[\'update status\'][\'severity\'] = REQUIREMENT_INFO;

  // Remove a requirements entry.
  unset($requirements[\'foo\']);
',
    ),
  ),
  'core:theme' => 
  array (
    'hook_form_system_theme_settings_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_form_system_theme_settings_alter',
      'definition' => 'function hook_form_system_theme_settings_alter(&$form, \\Drupal\\Core\\Form\\FormStateInterface $form_state)',
      'description' => 'Allow themes to alter the theme-specific settings form.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Add a checkbox to toggle the breadcrumb trail.
  $form[\'toggle_breadcrumb\'] = [
    \'#type\' => \'checkbox\',
    \'#title\' => t(\'Display the breadcrumb\'),
    \'#default_value\' => theme_get_setting(\'features.breadcrumb\'),
    \'#description\'   => t(\'Show a trail of links from the homepage to the current page.\'),
  ];
',
    ),
    'hook_preprocess' => 
    array (
      'type' => 'hook',
      'name' => 'hook_preprocess',
      'definition' => 'function hook_preprocess(&$variables, $hook)',
      'description' => 'Preprocess theme variables for templates.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  static $hooks;

  // Add contextual links to the variables, if the user has permission.

  if (!\\Drupal::currentUser()->hasPermission(\'access contextual links\')) {
    return;
  }

  if (!isset($hooks)) {
    $hooks = \\Drupal::service(\'theme.registry\')->get();
  }

  // Determine the primary theme function argument.
  if (isset($hooks[$hook][\'variables\'])) {
    $keys = array_keys($hooks[$hook][\'variables\']);
    $key = $keys[0];
  }
  else {
    $key = $hooks[$hook][\'render element\'];
  }

  if (isset($variables[$key])) {
    $element = $variables[$key];
  }

  if (isset($element) && is_array($element) && !empty($element[\'#contextual_links\'])) {
    $variables[\'title_suffix\'][\'contextual_links\'] = contextual_links_view($element);
    if (!empty($variables[\'title_suffix\'][\'contextual_links\'])) {
      $variables[\'attributes\'][\'class\'][] = \'contextual-links-region\';
    }
  }
',
    ),
    'hook_preprocess_HOOK' => 
    array (
      'type' => 'hook',
      'name' => 'hook_preprocess_HOOK',
      'definition' => 'function hook_preprocess_HOOK(&$variables)',
      'description' => 'Preprocess theme variables for a specific theme hook.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // This example is from node_preprocess_html(). It adds the node type to
  // the body classes, when on an individual node page or node preview page.
  if (($node = \\Drupal::routeMatch()->getParameter(\'node\')) || ($node = \\Drupal::routeMatch()->getParameter(\'node_preview\'))) {
    if ($node instanceof NodeInterface) {
      $variables[\'node_type\'] = $node->getType();
    }
  }
',
    ),
    'hook_theme_suggestions_HOOK' => 
    array (
      'type' => 'hook',
      'name' => 'hook_theme_suggestions_HOOK',
      'definition' => 'function hook_theme_suggestions_HOOK(array $variables)',
      'description' => 'Provides alternate named suggestions for a specific theme hook.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  $suggestions = [];

  $suggestions[] = \'hookname__\' . $variables[\'elements\'][\'#langcode\'];

  return $suggestions;
',
    ),
    'hook_theme_suggestions_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_theme_suggestions_alter',
      'definition' => 'function hook_theme_suggestions_alter(array &$suggestions, array &$variables, $hook)',
      'description' => 'Alters named suggestions for all theme hooks.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Add an interface-language specific suggestion to all theme hooks.
  $suggestions[] = $hook . \'__\' . \\Drupal::languageManager()->getCurrentLanguage()->getId();
',
    ),
    'hook_theme_suggestions_HOOK_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_theme_suggestions_HOOK_alter',
      'definition' => 'function hook_theme_suggestions_HOOK_alter(array &$suggestions, array &$variables)',
      'description' => 'Alters named suggestions for a specific theme hook.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => true,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  if (empty($variables[\'header\'])) {
    $suggestions[] = \'hookname__no_header\';
  }
',
    ),
    'hook_themes_installed' => 
    array (
      'type' => 'hook',
      'name' => 'hook_themes_installed',
      'definition' => 'function hook_themes_installed($theme_list)',
      'description' => 'Respond to themes being installed.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  foreach ($theme_list as $theme) {
    block_theme_initialize($theme);
  }
',
    ),
    'hook_themes_uninstalled' => 
    array (
      'type' => 'hook',
      'name' => 'hook_themes_uninstalled',
      'definition' => 'function hook_themes_uninstalled(array $themes)',
      'description' => 'Respond to themes being uninstalled.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Remove some state entries depending on the theme.
  foreach ($themes as $theme) {
    \\Drupal::state()->delete(\'example.\' . $theme);
  }
',
    ),
    'hook_extension' => 
    array (
      'type' => 'hook',
      'name' => 'hook_extension',
      'definition' => 'function hook_extension()',
      'description' => 'Declare a template file extension to be used with a theme engine.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Extension for template base names in Twig.
  return \'.html.twig\';
',
    ),
    'hook_render_template' => 
    array (
      'type' => 'hook',
      'name' => 'hook_render_template',
      'definition' => 'function hook_render_template($template_file, $variables)',
      'description' => 'Render a template using the theme engine.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  $twig_service = \\Drupal::service(\'twig\');

  return $twig_service->loadTemplate($template_file)->render($variables);
',
    ),
    'hook_element_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_element_info_alter',
      'definition' => 'function hook_element_info_alter(array &$info)',
      'description' => 'Alter the element type information returned from modules.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Decrease the default size of textfields.
  if (isset($info[\'textfield\'][\'#size\'])) {
    $info[\'textfield\'][\'#size\'] = 40;
  }
',
    ),
    'hook_element_plugin_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_element_plugin_alter',
      'definition' => 'function hook_element_plugin_alter(array &$definitions)',
      'description' => 'Alter Element plugin definitions.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Use a custom class for the LayoutBuilder element.
  $definitions[\'layout_builder\'][\'class\'] = \'\\Drupal\\my_module\\Element\\MyLayoutBuilderElement\';
',
    ),
    'hook_js_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_js_alter',
      'definition' => 'function hook_js_alter(&$javascript, \\Drupal\\Core\\Asset\\AttachedAssetsInterface $assets, \\Drupal\\Core\\Language\\LanguageInterface $language)',
      'description' => 'Alters JavaScript before it is presented on the page.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Swap out jQuery to use an updated version of the library.
  $javascript[\'core/assets/vendor/jquery/jquery.min.js\'][\'data\'] = \\Drupal::service(\'extension.list.module\')->getPath(\'jquery_update\') . \'/jquery.js\';
',
    ),
    'hook_library_info_build' => 
    array (
      'type' => 'hook',
      'name' => 'hook_library_info_build',
      'definition' => 'function hook_library_info_build()',
      'description' => 'Add dynamic library definitions.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  $libraries = [];
  // Add a library whose information changes depending on certain conditions.
  $libraries[\'zombie\'] = [
    \'dependencies\' => [
      \'core/once\',
    ],
  ];
  if (Drupal::moduleHandler()->moduleExists(\'minifyzombies\')) {
    $libraries[\'zombie\'] += [
      \'js\' => [
        \'zombie.min.js\' => [],
      ],
      \'css\' => [
        \'base\' => [
          \'zombie.min.css\' => [],
        ],
      ],
    ];
  }
  else {
    $libraries[\'zombie\'] += [
      \'js\' => [
        \'zombie.js\' => [],
      ],
      \'css\' => [
        \'base\' => [
          \'zombie.css\' => [],
        ],
      ],
    ];
  }

  // Add a library only if a certain condition is met. If code wants to
  // integrate with this library it is safe to (try to) load it unconditionally
  // without reproducing this check. If the library definition does not exist
  // the library (of course) not be loaded but no notices or errors will be
  // triggered.
  if (Drupal::moduleHandler()->moduleExists(\'vampirize\')) {
    $libraries[\'vampire\'] = [
      \'js\' => [
        \'js/vampire.js\' => [],
      ],
      \'css\' => [
        \'base\' => [
          \'css/vampire.css\',
        ],
      ],
      \'dependencies\' => [
        \'core/jquery\',
      ],
    ];
  }
  return $libraries;
',
    ),
    'hook_js_settings_build' => 
    array (
      'type' => 'hook',
      'name' => 'hook_js_settings_build',
      'definition' => 'function hook_js_settings_build(array &$settings, \\Drupal\\Core\\Asset\\AttachedAssetsInterface $assets)',
      'description' => 'Modify the JavaScript settings (drupalSettings).',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Manipulate settings.
  if (isset($settings[\'dialog\'])) {
    $settings[\'dialog\'][\'autoResize\'] = FALSE;
  }
',
    ),
    'hook_js_settings_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_js_settings_alter',
      'definition' => 'function hook_js_settings_alter(array &$settings, \\Drupal\\Core\\Asset\\AttachedAssetsInterface $assets)',
      'description' => 'Perform necessary alterations to the JavaScript settings (drupalSettings).',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Add settings.
  $settings[\'user\'][\'uid\'] = \\Drupal::currentUser();

  // Manipulate settings.
  if (isset($settings[\'dialog\'])) {
    $settings[\'dialog\'][\'autoResize\'] = FALSE;
  }
',
    ),
    'hook_library_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_library_info_alter',
      'definition' => 'function hook_library_info_alter(&$libraries, $extension)',
      'description' => 'Alter libraries provided by an extension.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Update imaginary library \'foo\' to version 2.0.
  if ($extension === \'core\' && isset($libraries[\'foo\'])) {
    // Verify existing version is older than the one we are updating to.
    if (version_compare($libraries[\'foo\'][\'version\'], \'2.0\', \'<\')) {
      // Update the existing \'foo\' to version 2.0.
      $libraries[\'foo\'][\'version\'] = \'2.0\';
      // To accurately replace library files, the order of files and the options
      // of each file have to be retained; e.g., like this:
      $old_path = \'assets/vendor/foo\';
      // Since the replaced library files are no longer located in a directory
      // relative to the original extension, specify an absolute path (relative
      // to DRUPAL_ROOT / base_path()) to the new location.
      $new_path = \'/\' . \\Drupal::service(\'extension.list.module\')->getPath(\'foo_update\') . \'/js\';
      $new_js = [];
      $replacements = [
        $old_path . \'/foo.js\' => $new_path . \'/foo-2.0.js\',
      ];
      foreach ($libraries[\'foo\'][\'js\'] as $source => $options) {
        if (isset($replacements[$source])) {
          $new_js[$replacements[$source]] = $options;
        }
        else {
          $new_js[$source] = $options;
        }
      }
      $libraries[\'foo\'][\'js\'] = $new_js;
    }
  }
',
    ),
    'hook_css_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_css_alter',
      'definition' => 'function hook_css_alter(&$css, \\Drupal\\Core\\Asset\\AttachedAssetsInterface $assets, \\Drupal\\Core\\Language\\LanguageInterface $language)',
      'description' => 'Alter CSS files before they are output on the page.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Remove defaults.css file.
  $file_path = \\Drupal::service(\'extension.list.module\')->getPath(\'system\') . \'/defaults.css\';
  unset($css[$file_path]);
',
    ),
    'hook_page_attachments' => 
    array (
      'type' => 'hook',
      'name' => 'hook_page_attachments',
      'definition' => 'function hook_page_attachments(array &$attachments)',
      'description' => 'Add attachments (typically assets) to a page before it is rendered.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Unconditionally attach an asset to the page.
  $attachments[\'#attached\'][\'library\'][] = \'core/drupalSettings\';

  // Conditionally attach an asset to the page.
  if (!\\Drupal::currentUser()->hasPermission(\'may pet kittens\')) {
    $attachments[\'#attached\'][\'library\'][] = \'core/jquery\';
  }
',
    ),
    'hook_page_attachments_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_page_attachments_alter',
      'definition' => 'function hook_page_attachments_alter(array &$attachments)',
      'description' => 'Alter attachments (typically assets) to a page before it is rendered.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Conditionally remove an asset.
  if (in_array(\'core/jquery\', $attachments[\'#attached\'][\'library\'])) {
    $index = array_search(\'core/jquery\', $attachments[\'#attached\'][\'library\']);
    unset($attachments[\'#attached\'][\'library\'][$index]);
  }
',
    ),
    'hook_page_top' => 
    array (
      'type' => 'hook',
      'name' => 'hook_page_top',
      'definition' => 'function hook_page_top(array &$page_top)',
      'description' => 'Add a renderable array to the top of the page.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  $page_top[\'my_module\'] = [\'#markup\' => \'This is the top.\'];
',
    ),
    'hook_page_bottom' => 
    array (
      'type' => 'hook',
      'name' => 'hook_page_bottom',
      'definition' => 'function hook_page_bottom(array &$page_bottom)',
      'description' => 'Add a renderable array to the bottom of the page.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  $page_bottom[\'my_module\'] = [\'#markup\' => \'This is the bottom.\'];
',
    ),
    'hook_theme' => 
    array (
      'type' => 'hook',
      'name' => 'hook_theme',
      'definition' => 'function hook_theme($existing, $type, $theme, $path)',
      'description' => 'Register a module or theme\'s theme implementations.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  return [
    \'my_module_display\' => [
      \'variables\' => [\'my_modules\' => NULL, \'topics\' => NULL, \'parents\' => NULL, \'tid\' => NULL, \'sortby\' => NULL, \'my_module_per_page\' => NULL],
    ],
    \'my_module_list\' => [
      \'variables\' => [\'my_modules\' => NULL, \'parents\' => NULL, \'tid\' => NULL],
    ],
    \'my_module_icon\' => [
      \'variables\' => [\'new_posts\' => NULL, \'num_posts\' => 0, \'comment_mode\' => 0, \'sticky\' => 0],
    ],
    \'status_report\' => [
      \'render element\' => \'requirements\',
      \'file\' => \'system.admin.inc\',
    ],
  ];
',
    ),
    'hook_theme_registry_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_theme_registry_alter',
      'definition' => 'function hook_theme_registry_alter(&$theme_registry)',
      'description' => 'Alter the theme registry information returned from hook_theme().',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  // Kill the next/previous my_module topic navigation links.
  foreach ($theme_registry[\'my_module_topic_navigation\'][\'preprocess functions\'] as $key => $value) {
    if ($value == \'template_preprocess_my_module_topic_navigation\') {
      unset($theme_registry[\'my_module_topic_navigation\'][\'preprocess functions\'][$key]);
    }
  }
',
    ),
    'hook_template_preprocess_default_variables_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_template_preprocess_default_variables_alter',
      'definition' => 'function hook_template_preprocess_default_variables_alter(&$variables)',
      'description' => 'Alter the default, hook-independent variables for all templates.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:theme',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Render/theme.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_theme.api.php',
      'body' => '
  $variables[\'is_admin\'] = \\Drupal::currentUser()->hasPermission(\'access administration pages\');
',
    ),
  ),
  'core:token' => 
  array (
    'hook_tokens' => 
    array (
      'type' => 'hook',
      'name' => 'hook_tokens',
      'definition' => 'function hook_tokens($type, $tokens, array $data, array $options, \\Drupal\\Core\\Render\\BubbleableMetadata $bubbleable_metadata)',
      'description' => 'Provide replacement values for placeholder tokens.',
      'destination' => '%module.tokens.inc',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:token',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Utility/token.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_token.api.php',
      'body' => '
  $token_service = \\Drupal::token();

  $url_options = [\'absolute\' => TRUE];
  if (isset($options[\'langcode\'])) {
    $url_options[\'language\'] = \\Drupal::languageManager()->getLanguage($options[\'langcode\']);
    $langcode = $options[\'langcode\'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = [];

  if ($type == \'node\' && !empty($data[\'node\'])) {
    /** @var \\Drupal\\node\\NodeInterface $node */
    $node = $data[\'node\'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the node.
        case \'nid\':
          $replacements[$original] = $node->nid;
          break;

        case \'title\':
          $replacements[$original] = $node->getTitle();
          break;

        case \'edit-url\':
          $replacements[$original] = $node->toUrl(\'edit-form\', $url_options)->toString();
          break;

        // Default values for the chained tokens handled below.
        case \'author\':
          $account = $node->getOwner() ? $node->getOwner() : \\Drupal\\user\\Entity\\User::load(0);
          $replacements[$original] = $account->label();
          $bubbleable_metadata->addCacheableDependency($account);
          break;

        case \'created\':
          $replacements[$original] = \\Drupal::service(\'date.formatter\')->format($node->getCreatedTime(), \'medium\', \'\', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, \'author\')) {
      $replacements += $token_service->generate(\'user\', $author_tokens, [\'user\' => $node->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, \'created\')) {
      $replacements += $token_service->generate(\'date\', $created_tokens, [\'date\' => $node->getCreatedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
',
    ),
    'hook_tokens_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_tokens_alter',
      'definition' => 'function hook_tokens_alter(array &$replacements, array $context, \\Drupal\\Core\\Render\\BubbleableMetadata $bubbleable_metadata)',
      'description' => 'Alter replacement values for placeholder tokens.',
      'destination' => '%module.tokens.inc',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:token',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Utility/token.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_token.api.php',
      'body' => '
  if ($context[\'type\'] == \'node\' && !empty($context[\'data\'][\'node\'])) {
    $node = $context[\'data\'][\'node\'];

    // Alter the [node:title] token, and replace it with the rendered content
    // of a field (field_title).
    if (isset($context[\'tokens\'][\'title\'])) {
      $title = $node->field_title->view(\'default\');
      $replacements[$context[\'tokens\'][\'title\']] = \\Drupal::service(\'renderer\')->render($title);
    }
  }
',
    ),
    'hook_token_info' => 
    array (
      'type' => 'hook',
      'name' => 'hook_token_info',
      'definition' => 'function hook_token_info()',
      'description' => 'Provide information about available placeholder tokens and token types.',
      'destination' => '%module.tokens.inc',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:token',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Utility/token.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_token.api.php',
      'body' => '
  $type = [
    \'name\' => t(\'Nodes\'),
    \'description\' => t(\'Tokens related to individual nodes.\'),
    \'needs-data\' => \'node\',
  ];

  // Core tokens for nodes.
  $node[\'nid\'] = [
    \'name\' => t("Node ID"),
    \'description\' => t("The unique ID of the node."),
  ];
  $node[\'title\'] = [
    \'name\' => t("Title"),
  ];
  $node[\'edit-url\'] = [
    \'name\' => t("Edit URL"),
    \'description\' => t("The URL of the node\'s edit page."),
  ];

  // Chained tokens for nodes.
  $node[\'created\'] = [
    \'name\' => t("Date created"),
    \'type\' => \'date\',
  ];
  $node[\'author\'] = [
    \'name\' => t("Author"),
    \'type\' => \'user\',
  ];

  return [
    \'types\' => [\'node\' => $type],
    \'tokens\' => [\'node\' => $node],
  ];
',
    ),
    'hook_token_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_token_info_alter',
      'definition' => 'function hook_token_info_alter(&$data)',
      'description' => 'Alter the metadata about available placeholder tokens and token types.',
      'destination' => '%module.tokens.inc',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'core:token',
      'core' => true,
      'original_file_path' => 'core/lib/Drupal/Core/Utility/token.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/CORE_token.api.php',
      'body' => '
  // Modify description of node tokens for our site.
  $data[\'tokens\'][\'node\'][\'nid\'] = [
    \'name\' => t("Node ID"),
    \'description\' => t("The unique ID of the article."),
  ];
  $data[\'tokens\'][\'node\'][\'title\'] = [
    \'name\' => t("Title"),
    \'description\' => t("The title of the article."),
  ];

  // Chained tokens for nodes.
  $data[\'tokens\'][\'node\'][\'created\'] = [
    \'name\' => t("Date created"),
    \'description\' => t("The date the article was posted."),
    \'type\' => \'date\',
  ];
',
    ),
  ),
  'help' => 
  array (
    'hook_help' => 
    array (
      'type' => 'hook',
      'name' => 'hook_help',
      'definition' => 'function hook_help($route_name, \\Drupal\\Core\\Routing\\RouteMatchInterface $route_match)',
      'description' => 'Provide online user help.',
      'destination' => '%module.module',
      'has_return' => true,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'help',
      'core' => true,
      'original_file_path' => 'core/modules/help/help.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/help.api.php',
      'body' => '
  switch ($route_name) {
    // Main module help for the block module.
    case \'help.page.block\':
      return \'<p>\' . t(\'Blocks are boxes of content rendered into an area, or region, of a web page. The default theme Olivero, for example, implements the regions "Sidebar", "Highlighted", "Content", "Header", "Footer Top", "Footer Bottom", etc., and a block may appear in any one of these areas. The <a href=":blocks">blocks administration page</a> provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions.\', [\':blocks\' => \\Drupal\\Core\\Url::fromRoute(\'block.admin_display\')->toString()]) . \'</p>\';

    // Help for another path in the block module.
    case \'block.admin_display\':
      return \'<p>\' . t(\'This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.\') . \'</p>\';
  }
',
    ),
    'hook_help_section_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_help_section_info_alter',
      'definition' => 'function hook_help_section_info_alter(array &$info)',
      'description' => 'Perform alterations on help page section plugin definitions.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'help',
      'core' => true,
      'original_file_path' => 'core/modules/help/help.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/help.api.php',
      'body' => '
  // Alter the header for the module overviews section.
  $info[\'hook_help\'][\'title\'] = t(\'Overviews of modules\');
  // Move the module overviews section to the end.
  $info[\'hook_help\'][\'weight\'] = 500;
',
    ),
    'hook_help_topics_info_alter' => 
    array (
      'type' => 'hook',
      'name' => 'hook_help_topics_info_alter',
      'definition' => 'function hook_help_topics_info_alter(array &$info)',
      'description' => 'Perform alterations on help topic definitions.',
      'destination' => '%module.module',
      'has_return' => false,
      'procedural' => false,
      'dependencies' => 
      array (
      ),
      'group' => 'help',
      'core' => true,
      'original_file_path' => 'core/modules/help/help.api.php',
      'file_path' => '/Users/joachim/Sites/dcb-repos-9/repos/drupal-code-builder/Test/sample_hook_definitions/11/help.api.php',
      'body' => '
  // Alter the help topic to be displayed on admin/help.
  $info[\'example.help_topic\'][\'top_level\'] = TRUE;
',
    ),
  ),
);