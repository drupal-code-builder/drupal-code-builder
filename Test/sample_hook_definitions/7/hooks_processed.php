<?php $data =
array (
  'block' =>
  array (
    'hook_block_info' =>
    array (
      'type' => 'hook',
      'name' => 'hook_block_info',
      'definition' => 'function hook_block_info()',
      'description' => 'Define all blocks provided by the module.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
        0 => 'hook_block_info',
      ),
      'group' => 'block',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/block.api.php',
      'body' => '
  // This example comes from node.module.
  $blocks[\'syndicate\'] = array(
    \'info\' => t(\'Syndicate\'),
    \'cache\' => DRUPAL_NO_CACHE
  );

  $blocks[\'recent\'] = array(
    \'info\' => t(\'Recent content\'),
    // DRUPAL_CACHE_PER_ROLE will be assumed.
  );

  return $blocks;
',
    ),
    'hook_block_info_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_block_info_alter',
      'definition' => 'function hook_block_info_alter(&$blocks, $theme, $code_blocks)',
      'description' => 'Change block definition before saving to the database.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
        0 => 'hook_block_info',
      ),
      'group' => 'block',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/block.api.php',
      'body' => '
  // Disable the login block.
  $blocks[\'user\'][\'login\'][\'status\'] = 0;
',
    ),
    'hook_block_configure' =>
    array (
      'type' => 'hook',
      'name' => 'hook_block_configure',
      'definition' => 'function hook_block_configure($delta = \'\')',
      'description' => 'Define a configuration form for a block.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
        0 => 'hook_block_info',
      ),
      'group' => 'block',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/block.api.php',
      'body' => '
  // This example comes from node.module.
  $form = array();
  if ($delta == \'recent\') {
    $form[\'node_recent_block_count\'] = array(
      \'#type\' => \'select\',
      \'#title\' => t(\'Number of recent content items to display\'),
      \'#default_value\' => variable_get(\'node_recent_block_count\', 10),
      \'#options\' => drupal_map_assoc(array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 25, 30)),
    );
  }
  return $form;
',
    ),
    'hook_block_save' =>
    array (
      'type' => 'hook',
      'name' => 'hook_block_save',
      'definition' => 'function hook_block_save($delta = \'\', $edit = array())',
      'description' => 'Save the configuration options from hook_block_configure().',
      'destination' => '%module.module',
      'dependencies' =>
      array (
        0 => 'hook_block_info',
      ),
      'group' => 'block',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/block.api.php',
      'body' => '
  // This example comes from node.module.
  if ($delta == \'recent\') {
    variable_set(\'node_recent_block_count\', $edit[\'node_recent_block_count\']);
  }
',
    ),
    'hook_block_view' =>
    array (
      'type' => 'hook',
      'name' => 'hook_block_view',
      'definition' => 'function hook_block_view($delta = \'\')',
      'description' => 'Return a rendered or renderable view of a block.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
        0 => 'hook_block_info',
      ),
      'group' => 'block',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/block.api.php',
      'body' => '
  // This example is adapted from node.module.
  $block = array();

  switch ($delta) {
    case \'syndicate\':
      $block[\'subject\'] = t(\'Syndicate\');
      $block[\'content\'] = array(
        \'#theme\' => \'feed_icon\',
        \'#url\' => \'rss.xml\',
        \'#title\' => t(\'Syndicate\'),
      );
      break;

    case \'recent\':
      if (user_access(\'access content\')) {
        $block[\'subject\'] = t(\'Recent content\');
        if ($nodes = node_get_recent(variable_get(\'node_recent_block_count\', 10))) {
          $block[\'content\'] = array(
            \'#theme\' => \'node_recent_block\',
            \'#nodes\' => $nodes,
          );
        } else {
          $block[\'content\'] = t(\'No content available.\');
        }
      }
      break;
  }
  return $block;
',
    ),
    'hook_block_view_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_block_view_alter',
      'definition' => 'function hook_block_view_alter(&$data, $block)',
      'description' => 'Perform alterations to the content of a block.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
        0 => 'hook_block_info',
      ),
      'group' => 'block',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/block.api.php',
      'body' => '
  // Remove the contextual links on all blocks that provide them.
  if (is_array($data[\'content\']) && isset($data[\'content\'][\'#contextual_links\'])) {
    unset($data[\'content\'][\'#contextual_links\']);
  }
  // Add a theme wrapper function defined by the current module to all blocks
  // provided by the "somemodule" module.
  if (is_array($data[\'content\']) && $block->module == \'somemodule\') {
    $data[\'content\'][\'#theme_wrappers\'][] = \'mymodule_special_block\';
  }
',
    ),
    'hook_block_view_MODULE_DELTA_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_block_view_MODULE_DELTA_alter',
      'definition' => 'function hook_block_view_MODULE_DELTA_alter(&$data, $block)',
      'description' => 'Perform alterations to a specific block.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
        0 => 'hook_block_info',
      ),
      'group' => 'block',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/block.api.php',
      'body' => '
  // This code will only run for a specific block. For example, if MODULE_DELTA
  // in the function definition above is set to "mymodule_somedelta", the code
  // will only run on the "somedelta" block provided by the "mymodule" module.

  // Change the title of the "somedelta" block provided by the "mymodule"
  // module.
  $data[\'subject\'] = t(\'New title of the block\');
',
    ),
    'hook_block_list_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_block_list_alter',
      'definition' => 'function hook_block_list_alter(&$blocks)',
      'description' => 'Act on blocks prior to rendering.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
        0 => 'hook_block_info',
      ),
      'group' => 'block',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/block.api.php',
      'body' => '
  global $language, $theme_key;

  // This example shows how to achieve language specific visibility setting for
  // blocks.

  $result = db_query(\'SELECT module, delta, language FROM {my_table}\');
  $block_languages = array();
  foreach ($result as $record) {
    $block_languages[$record->module][$record->delta][$record->language] = TRUE;
  }

  foreach ($blocks as $key => $block) {
    // Any module using this alter should inspect the data before changing it,
    // to ensure it is what they expect.
    if (!isset($block->theme) || !isset($block->status) || $block->theme != $theme_key || $block->status != 1) {
      // This block was added by a contrib module, leave it in the list.
      continue;
    }

    if (!isset($block_languages[$block->module][$block->delta])) {
      // No language setting for this block, leave it in the list.
      continue;
    }

    if (!isset($block_languages[$block->module][$block->delta][$language->language])) {
      // This block should not be displayed with the active language, remove
      // from the list.
      unset($blocks[$key]);
    }
  }
',
    ),
  ),
  'system' =>
  array (
    'hook_hook_info' =>
    array (
      'type' => 'hook',
      'name' => 'hook_hook_info',
      'definition' => 'function hook_hook_info()',
      'description' => 'Defines one or more hooks that are exposed by a module.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $hooks[\'token_info\'] = array(
    \'group\' => \'tokens\',
  );
  $hooks[\'tokens\'] = array(
    \'group\' => \'tokens\',
  );
  return $hooks;
',
    ),
    'hook_hook_info_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_hook_info_alter',
      'definition' => 'function hook_hook_info_alter(&$hooks)',
      'description' => 'Alter information from hook_hook_info().',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Our module wants to completely override the core tokens, so make
  // sure the core token hooks are not found.
  $hooks[\'token_info\'][\'group\'] = \'mytokens\';
  $hooks[\'tokens\'][\'group\'] = \'mytokens\';
',
    ),
    'hook_entity_info' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_info',
      'definition' => 'function hook_entity_info()',
      'description' => 'Inform the base system and the Field API about one or more entity types.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
        0 => 'callback_entity_info_uri',
        1 => 'callback_entity_info_label',
        2 => 'callback_entity_info_language',
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $return = array(
    \'node\' => array(
      \'label\' => t(\'Node\'),
      \'controller class\' => \'NodeController\',
      \'base table\' => \'node\',
      \'revision table\' => \'node_revision\',
      \'uri callback\' => \'node_uri\',
      \'fieldable\' => TRUE,
      \'translation\' => array(
        \'locale\' => TRUE,
      ),
      \'entity keys\' => array(
        \'id\' => \'nid\',
        \'revision\' => \'vid\',
        \'bundle\' => \'type\',
        \'language\' => \'language\',
      ),
      \'bundle keys\' => array(
        \'bundle\' => \'type\',
      ),
      \'bundles\' => array(),
      \'view modes\' => array(
        \'full\' => array(
          \'label\' => t(\'Full content\'),
          \'custom settings\' => FALSE,
        ),
        \'teaser\' => array(
          \'label\' => t(\'Teaser\'),
          \'custom settings\' => TRUE,
        ),
        \'rss\' => array(
          \'label\' => t(\'RSS\'),
          \'custom settings\' => FALSE,
        ),
      ),
    ),
  );

  // Search integration is provided by node.module, so search-related
  // view modes for nodes are defined here and not in search.module.
  if (module_exists(\'search\')) {
    $return[\'node\'][\'view modes\'] += array(
      \'search_index\' => array(
        \'label\' => t(\'Search index\'),
        \'custom settings\' => FALSE,
      ),
      \'search_result\' => array(
        \'label\' => t(\'Search result highlighting input\'),
        \'custom settings\' => FALSE,
      ),
    );
  }

  // Bundles must provide a human readable name so we can create help and error
  // messages, and the path to attach Field admin pages to.
  foreach (node_type_get_names() as $type => $name) {
    $return[\'node\'][\'bundles\'][$type] = array(
      \'label\' => $name,
      \'admin\' => array(
        \'path\' => \'admin/structure/types/manage/%node_type\',
        \'real path\' => \'admin/structure/types/manage/\' . str_replace(\'_\', \'-\', $type),
        \'bundle argument\' => 4,
        \'access arguments\' => array(\'administer content types\'),
      ),
    );
  }

  return $return;
',
    ),
    'hook_entity_info_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_info_alter',
      'definition' => 'function hook_entity_info_alter(&$entity_info)',
      'description' => 'Alter the entity info.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Set the controller class for nodes to an alternate implementation of the
  // DrupalEntityController interface.
  $entity_info[\'node\'][\'controller class\'] = \'MyCustomNodeController\';
',
    ),
    'hook_entity_load' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_load',
      'definition' => 'function hook_entity_load($entities, $type)',
      'description' => 'Act on entities when loaded.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  foreach ($entities as $entity) {
    $entity->foo = mymodule_add_something($entity, $type);
  }
',
    ),
    'hook_entity_presave' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_presave',
      'definition' => 'function hook_entity_presave($entity, $type)',
      'description' => 'Act on an entity before it is about to be created or updated.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $entity->changed = REQUEST_TIME;
',
    ),
    'hook_entity_insert' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_insert',
      'definition' => 'function hook_entity_insert($entity, $type)',
      'description' => 'Act on entities when inserted.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Insert the new entity into a fictional table of all entities.
  $info = entity_get_info($type);
  list($id) = entity_extract_ids($type, $entity);
  db_insert(\'example_entity\')
    ->fields(array(
      \'type\' => $type,
      \'id\' => $id,
      \'created\' => REQUEST_TIME,
      \'updated\' => REQUEST_TIME,
    ))
    ->execute();
',
    ),
    'hook_entity_update' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_update',
      'definition' => 'function hook_entity_update($entity, $type)',
      'description' => 'Act on entities when updated.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Update the entity\'s entry in a fictional table of all entities.
  $info = entity_get_info($type);
  list($id) = entity_extract_ids($type, $entity);
  db_update(\'example_entity\')
    ->fields(array(
      \'updated\' => REQUEST_TIME,
    ))
    ->condition(\'type\', $type)
    ->condition(\'id\', $id)
    ->execute();
',
    ),
    'hook_entity_delete' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_delete',
      'definition' => 'function hook_entity_delete($entity, $type)',
      'description' => 'Act on entities when deleted.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Delete the entity\'s entry from a fictional table of all entities.
  $info = entity_get_info($type);
  list($id) = entity_extract_ids($type, $entity);
  db_delete(\'example_entity\')
    ->condition(\'type\', $type)
    ->condition(\'id\', $id)
    ->execute();
',
    ),
    'hook_entity_query_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_query_alter',
      'definition' => 'function hook_entity_query_alter($query)',
      'description' => 'Alter or execute an EntityFieldQuery.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $query->executeCallback = \'my_module_query_callback\';
',
    ),
    'hook_entity_view' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_view',
      'definition' => 'function hook_entity_view($entity, $type, $view_mode, $langcode)',
      'description' => 'Act on entities being assembled before rendering.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $entity->content[\'my_additional_field\'] = array(
    \'#markup\' => $additional_field,
    \'#weight\' => 10,
    \'#theme\' => \'mymodule_my_additional_field\',
  );
',
    ),
    'hook_entity_view_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_view_alter',
      'definition' => 'function hook_entity_view_alter(&$build, $type)',
      'description' => 'Alter the results of ENTITY_view().',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  if ($build[\'#view_mode\'] == \'full\' && isset($build[\'an_additional_field\'])) {
    // Change its weight.
    $build[\'an_additional_field\'][\'#weight\'] = -10;

    // Add a #post_render callback to act on the rendered HTML of the entity.
    $build[\'#post_render\'][] = \'my_module_node_post_render\';
  }
',
    ),
    'hook_entity_view_mode_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_view_mode_alter',
      'definition' => 'function hook_entity_view_mode_alter(&$view_mode, $context)',
      'description' => 'Change the view mode of an entity that is being displayed.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // For nodes, change the view mode when it is teaser.
  if ($context[\'entity_type\'] == \'node\' && $view_mode == \'teaser\') {
    $view_mode = \'my_custom_view_mode\';
  }
',
    ),
    'hook_admin_paths' =>
    array (
      'type' => 'hook',
      'name' => 'hook_admin_paths',
      'definition' => 'function hook_admin_paths()',
      'description' => 'Define administrative paths.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $paths = array(
    \'mymodule/*/add\' => TRUE,
    \'mymodule/*/edit\' => TRUE,
  );
  return $paths;
',
    ),
    'hook_admin_paths_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_admin_paths_alter',
      'definition' => 'function hook_admin_paths_alter(&$paths)',
      'description' => 'Redefine administrative paths defined by other modules.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Treat all user pages as administrative.
  $paths[\'user\'] = TRUE;
  $paths[\'user/*\'] = TRUE;
  // Treat the forum topic node form as a non-administrative page.
  $paths[\'node/add/forum\'] = FALSE;
',
    ),
    'hook_entity_prepare_view' =>
    array (
      'type' => 'hook',
      'name' => 'hook_entity_prepare_view',
      'definition' => 'function hook_entity_prepare_view($entities, $type, $langcode)',
      'description' => 'Act on entities as they are being prepared for view.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Load a specific node into the user object for later theming.
  if ($type == \'user\') {
    $nodes = mymodule_get_user_nodes(array_keys($entities));
    foreach ($entities as $uid => $entity) {
      $entity->user_node = $nodes[$uid];
    }
  }
',
    ),
    'hook_cron' =>
    array (
      'type' => 'hook',
      'name' => 'hook_cron',
      'definition' => 'function hook_cron()',
      'description' => 'Perform periodic actions.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Short-running operation example, not using a queue:
  // Delete all expired records since the last cron run.
  $expires = variable_get(\'mymodule_cron_last_run\', REQUEST_TIME);
  db_delete(\'mymodule_table\')
    ->condition(\'expires\', $expires, \'>=\')
    ->execute();
  variable_set(\'mymodule_cron_last_run\', REQUEST_TIME);

  // Long-running operation example, leveraging a queue:
  // Fetch feeds from other sites.
  $result = db_query(\'SELECT * FROM {aggregator_feed} WHERE checked + refresh < :time AND refresh <> :never\', array(
    \':time\' => REQUEST_TIME,
    \':never\' => AGGREGATOR_CLEAR_NEVER,
  ));
  $queue = DrupalQueue::get(\'aggregator_feeds\');
  foreach ($result as $feed) {
    $queue->createItem($feed);
  }
',
    ),
    'hook_cron_queue_info' =>
    array (
      'type' => 'hook',
      'name' => 'hook_cron_queue_info',
      'definition' => 'function hook_cron_queue_info()',
      'description' => 'Declare queues holding items that need to be run periodically.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
        0 => 'callback_queue_worker',
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $queues[\'aggregator_feeds\'] = array(
    \'worker callback\' => \'aggregator_refresh\',
    \'time\' => 60,
  );
  return $queues;
',
    ),
    'hook_cron_queue_info_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_cron_queue_info_alter',
      'definition' => 'function hook_cron_queue_info_alter(&$queues)',
      'description' => 'Alter cron queue information before cron runs.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // This site has many feeds so let\'s spend 90 seconds on each cron run
  // updating feeds instead of the default 60.
  $queues[\'aggregator_feeds\'][\'time\'] = 90;
',
    ),
    'hook_element_info' =>
    array (
      'type' => 'hook',
      'name' => 'hook_element_info',
      'definition' => 'function hook_element_info()',
      'description' => 'Allows modules to declare their own Form API element types and specify their',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $types[\'filter_format\'] = array(
    \'#input\' => TRUE,
  );
  return $types;
',
    ),
    'hook_element_info_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_element_info_alter',
      'definition' => 'function hook_element_info_alter(&$type)',
      'description' => 'Alter the element type information returned from modules.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Decrease the default size of textfields.
  if (isset($type[\'textfield\'][\'#size\'])) {
    $type[\'textfield\'][\'#size\'] = 40;
  }
',
    ),
    'hook_exit' =>
    array (
      'type' => 'hook',
      'name' => 'hook_exit',
      'definition' => 'function hook_exit($destination = NULL)',
      'description' => 'Perform cleanup tasks.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  db_update(\'counter\')
    ->expression(\'hits\', \'hits + 1\')
    ->condition(\'type\', 1)
    ->execute();
',
    ),
    'hook_js_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_js_alter',
      'definition' => 'function hook_js_alter(&$javascript)',
      'description' => 'Perform necessary alterations to the JavaScript before it is presented on',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Swap out jQuery to use an updated version of the library.
  $javascript[\'misc/jquery.js\'][\'data\'] = drupal_get_path(\'module\', \'jquery_update\') . \'/jquery.js\';
',
    ),
    'hook_library' =>
    array (
      'type' => 'hook',
      'name' => 'hook_library',
      'definition' => 'function hook_library()',
      'description' => 'Registers JavaScript/CSS libraries associated with a module.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Library One.
  $libraries[\'library-1\'] = array(
    \'title\' => \'Library One\',
    \'website\' => \'http://example.com/library-1\',
    \'version\' => \'1.2\',
    \'js\' => array(
      drupal_get_path(\'module\', \'my_module\') . \'/library-1.js\' => array(),
    ),
    \'css\' => array(
      drupal_get_path(\'module\', \'my_module\') . \'/library-2.css\' => array(
        \'type\' => \'file\',
        \'media\' => \'screen\',
      ),
    ),
  );
  // Library Two.
  $libraries[\'library-2\'] = array(
    \'title\' => \'Library Two\',
    \'website\' => \'http://example.com/library-2\',
    \'version\' => \'3.1-beta1\',
    \'js\' => array(
      // JavaScript settings may use the \'data\' key.
      array(
        \'type\' => \'setting\',
        \'data\' => array(\'library2\' => TRUE),
      ),
    ),
    \'dependencies\' => array(
      // Require jQuery UI core by System module.
      array(\'system\', \'ui\'),
      // Require our other library.
      array(\'my_module\', \'library-1\'),
      // Require another library.
      array(\'other_module\', \'library-3\'),
    ),
  );
  return $libraries;
',
    ),
    'hook_library_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_library_alter',
      'definition' => 'function hook_library_alter(&$libraries, $module)',
      'description' => 'Alters the JavaScript/CSS library registry.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Update Farbtastic to version 2.0.
  if ($module == \'system\' && isset($libraries[\'farbtastic\'])) {
    // Verify existing version is older than the one we are updating to.
    if (version_compare($libraries[\'farbtastic\'][\'version\'], \'2.0\', \'<\')) {
      // Update the existing Farbtastic to version 2.0.
      $libraries[\'farbtastic\'][\'version\'] = \'2.0\';
      $libraries[\'farbtastic\'][\'js\'] = array(
        drupal_get_path(\'module\', \'farbtastic_update\') . \'/farbtastic-2.0.js\' => array(),
      );
    }
  }
',
    ),
    'hook_css_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_css_alter',
      'definition' => 'function hook_css_alter(&$css)',
      'description' => 'Alter CSS files before they are output on the page.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Remove defaults.css file.
  unset($css[drupal_get_path(\'module\', \'system\') . \'/defaults.css\']);
',
    ),
    'hook_ajax_render_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_ajax_render_alter',
      'definition' => 'function hook_ajax_render_alter(&$commands)',
      'description' => 'Alter the commands that are sent to the user through the Ajax framework.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Inject any new status messages into the content area.
  $commands[] = ajax_command_prepend(\'#block-system-main .content\', theme(\'status_messages\'));
',
    ),
    'hook_page_build' =>
    array (
      'type' => 'hook',
      'name' => 'hook_page_build',
      'definition' => 'function hook_page_build(&$page)',
      'description' => 'Add elements to a page before it is rendered.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  if (menu_get_object(\'node\', 1)) {
    // We are on a node detail page. Append a standard disclaimer to the
    // content region.
    $page[\'content\'][\'disclaimer\'] = array(
      \'#markup\' => t(\'Acme, Inc. is not responsible for the contents of this sample code.\'),
      \'#weight\' => 25,
    );
  }
',
    ),
    'hook_menu_get_item_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_menu_get_item_alter',
      'definition' => 'function hook_menu_get_item_alter(&$router_item, $path, $original_map)',
      'description' => 'Alter a menu router item right after it has been retrieved from the database or cache.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // When retrieving the router item for the current path...
  if ($path == $_GET[\'q\']) {
    // ...call a function that prepares something for this request.
    mymodule_prepare_something();
  }
',
    ),
    'hook_menu' =>
    array (
      'type' => 'hook',
      'name' => 'hook_menu',
      'definition' => 'function hook_menu()',
      'description' => 'Define menu items and page callbacks.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $items[\'example\'] = array(
    \'title\' => \'Example Page\',
    \'page callback\' => \'example_page\',
    \'access arguments\' => array(\'access content\'),
    \'type\' => MENU_SUGGESTED_ITEM,
  );
  $items[\'example/feed\'] = array(
    \'title\' => \'Example RSS feed\',
    \'page callback\' => \'example_feed\',
    \'access arguments\' => array(\'access content\'),
    \'type\' => MENU_CALLBACK,
  );

  return $items;
',
    ),
    'hook_menu_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_menu_alter',
      'definition' => 'function hook_menu_alter(&$items)',
      'description' => 'Alter the data being saved to the {menu_router} table after hook_menu is invoked.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Example - disable the page at node/add
  $items[\'node/add\'][\'access callback\'] = FALSE;
',
    ),
    'hook_menu_link_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_menu_link_alter',
      'definition' => 'function hook_menu_link_alter(&$item)',
      'description' => 'Alter the data being saved to the {menu_links} table by menu_link_save().',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Make all new admin links hidden (a.k.a disabled).
  if (strpos($item[\'link_path\'], \'admin\') === 0 && empty($item[\'mlid\'])) {
    $item[\'hidden\'] = 1;
  }
  // Flag a link to be altered by hook_translated_menu_link_alter().
  if ($item[\'link_path\'] == \'devel/cache/clear\') {
    $item[\'options\'][\'alter\'] = TRUE;
  }
  // Flag a link to be altered by hook_translated_menu_link_alter(), but only
  // if it is derived from a menu router item; i.e., do not alter a custom
  // menu link pointing to the same path that has been created by a user.
  if ($item[\'link_path\'] == \'user\' && $item[\'module\'] == \'system\') {
    $item[\'options\'][\'alter\'] = TRUE;
  }
',
    ),
    'hook_translated_menu_link_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_translated_menu_link_alter',
      'definition' => 'function hook_translated_menu_link_alter(&$item, $map)',
      'description' => 'Alter a menu link after it has been translated and before it is rendered.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  if ($item[\'href\'] == \'devel/cache/clear\') {
    $item[\'localized_options\'][\'query\'] = drupal_get_destination();
  }
',
    ),
    'hook_menu_link_insert' =>
    array (
      'type' => 'hook',
      'name' => 'hook_menu_link_insert',
      'definition' => 'function hook_menu_link_insert($link)',
      'description' => 'Inform modules that a menu link has been created.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // In our sample case, we track menu items as editing sections
  // of the site. These are stored in our table as \'disabled\' items.
  $record[\'mlid\'] = $link[\'mlid\'];
  $record[\'menu_name\'] = $link[\'menu_name\'];
  $record[\'status\'] = 0;
  drupal_write_record(\'menu_example\', $record);
',
    ),
    'hook_menu_link_update' =>
    array (
      'type' => 'hook',
      'name' => 'hook_menu_link_update',
      'definition' => 'function hook_menu_link_update($link)',
      'description' => 'Inform modules that a menu link has been updated.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // If the parent menu has changed, update our record.
  $menu_name = db_query("SELECT menu_name FROM {menu_example} WHERE mlid = :mlid", array(\':mlid\' => $link[\'mlid\']))->fetchField();
  if ($menu_name != $link[\'menu_name\']) {
    db_update(\'menu_example\')
      ->fields(array(\'menu_name\' => $link[\'menu_name\']))
      ->condition(\'mlid\', $link[\'mlid\'])
      ->execute();
  }
',
    ),
    'hook_menu_link_delete' =>
    array (
      'type' => 'hook',
      'name' => 'hook_menu_link_delete',
      'definition' => 'function hook_menu_link_delete($link)',
      'description' => 'Inform modules that a menu link has been deleted.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Delete the record from our table.
  db_delete(\'menu_example\')
    ->condition(\'mlid\', $link[\'mlid\'])
    ->execute();
',
    ),
    'hook_menu_local_tasks_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_menu_local_tasks_alter',
      'definition' => 'function hook_menu_local_tasks_alter(&$data, $router_item, $root_path)',
      'description' => 'Alter tabs and actions displayed on the page before they are rendered.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Add an action linking to node/add to all pages.
  $data[\'actions\'][\'output\'][] = array(
    \'#theme\' => \'menu_local_task\',
    \'#link\' => array(
      \'title\' => t(\'Add new content\'),
      \'href\' => \'node/add\',
      \'localized_options\' => array(
        \'attributes\' => array(
          \'title\' => t(\'Add new content\'),
        ),
      ),
    ),
  );

  // Add a tab linking to node/add to all pages.
  $data[\'tabs\'][0][\'output\'][] = array(
    \'#theme\' => \'menu_local_task\',
    \'#link\' => array(
      \'title\' => t(\'Example tab\'),
      \'href\' => \'node/add\',
      \'localized_options\' => array(
        \'attributes\' => array(
          \'title\' => t(\'Add new content\'),
        ),
      ),
    ),
    // Define whether this link is active. This can be omitted for
    // implementations that add links to pages outside of the current page
    // context.
    \'#active\' => ($router_item[\'path\'] == $root_path),
  );
',
    ),
    'hook_menu_breadcrumb_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_menu_breadcrumb_alter',
      'definition' => 'function hook_menu_breadcrumb_alter(&$active_trail, $item)',
      'description' => 'Alter links in the active trail before it is rendered as the breadcrumb.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Always display a link to the current page by duplicating the last link in
  // the active trail. This means that menu_get_active_breadcrumb() will remove
  // the last link (for the current page), but since it is added once more here,
  // it will appear.
  if (!drupal_is_front_page()) {
    $end = end($active_trail);
    if ($item[\'href\'] == $end[\'href\']) {
      $active_trail[] = $end;
    }
  }
',
    ),
    'hook_menu_contextual_links_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_menu_contextual_links_alter',
      'definition' => 'function hook_menu_contextual_links_alter(&$links, $router_item, $root_path)',
      'description' => 'Alter contextual links before they are rendered.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Add a link to all contextual links for nodes.
  if ($root_path == \'node/%\') {
    $links[\'foo\'] = array(
      \'title\' => t(\'Do fu\'),
      \'href\' => \'foo/do\',
      \'localized_options\' => array(
        \'query\' => array(
          \'foo\' => \'bar\',
        ),
      ),
    );
  }
',
    ),
    'hook_page_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_page_alter',
      'definition' => 'function hook_page_alter(&$page)',
      'description' => 'Perform alterations before a page is rendered.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Add help text to the user login block.
  $page[\'sidebar_first\'][\'user_login\'][\'help\'] = array(
    \'#weight\' => -10,
    \'#markup\' => t(\'To post comments or add new content, you first have to log in.\'),
  );
',
    ),
    'hook_form_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_form_alter',
      'definition' => 'function hook_form_alter(&$form, &$form_state, $form_id)',
      'description' => 'Perform alterations before a form is rendered.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  if (isset($form[\'type\']) && $form[\'type\'][\'#value\'] . \'_node_settings\' == $form_id) {
    $form[\'workflow\'][\'upload_\' . $form[\'type\'][\'#value\']] = array(
      \'#type\' => \'radios\',
      \'#title\' => t(\'Attachments\'),
      \'#default_value\' => variable_get(\'upload_\' . $form[\'type\'][\'#value\'], 1),
      \'#options\' => array(t(\'Disabled\'), t(\'Enabled\')),
    );
  }
',
    ),
    'hook_form_FORM_ID_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_form_FORM_ID_alter',
      'definition' => 'function hook_form_FORM_ID_alter(&$form, &$form_state, $form_id)',
      'description' => 'Provide a form-specific alteration instead of the global hook_form_alter().',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Modification for the form with the given form ID goes here. For example, if
  // FORM_ID is "user_register_form" this code would run only on the user
  // registration form.

  // Add a checkbox to registration form about agreeing to terms of use.
  $form[\'terms_of_use\'] = array(
    \'#type\' => \'checkbox\',
    \'#title\' => t("I agree with the website\'s terms and conditions."),
    \'#required\' => TRUE,
  );
',
    ),
    'hook_form_BASE_FORM_ID_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_form_BASE_FORM_ID_alter',
      'definition' => 'function hook_form_BASE_FORM_ID_alter(&$form, &$form_state, $form_id)',
      'description' => 'Provide a form-specific alteration for shared (\'base\') forms.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Modification for the form with the given BASE_FORM_ID goes here. For
  // example, if BASE_FORM_ID is "node_form", this code would run on every
  // node form, regardless of node type.

  // Add a checkbox to the node form about agreeing to terms of use.
  $form[\'terms_of_use\'] = array(
    \'#type\' => \'checkbox\',
    \'#title\' => t("I agree with the website\'s terms and conditions."),
    \'#required\' => TRUE,
  );
',
    ),
    'hook_forms' =>
    array (
      'type' => 'hook',
      'name' => 'hook_forms',
      'definition' => 'function hook_forms($form_id, $args)',
      'description' => 'Map form_ids to form builder functions.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Simply reroute the (non-existing) $form_id \'mymodule_first_form\' to
  // \'mymodule_main_form\'.
  $forms[\'mymodule_first_form\'] = array(
    \'callback\' => \'mymodule_main_form\',
  );

  // Reroute the $form_id and prepend an additional argument that gets passed to
  // the \'mymodule_main_form\' form builder function.
  $forms[\'mymodule_second_form\'] = array(
    \'callback\' => \'mymodule_main_form\',
    \'callback arguments\' => array(\'some parameter\'),
  );

  // Reroute the $form_id, but invoke the form builder function
  // \'mymodule_main_form_wrapper\' first, so we can prepopulate the $form array
  // that is passed to the actual form builder \'mymodule_main_form\'.
  $forms[\'mymodule_wrapped_form\'] = array(
    \'callback\' => \'mymodule_main_form\',
    \'wrapper_callback\' => \'mymodule_main_form_wrapper\',
  );

  return $forms;
',
    ),
    'hook_boot' =>
    array (
      'type' => 'hook',
      'name' => 'hook_boot',
      'definition' => 'function hook_boot()',
      'description' => 'Perform setup tasks for all page requests.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // We need user_access() in the shutdown function. Make sure it gets loaded.
  drupal_load(\'module\', \'user\');
  drupal_register_shutdown_function(\'devel_shutdown\');
',
    ),
    'hook_init' =>
    array (
      'type' => 'hook',
      'name' => 'hook_init',
      'definition' => 'function hook_init()',
      'description' => 'Perform setup tasks for non-cached page requests.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Since this file should only be loaded on the front page, it cannot be
  // declared in the info file.
  if (drupal_is_front_page()) {
    drupal_add_css(drupal_get_path(\'module\', \'foo\') . \'/foo.css\');
  }
',
    ),
    'hook_image_toolkits' =>
    array (
      'type' => 'hook',
      'name' => 'hook_image_toolkits',
      'definition' => 'function hook_image_toolkits()',
      'description' => 'Define image toolkits provided by this module.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return array(
    \'working\' => array(
      \'title\' => t(\'A toolkit that works.\'),
      \'available\' => TRUE,
    ),
    \'broken\' => array(
      \'title\' => t(\'A toolkit that is "broken" and will not be listed.\'),
      \'available\' => FALSE,
    ),
  );
',
    ),
    'hook_mail_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_mail_alter',
      'definition' => 'function hook_mail_alter(&$message)',
      'description' => 'Alter an email message created with the drupal_mail() function.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  if ($message[\'id\'] == \'modulename_messagekey\') {
    if (!example_notifications_optin($message[\'to\'], $message[\'id\'])) {
      // If the recipient has opted to not receive such messages, cancel
      // sending.
      $message[\'send\'] = FALSE;
      return;
    }
    $message[\'body\'][] = "--\\nMail sent out from " . variable_get(\'site_name\', t(\'Drupal\'));
  }
',
    ),
    'hook_module_implements_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_module_implements_alter',
      'definition' => 'function hook_module_implements_alter(&$implementations, $hook)',
      'description' => 'Alter the registry of modules implementing a hook.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  if ($hook == \'rdf_mapping\') {
    // Move my_module_rdf_mapping() to the end of the list. module_implements()
    // iterates through $implementations with a foreach loop which PHP iterates
    // in the order that the items were added, so to move an item to the end of
    // the array, we remove it and then add it.
    $group = $implementations[\'my_module\'];
    unset($implementations[\'my_module\']);
    $implementations[\'my_module\'] = $group;
  }
',
    ),
    'hook_system_theme_info' =>
    array (
      'type' => 'hook',
      'name' => 'hook_system_theme_info',
      'definition' => 'function hook_system_theme_info()',
      'description' => 'Return additional themes provided by modules.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $themes[\'mymodule_test_theme\'] = drupal_get_path(\'module\', \'mymodule\') . \'/mymodule_test_theme/mymodule_test_theme.info\';
  return $themes;
',
    ),
    'hook_system_info_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_system_info_alter',
      'definition' => 'function hook_system_info_alter(&$info, $file, $type)',
      'description' => 'Alter the information parsed from module and theme .info files',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Only fill this in if the .info file does not define a \'datestamp\'.
  if (empty($info[\'datestamp\'])) {
    $info[\'datestamp\'] = filemtime($file->filename);
  }
',
    ),
    'hook_permission' =>
    array (
      'type' => 'hook',
      'name' => 'hook_permission',
      'definition' => 'function hook_permission()',
      'description' => 'Define user permissions.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return array(
    \'administer my module\' =>  array(
      \'title\' => t(\'Administer my module\'),
      \'description\' => t(\'Perform administration tasks for my module.\'),
    ),
  );
',
    ),
    'hook_help' =>
    array (
      'type' => 'hook',
      'name' => 'hook_help',
      'definition' => 'function hook_help($path, $arg)',
      'description' => 'Provide online user help.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  switch ($path) {
    // Main module help for the block module
    case \'admin/help#block\':
      return \'<p>\' . t(\'Blocks are boxes of content rendered into an area, or region, of a web page. The default theme Bartik, for example, implements the regions "Sidebar first", "Sidebar second", "Featured", "Content", "Header", "Footer", etc., and a block may appear in any one of these areas. The <a href="@blocks">blocks administration page</a> provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions.\', array(\'@blocks\' => url(\'admin/structure/block\'))) . \'</p>\';

    // Help for another path in the block module
    case \'admin/structure/block\':
      return \'<p>\' . t(\'This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.\') . \'</p>\';
  }
',
    ),
    'hook_theme' =>
    array (
      'type' => 'hook',
      'name' => 'hook_theme',
      'definition' => 'function hook_theme($existing, $type, $theme, $path)',
      'description' => 'Register a module (or theme\'s) theme implementations.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return array(
    \'forum_display\' => array(
      \'variables\' => array(\'forums\' => NULL, \'topics\' => NULL, \'parents\' => NULL, \'tid\' => NULL, \'sortby\' => NULL, \'forum_per_page\' => NULL),
    ),
    \'forum_list\' => array(
      \'variables\' => array(\'forums\' => NULL, \'parents\' => NULL, \'tid\' => NULL),
    ),
    \'forum_topic_list\' => array(
      \'variables\' => array(\'tid\' => NULL, \'topics\' => NULL, \'sortby\' => NULL, \'forum_per_page\' => NULL),
    ),
    \'forum_icon\' => array(
      \'variables\' => array(\'new_posts\' => NULL, \'num_posts\' => 0, \'comment_mode\' => 0, \'sticky\' => 0),
    ),
    \'status_report\' => array(
      \'render element\' => \'requirements\',
      \'file\' => \'system.admin.inc\',
    ),
    \'system_date_time_settings\' => array(
      \'render element\' => \'form\',
      \'file\' => \'system.admin.inc\',
    ),
  );
',
    ),
    'hook_theme_registry_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_theme_registry_alter',
      'definition' => 'function hook_theme_registry_alter(&$theme_registry)',
      'description' => 'Alter the theme registry information returned from hook_theme().',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Kill the next/previous forum topic navigation links.
  foreach ($theme_registry[\'forum_topic_navigation\'][\'preprocess functions\'] as $key => $value) {
    if ($value == \'template_preprocess_forum_topic_navigation\') {
      unset($theme_registry[\'forum_topic_navigation\'][\'preprocess functions\'][$key]);
    }
  }
',
    ),
    'hook_custom_theme' =>
    array (
      'type' => 'hook',
      'name' => 'hook_custom_theme',
      'definition' => 'function hook_custom_theme()',
      'description' => 'Return the machine-readable name of the theme to use for the current page.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Allow the user to request a particular theme via a query parameter.
  if (isset($_GET[\'theme\'])) {
    return $_GET[\'theme\'];
  }
',
    ),
    'hook_xmlrpc' =>
    array (
      'type' => 'hook',
      'name' => 'hook_xmlrpc',
      'definition' => 'function hook_xmlrpc()',
      'description' => 'Register XML-RPC callbacks.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return array(
    \'drupal.login\' => \'drupal_login\',
    array(
      \'drupal.site.ping\',
      \'drupal_directory_ping\',
      array(\'boolean\', \'string\', \'string\', \'string\', \'string\', \'string\'),
      t(\'Handling ping request\'))
  );
',
    ),
    'hook_xmlrpc_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_xmlrpc_alter',
      'definition' => 'function hook_xmlrpc_alter(&$methods)',
      'description' => 'Alters the definition of XML-RPC methods before they are called.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Directly change a simple method.
  $methods[\'drupal.login\'] = \'mymodule_login\';

  // Alter complex definitions.
  foreach ($methods as $key => &$method) {
    // Skip simple method definitions.
    if (!is_int($key)) {
      continue;
    }
    // Perform the wanted manipulation.
    if ($method[0] == \'drupal.site.ping\') {
      $method[1] = \'mymodule_directory_ping\';
    }
  }
',
    ),
    'hook_watchdog' =>
    array (
      'type' => 'hook',
      'name' => 'hook_watchdog',
      'definition' => 'function hook_watchdog(array $log_entry)',
      'description' => 'Log an event message.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  global $base_url, $language;

  $severity_list = array(
    WATCHDOG_EMERGENCY => t(\'Emergency\'),
    WATCHDOG_ALERT     => t(\'Alert\'),
    WATCHDOG_CRITICAL  => t(\'Critical\'),
    WATCHDOG_ERROR     => t(\'Error\'),
    WATCHDOG_WARNING   => t(\'Warning\'),
    WATCHDOG_NOTICE    => t(\'Notice\'),
    WATCHDOG_INFO      => t(\'Info\'),
    WATCHDOG_DEBUG     => t(\'Debug\'),
  );

  $to = \'someone@example.com\';
  $params = array();
  $params[\'subject\'] = t(\'[@site_name] @severity_desc: Alert from your web site\', array(
    \'@site_name\' => variable_get(\'site_name\', \'Drupal\'),
    \'@severity_desc\' => $severity_list[$log_entry[\'severity\']],
  ));

  $params[\'message\']  = "\\nSite:         @base_url";
  $params[\'message\'] .= "\\nSeverity:     (@severity) @severity_desc";
  $params[\'message\'] .= "\\nTimestamp:    @timestamp";
  $params[\'message\'] .= "\\nType:         @type";
  $params[\'message\'] .= "\\nIP Address:   @ip";
  $params[\'message\'] .= "\\nRequest URI:  @request_uri";
  $params[\'message\'] .= "\\nReferrer URI: @referer_uri";
  $params[\'message\'] .= "\\nUser:         (@uid) @name";
  $params[\'message\'] .= "\\nLink:         @link";
  $params[\'message\'] .= "\\nMessage:      \\n\\n@message";

  $params[\'message\'] = t($params[\'message\'], array(
    \'@base_url\'      => $base_url,
    \'@severity\'      => $log_entry[\'severity\'],
    \'@severity_desc\' => $severity_list[$log_entry[\'severity\']],
    \'@timestamp\'     => format_date($log_entry[\'timestamp\']),
    \'@type\'          => $log_entry[\'type\'],
    \'@ip\'            => $log_entry[\'ip\'],
    \'@request_uri\'   => $log_entry[\'request_uri\'],
    \'@referer_uri\'   => $log_entry[\'referer\'],
    \'@uid\'           => $log_entry[\'uid\'],
    \'@name\'          => $log_entry[\'user\']->name,
    \'@link\'          => strip_tags($log_entry[\'link\']),
    \'@message\'       => strip_tags($log_entry[\'message\']),
  ));

  drupal_mail(\'emaillog\', \'entry\', $to, $language, $params);
',
    ),
    'hook_mail' =>
    array (
      'type' => 'hook',
      'name' => 'hook_mail',
      'definition' => 'function hook_mail($key, &$message, $params)',
      'description' => 'Prepare a message based on parameters; called from drupal_mail().',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $account = $params[\'account\'];
  $context = $params[\'context\'];
  $variables = array(
    \'%site_name\' => variable_get(\'site_name\', \'Drupal\'),
    \'%username\' => format_username($account),
  );
  if ($context[\'hook\'] == \'taxonomy\') {
    $entity = $params[\'entity\'];
    $vocabulary = taxonomy_vocabulary_load($entity->vid);
    $variables += array(
      \'%term_name\' => $entity->name,
      \'%term_description\' => $entity->description,
      \'%term_id\' => $entity->tid,
      \'%vocabulary_name\' => $vocabulary->name,
      \'%vocabulary_description\' => $vocabulary->description,
      \'%vocabulary_id\' => $vocabulary->vid,
    );
  }

  // Node-based variable translation is only available if we have a node.
  if (isset($params[\'node\'])) {
    $node = $params[\'node\'];
    $variables += array(
      \'%uid\' => $node->uid,
      \'%node_url\' => url(\'node/\' . $node->nid, array(\'absolute\' => TRUE)),
      \'%node_type\' => node_type_get_name($node),
      \'%title\' => $node->title,
      \'%teaser\' => $node->teaser,
      \'%body\' => $node->body,
    );
  }
  $subject = strtr($context[\'subject\'], $variables);
  $body = strtr($context[\'message\'], $variables);
  $message[\'subject\'] .= str_replace(array("\\r", "\\n"), \'\', $subject);
  $message[\'body\'][] = drupal_html_to_text($body);
',
    ),
    'hook_flush_caches' =>
    array (
      'type' => 'hook',
      'name' => 'hook_flush_caches',
      'definition' => 'function hook_flush_caches()',
      'description' => 'Add a list of cache tables to be cleared.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return array(\'cache_example\');
',
    ),
    'hook_modules_installed' =>
    array (
      'type' => 'hook',
      'name' => 'hook_modules_installed',
      'definition' => 'function hook_modules_installed($modules)',
      'description' => 'Perform necessary actions after modules are installed.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  if (in_array(\'lousy_module\', $modules)) {
    variable_set(\'lousy_module_conflicting_variable\', FALSE);
  }
',
    ),
    'hook_modules_enabled' =>
    array (
      'type' => 'hook',
      'name' => 'hook_modules_enabled',
      'definition' => 'function hook_modules_enabled($modules)',
      'description' => 'Perform necessary actions after modules are enabled.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  if (in_array(\'lousy_module\', $modules)) {
    drupal_set_message(t(\'mymodule is not compatible with lousy_module\'), \'error\');
    mymodule_disable_functionality();
  }
',
    ),
    'hook_modules_disabled' =>
    array (
      'type' => 'hook',
      'name' => 'hook_modules_disabled',
      'definition' => 'function hook_modules_disabled($modules)',
      'description' => 'Perform necessary actions after modules are disabled.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  if (in_array(\'lousy_module\', $modules)) {
    mymodule_enable_functionality();
  }
',
    ),
    'hook_modules_uninstalled' =>
    array (
      'type' => 'hook',
      'name' => 'hook_modules_uninstalled',
      'definition' => 'function hook_modules_uninstalled($modules)',
      'description' => 'Perform necessary actions after modules are uninstalled.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  foreach ($modules as $module) {
    db_delete(\'mymodule_table\')
      ->condition(\'module\', $module)
      ->execute();
  }
  mymodule_cache_rebuild();
',
    ),
    'hook_stream_wrappers' =>
    array (
      'type' => 'hook',
      'name' => 'hook_stream_wrappers',
      'definition' => 'function hook_stream_wrappers()',
      'description' => 'Registers PHP stream wrapper implementations associated with a module.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return array(
    \'public\' => array(
      \'name\' => t(\'Public files\'),
      \'class\' => \'DrupalPublicStreamWrapper\',
      \'description\' => t(\'Public local files served by the webserver.\'),
      \'type\' => STREAM_WRAPPERS_LOCAL_NORMAL,
    ),
    \'private\' => array(
      \'name\' => t(\'Private files\'),
      \'class\' => \'DrupalPrivateStreamWrapper\',
      \'description\' => t(\'Private local files served by Drupal.\'),
      \'type\' => STREAM_WRAPPERS_LOCAL_NORMAL,
    ),
    \'temp\' => array(
      \'name\' => t(\'Temporary files\'),
      \'class\' => \'DrupalTempStreamWrapper\',
      \'description\' => t(\'Temporary local files for upload and previews.\'),
      \'type\' => STREAM_WRAPPERS_LOCAL_HIDDEN,
    ),
    \'cdn\' => array(
      \'name\' => t(\'Content delivery network files\'),
      \'class\' => \'MyModuleCDNStreamWrapper\',
      \'description\' => t(\'Files served by a content delivery network.\'),
      // \'type\' can be omitted to use the default of STREAM_WRAPPERS_NORMAL
    ),
    \'youtube\' => array(
      \'name\' => t(\'YouTube video\'),
      \'class\' => \'MyModuleYouTubeStreamWrapper\',
      \'description\' => t(\'Video streamed from YouTube.\'),
      // A module implementing YouTube integration may decide to support using
      // the YouTube API for uploading video, but here, we assume that this
      // particular module only supports playing YouTube video.
      \'type\' => STREAM_WRAPPERS_READ_VISIBLE,
    ),
  );
',
    ),
    'hook_stream_wrappers_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_stream_wrappers_alter',
      'definition' => 'function hook_stream_wrappers_alter(&$wrappers)',
      'description' => 'Alters the list of PHP stream wrapper implementations.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Change the name of private files to reflect the performance.
  $wrappers[\'private\'][\'name\'] = t(\'Slow files\');
',
    ),
    'hook_file_load' =>
    array (
      'type' => 'hook',
      'name' => 'hook_file_load',
      'definition' => 'function hook_file_load($files)',
      'description' => 'Load additional information into file objects.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Add the upload specific data into the file object.
  $result = db_query(\'SELECT * FROM {upload} u WHERE u.fid IN (:fids)\', array(\':fids\' => array_keys($files)))->fetchAll(PDO::FETCH_ASSOC);
  foreach ($result as $record) {
    foreach ($record as $key => $value) {
      $files[$record[\'fid\']]->$key = $value;
    }
  }
',
    ),
    'hook_file_validate' =>
    array (
      'type' => 'hook',
      'name' => 'hook_file_validate',
      'definition' => 'function hook_file_validate($file)',
      'description' => 'Check that files meet a given criteria.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $errors = array();

  if (empty($file->filename)) {
    $errors[] = t("The file\'s name is empty. Please give a name to the file.");
  }
  if (strlen($file->filename) > 255) {
    $errors[] = t("The file\'s name exceeds the 255 characters limit. Please rename the file and try again.");
  }

  return $errors;
',
    ),
    'hook_file_presave' =>
    array (
      'type' => 'hook',
      'name' => 'hook_file_presave',
      'definition' => 'function hook_file_presave($file)',
      'description' => 'Act on a file being inserted or updated.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Change the file timestamp to an hour prior.
  $file->timestamp -= 3600;
',
    ),
    'hook_file_insert' =>
    array (
      'type' => 'hook',
      'name' => 'hook_file_insert',
      'definition' => 'function hook_file_insert($file)',
      'description' => 'Respond to a file being added.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Add a message to the log, if the file is a jpg
  $validate = file_validate_extensions($file, \'jpg\');
  if (empty($validate)) {
    watchdog(\'file\', \'A jpg has been added.\');
  }
',
    ),
    'hook_file_update' =>
    array (
      'type' => 'hook',
      'name' => 'hook_file_update',
      'definition' => 'function hook_file_update($file)',
      'description' => 'Respond to a file being updated.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $file_user = user_load($file->uid);
  // Make sure that the file name starts with the owner\'s user name.
  if (strpos($file->filename, $file_user->name) !== 0) {
    $old_filename = $file->filename;
    $file->filename = $file_user->name . \'_\' . $file->filename;
    $file->save();

    watchdog(\'file\', t(\'%source has been renamed to %destination\', array(\'%source\' => $old_filename, \'%destination\' => $file->filename)));
  }
',
    ),
    'hook_file_copy' =>
    array (
      'type' => 'hook',
      'name' => 'hook_file_copy',
      'definition' => 'function hook_file_copy($file, $source)',
      'description' => 'Respond to a file that has been copied.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $file_user = user_load($file->uid);
  // Make sure that the file name starts with the owner\'s user name.
  if (strpos($file->filename, $file_user->name) !== 0) {
    $file->filename = $file_user->name . \'_\' . $file->filename;
    $file->save();

    watchdog(\'file\', t(\'Copied file %source has been renamed to %destination\', array(\'%source\' => $source->filename, \'%destination\' => $file->filename)));
  }
',
    ),
    'hook_file_move' =>
    array (
      'type' => 'hook',
      'name' => 'hook_file_move',
      'definition' => 'function hook_file_move($file, $source)',
      'description' => 'Respond to a file that has been moved.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $file_user = user_load($file->uid);
  // Make sure that the file name starts with the owner\'s user name.
  if (strpos($file->filename, $file_user->name) !== 0) {
    $file->filename = $file_user->name . \'_\' . $file->filename;
    $file->save();

    watchdog(\'file\', t(\'Moved file %source has been renamed to %destination\', array(\'%source\' => $source->filename, \'%destination\' => $file->filename)));
  }
',
    ),
    'hook_file_delete' =>
    array (
      'type' => 'hook',
      'name' => 'hook_file_delete',
      'definition' => 'function hook_file_delete($file)',
      'description' => 'Respond to a file being deleted.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Delete all information associated with the file.
  db_delete(\'upload\')->condition(\'fid\', $file->fid)->execute();
',
    ),
    'hook_file_download' =>
    array (
      'type' => 'hook',
      'name' => 'hook_file_download',
      'definition' => 'function hook_file_download($uri)',
      'description' => 'Control access to private file downloads and specify HTTP headers.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Check if the file is controlled by the current module.
  if (!file_prepare_directory($uri)) {
    $uri = FALSE;
  }
  if (strpos(file_uri_target($uri), variable_get(\'user_picture_path\', \'pictures\') . \'/picture-\') === 0) {
    if (!user_access(\'access user profiles\')) {
      // Access to the file is denied.
      return -1;
    }
    else {
      $info = image_get_info($uri);
      return array(\'Content-Type\' => $info[\'mime_type\']);
    }
  }
',
    ),
    'hook_file_url_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_file_url_alter',
      'definition' => 'function hook_file_url_alter(&$uri)',
      'description' => 'Alter the URL to a file.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  global $user;

  // User 1 will always see the local file in this example.
  if ($user->uid == 1) {
    return;
  }

  $cdn1 = \'http://cdn1.example.com\';
  $cdn2 = \'http://cdn2.example.com\';
  $cdn_extensions = array(\'css\', \'js\', \'gif\', \'jpg\', \'jpeg\', \'png\');

  // Most CDNs don\'t support private file transfers without a lot of hassle,
  // so don\'t support this in the common case.
  $schemes = array(\'public\');

  $scheme = file_uri_scheme($uri);

  // Only serve shipped files and public created files from the CDN.
  if (!$scheme || in_array($scheme, $schemes)) {
    // Shipped files.
    if (!$scheme) {
      $path = $uri;
    }
    // Public created files.
    else {
      $wrapper = file_stream_wrapper_get_instance_by_scheme($scheme);
      $path = $wrapper->getDirectoryPath() . \'/\' . file_uri_target($uri);
    }

    // Clean up Windows paths.
    $path = str_replace(\'\\\\\', \'/\', $path);

    // Serve files with one of the CDN extensions from CDN 1, all others from
    // CDN 2.
    $pathinfo = pathinfo($path);
    if (isset($pathinfo[\'extension\']) && in_array($pathinfo[\'extension\'], $cdn_extensions)) {
      $uri = $cdn1 . \'/\' . $path;
    }
    else {
      $uri = $cdn2 . \'/\' . $path;
    }
  }
',
    ),
    'hook_requirements' =>
    array (
      'type' => 'hook',
      'name' => 'hook_requirements',
      'definition' => 'function hook_requirements($phase)',
      'description' => 'Check installation requirements and do status reporting.',
      'destination' => '%module.install',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $requirements = array();
  // Ensure translations don\'t break during installation.
  $t = get_t();

  // Report Drupal version
  if ($phase == \'runtime\') {
    $requirements[\'drupal\'] = array(
      \'title\' => $t(\'Drupal\'),
      \'value\' => VERSION,
      \'severity\' => REQUIREMENT_INFO
    );
  }

  // Test PHP version
  $requirements[\'php\'] = array(
    \'title\' => $t(\'PHP\'),
    \'value\' => ($phase == \'runtime\') ? l(phpversion(), \'admin/reports/status/php\') : phpversion(),
  );
  if (version_compare(phpversion(), DRUPAL_MINIMUM_PHP) < 0) {
    $requirements[\'php\'][\'description\'] = $t(\'Your PHP installation is too old. Drupal requires at least PHP %version.\', array(\'%version\' => DRUPAL_MINIMUM_PHP));
    $requirements[\'php\'][\'severity\'] = REQUIREMENT_ERROR;
  }

  // Report cron status
  if ($phase == \'runtime\') {
    $cron_last = variable_get(\'cron_last\');

    if (is_numeric($cron_last)) {
      $requirements[\'cron\'][\'value\'] = $t(\'Last run !time ago\', array(\'!time\' => format_interval(REQUEST_TIME - $cron_last)));
    }
    else {
      $requirements[\'cron\'] = array(
        \'description\' => $t(\'Cron has not run. It appears cron jobs have not been setup on your system. Check the help pages for <a href="@url">configuring cron jobs</a>.\', array(\'@url\' => \'http://drupal.org/cron\')),
        \'severity\' => REQUIREMENT_ERROR,
        \'value\' => $t(\'Never run\'),
      );
    }

    $requirements[\'cron\'][\'description\'] .= \' \' . $t(\'You can <a href="@cron">run cron manually</a>.\', array(\'@cron\' => url(\'admin/reports/status/run-cron\')));

    $requirements[\'cron\'][\'title\'] = $t(\'Cron maintenance tasks\');
  }

  return $requirements;
',
    ),
    'hook_schema' =>
    array (
      'type' => 'hook',
      'name' => 'hook_schema',
      'definition' => 'function hook_schema()',
      'description' => 'Define the current version of the database schema.',
      'destination' => '%module.install',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $schema[\'node\'] = array(
    // Example (partial) specification for table "node".
    \'description\' => \'The base table for nodes.\',
    \'fields\' => array(
      \'nid\' => array(
        \'description\' => \'The primary identifier for a node.\',
        \'type\' => \'serial\',
        \'unsigned\' => TRUE,
        \'not null\' => TRUE,
      ),
      \'vid\' => array(
        \'description\' => \'The current {node_revision}.vid version identifier.\',
        \'type\' => \'int\',
        \'unsigned\' => TRUE,
        \'not null\' => TRUE,
        \'default\' => 0,
      ),
      \'type\' => array(
        \'description\' => \'The {node_type} of this node.\',
        \'type\' => \'varchar\',
        \'length\' => 32,
        \'not null\' => TRUE,
        \'default\' => \'\',
      ),
      \'title\' => array(
        \'description\' => \'The title of this node, always treated as non-markup plain text.\',
        \'type\' => \'varchar\',
        \'length\' => 255,
        \'not null\' => TRUE,
        \'default\' => \'\',
      ),
    ),
    \'indexes\' => array(
      \'node_changed\'        => array(\'changed\'),
      \'node_created\'        => array(\'created\'),
    ),
    \'unique keys\' => array(
      \'nid_vid\' => array(\'nid\', \'vid\'),
      \'vid\'     => array(\'vid\'),
    ),
    \'foreign keys\' => array(
      \'node_revision\' => array(
        \'table\' => \'node_revision\',
        \'columns\' => array(\'vid\' => \'vid\'),
      ),
      \'node_author\' => array(
        \'table\' => \'users\',
        \'columns\' => array(\'uid\' => \'uid\'),
      ),
    ),
    \'primary key\' => array(\'nid\'),
  );
  return $schema;
',
    ),
    'hook_schema_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_schema_alter',
      'definition' => 'function hook_schema_alter(&$schema)',
      'description' => 'Perform alterations to existing database schemas.',
      'destination' => '%module.install',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Add field to existing schema.
  $schema[\'users\'][\'fields\'][\'timezone_id\'] = array(
    \'type\' => \'int\',
    \'not null\' => TRUE,
    \'default\' => 0,
    \'description\' => \'Per-user timezone configuration.\',
  );
',
    ),
    'hook_query_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_query_alter',
      'definition' => 'function hook_query_alter(QueryAlterableInterface $query)',
      'description' => 'Perform alterations to a structured query.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  if ($query->hasTag(\'micro_limit\')) {
    $query->range(0, 2);
  }
',
    ),
    'hook_query_TAG_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_query_TAG_alter',
      'definition' => 'function hook_query_TAG_alter(QueryAlterableInterface $query)',
      'description' => 'Perform alterations to a structured query for a given tag.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Skip the extra expensive alterations if site has no node access control modules.
  if (!node_access_view_all_nodes()) {
    // Prevent duplicates records.
    $query->distinct();
    // The recognized operations are \'view\', \'update\', \'delete\'.
    if (!$op = $query->getMetaData(\'op\')) {
      $op = \'view\';
    }
    // Skip the extra joins and conditions for node admins.
    if (!user_access(\'bypass node access\')) {
      // The node_access table has the access grants for any given node.
      $access_alias = $query->join(\'node_access\', \'na\', \'%alias.nid = n.nid\');
      $or = db_or();
      // If any grant exists for the specified user, then user has access to the node for the specified operation.
      foreach (node_access_grants($op, $query->getMetaData(\'account\')) as $realm => $gids) {
        foreach ($gids as $gid) {
          $or->condition(db_and()
            ->condition($access_alias . \'.gid\', $gid)
            ->condition($access_alias . \'.realm\', $realm)
          );
        }
      }

      if (count($or->conditions())) {
        $query->condition($or);
      }

      $query->condition($access_alias . \'grant_\' . $op, 1, \'>=\');
    }
  }
',
    ),
    'hook_install' =>
    array (
      'type' => 'hook',
      'name' => 'hook_install',
      'definition' => 'function hook_install()',
      'description' => 'Perform setup tasks when the module is installed.',
      'destination' => '%module.install',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Populate the default {node_access} record.
  db_insert(\'node_access\')
    ->fields(array(
      \'nid\' => 0,
      \'gid\' => 0,
      \'realm\' => \'all\',
      \'grant_view\' => 1,
      \'grant_update\' => 0,
      \'grant_delete\' => 0,
    ))
    ->execute();
',
    ),
    'hook_update_N' =>
    array (
      'type' => 'hook',
      'name' => 'hook_update_N',
      'definition' => 'function hook_update_N(&$sandbox)',
      'description' => 'Perform a single update.',
      'destination' => '%module.install',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // For non-multipass updates, the signature can simply be;
  // function hook_update_N() {

  // For most updates, the following is sufficient.
  db_add_field(\'mytable1\', \'newcol\', array(\'type\' => \'int\', \'not null\' => TRUE, \'description\' => \'My new integer column.\'));

  // However, for more complex operations that may take a long time,
  // you may hook into Batch API as in the following example.

  // Update 3 users at a time to have an exclamation point after their names.
  // (They\'re really happy that we can do batch API in this hook!)
  if (!isset($sandbox[\'progress\'])) {
    $sandbox[\'progress\'] = 0;
    $sandbox[\'current_uid\'] = 0;
    // We\'ll -1 to disregard the uid 0...
    $sandbox[\'max\'] = db_query(\'SELECT COUNT(DISTINCT uid) FROM {users}\')->fetchField() - 1;
  }

  $users = db_select(\'users\', \'u\')
    ->fields(\'u\', array(\'uid\', \'name\'))
    ->condition(\'uid\', $sandbox[\'current_uid\'], \'>\')
    ->range(0, 3)
    ->orderBy(\'uid\', \'ASC\')
    ->execute();

  foreach ($users as $user) {
    $user->name .= \'!\';
    db_update(\'users\')
      ->fields(array(\'name\' => $user->name))
      ->condition(\'uid\', $user->uid)
      ->execute();

    $sandbox[\'progress\']++;
    $sandbox[\'current_uid\'] = $user->uid;
  }

  $sandbox[\'#finished\'] = empty($sandbox[\'max\']) ? 1 : ($sandbox[\'progress\'] / $sandbox[\'max\']);

  // To display a message to the user when the update is completed, return it.
  // If you do not want to display a completion message, simply return nothing.
  return t(\'The update did what it was supposed to do.\');

  // In case of an error, simply throw an exception with an error message.
  throw new DrupalUpdateException(\'Something went wrong; here is what you should do.\');
',
    ),
    'hook_update_dependencies' =>
    array (
      'type' => 'hook',
      'name' => 'hook_update_dependencies',
      'definition' => 'function hook_update_dependencies()',
      'description' => 'Return an array of information about module update dependencies.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Indicate that the mymodule_update_7000() function provided by this module
  // must run after the another_module_update_7002() function provided by the
  // \'another_module\' module.
  $dependencies[\'mymodule\'][7000] = array(
    \'another_module\' => 7002,
  );
  // Indicate that the mymodule_update_7001() function provided by this module
  // must run before the yet_another_module_update_7004() function provided by
  // the \'yet_another_module\' module. (Note that declaring dependencies in this
  // direction should be done only in rare situations, since it can lead to the
  // following problem: If a site has already run the yet_another_module
  // module\'s database updates before it updates its codebase to pick up the
  // newest mymodule code, then the dependency declared here will be ignored.)
  $dependencies[\'yet_another_module\'][7004] = array(
    \'mymodule\' => 7001,
  );
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
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // We\'ve removed the 5.x-1.x version of mymodule, including database updates.
  // The next update function is mymodule_update_5200().
  return 5103;
',
    ),
    'hook_uninstall' =>
    array (
      'type' => 'hook',
      'name' => 'hook_uninstall',
      'definition' => 'function hook_uninstall()',
      'description' => 'Remove any information that the module sets.',
      'destination' => '%module.install',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  variable_del(\'upload_file_types\');
',
    ),
    'hook_enable' =>
    array (
      'type' => 'hook',
      'name' => 'hook_enable',
      'definition' => 'function hook_enable()',
      'description' => 'Perform necessary actions after module is enabled.',
      'destination' => '%module.install',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  mymodule_cache_rebuild();
',
    ),
    'hook_disable' =>
    array (
      'type' => 'hook',
      'name' => 'hook_disable',
      'definition' => 'function hook_disable()',
      'description' => 'Perform necessary actions before module is disabled.',
      'destination' => '%module.install',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  mymodule_cache_rebuild();
',
    ),
    'hook_registry_files_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_registry_files_alter',
      'definition' => 'function hook_registry_files_alter(&$files, $modules)',
      'description' => 'Perform necessary alterations to the list of files parsed by the registry.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  foreach ($modules as $module) {
    // Only add test files for disabled modules, as enabled modules should
    // already include any test files they provide.
    if (!$module->status) {
      $dir = $module->dir;
      foreach ($module->info[\'files\'] as $file) {
        if (substr($file, -5) == \'.test\') {
          $files["$dir/$file"] = array(\'module\' => $module->name, \'weight\' => $module->weight);
        }
      }
    }
  }
',
    ),
    'hook_install_tasks' =>
    array (
      'type' => 'hook',
      'name' => 'hook_install_tasks',
      'definition' => 'function hook_install_tasks(&$install_state)',
      'description' => 'Return an array of tasks to be performed by an installation profile.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Here, we define a variable to allow tasks to indicate that a particular,
  // processor-intensive batch process needs to be triggered later on in the
  // installation.
  $myprofile_needs_batch_processing = variable_get(\'myprofile_needs_batch_processing\', FALSE);
  $tasks = array(
    // This is an example of a task that defines a form which the user who is
    // installing the site will be asked to fill out. To implement this task,
    // your profile would define a function named myprofile_data_import_form()
    // as a normal form API callback function, with associated validation and
    // submit handlers. In the submit handler, in addition to saving whatever
    // other data you have collected from the user, you might also call
    // variable_set(\'myprofile_needs_batch_processing\', TRUE) if the user has
    // entered data which requires that batch processing will need to occur
    // later on.
    \'myprofile_data_import_form\' => array(
      \'display_name\' => st(\'Data import options\'),
      \'type\' => \'form\',
    ),
    // Similarly, to implement this task, your profile would define a function
    // named myprofile_settings_form() with associated validation and submit
    // handlers. This form might be used to collect and save additional
    // information from the user that your profile needs. There are no extra
    // steps required for your profile to act as an "installation wizard"; you
    // can simply define as many tasks of type \'form\' as you wish to execute,
    // and the forms will be presented to the user, one after another.
    \'myprofile_settings_form\' => array(
      \'display_name\' => st(\'Additional options\'),
      \'type\' => \'form\',
    ),
    // This is an example of a task that performs batch operations. To
    // implement this task, your profile would define a function named
    // myprofile_batch_processing() which returns a batch API array definition
    // that the installer will use to execute your batch operations. Due to the
    // \'myprofile_needs_batch_processing\' variable used here, this task will be
    // hidden and skipped unless your profile set it to TRUE in one of the
    // previous tasks.
    \'myprofile_batch_processing\' => array(
      \'display_name\' => st(\'Import additional data\'),
      \'display\' => $myprofile_needs_batch_processing,
      \'type\' => \'batch\',
      \'run\' => $myprofile_needs_batch_processing ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ),
    // This is an example of a task that will not be displayed in the list that
    // the user sees. To implement this task, your profile would define a
    // function named myprofile_final_site_setup(), in which additional,
    // automated site setup operations would be performed. Since this is the
    // last task defined by your profile, you should also use this function to
    // call variable_del(\'myprofile_needs_batch_processing\') and clean up the
    // variable that was used above. If you want the user to pass to the final
    // Drupal installation tasks uninterrupted, return no output from this
    // function. Otherwise, return themed output that the user will see (for
    // example, a confirmation page explaining that your profile\'s tasks are
    // complete, with a link to reload the current page and therefore pass on
    // to the final Drupal installation tasks when the user is ready to do so).
    \'myprofile_final_site_setup\' => array(
    ),
  );
  return $tasks;
',
    ),
    'hook_drupal_goto_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_drupal_goto_alter',
      'definition' => 'function hook_drupal_goto_alter(&$path, &$options, &$http_response_code)',
      'description' => 'Change the page the user is sent to by drupal_goto().',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // A good addition to misery module.
  $http_response_code = 500;
',
    ),
    'hook_html_head_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_html_head_alter',
      'definition' => 'function hook_html_head_alter(&$head_elements)',
      'description' => 'Alter XHTML HEAD tags before they are rendered by drupal_get_html_head().',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  foreach ($head_elements as $key => $element) {
    if (isset($element[\'#attributes\'][\'rel\']) && $element[\'#attributes\'][\'rel\'] == \'canonical\') {
      // I want a custom canonical URL.
      $head_elements[$key][\'#attributes\'][\'href\'] = mymodule_canonical_url();
    }
  }
',
    ),
    'hook_install_tasks_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_install_tasks_alter',
      'definition' => 'function hook_install_tasks_alter(&$tasks, $install_state)',
      'description' => 'Alter the full list of installation tasks.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Replace the "Choose language" installation task provided by Drupal core
  // with a custom callback function defined by this installation profile.
  $tasks[\'install_select_locale\'][\'function\'] = \'myprofile_locale_selection\';
',
    ),
    'hook_file_mimetype_mapping_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_file_mimetype_mapping_alter',
      'definition' => 'function hook_file_mimetype_mapping_alter(&$mapping)',
      'description' => 'Alter MIME type mappings used to determine MIME type from a file extension.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Add new MIME type \'drupal/info\'.
  $mapping[\'mimetypes\'][\'example_info\'] = \'drupal/info\';
  // Add new extension \'.info\' and map it to the \'drupal/info\' MIME type.
  $mapping[\'extensions\'][\'info\'] = \'example_info\';
  // Override existing extension mapping for \'.ogg\' files.
  $mapping[\'extensions\'][\'ogg\'] = 189;
',
    ),
    'hook_action_info' =>
    array (
      'type' => 'hook',
      'name' => 'hook_action_info',
      'definition' => 'function hook_action_info()',
      'description' => 'Declares information about actions.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return array(
    \'comment_unpublish_action\' => array(
      \'type\' => \'comment\',
      \'label\' => t(\'Unpublish comment\'),
      \'configurable\' => FALSE,
      \'behavior\' => array(\'changes_property\'),
      \'triggers\' => array(\'comment_presave\', \'comment_insert\', \'comment_update\'),
    ),
    \'comment_unpublish_by_keyword_action\' => array(
      \'type\' => \'comment\',
      \'label\' => t(\'Unpublish comment containing keyword(s)\'),
      \'configurable\' => TRUE,
      \'behavior\' => array(\'changes_property\'),
      \'triggers\' => array(\'comment_presave\', \'comment_insert\', \'comment_update\'),
    ),
    \'comment_save_action\' => array(
      \'type\' => \'comment\',
      \'label\' => t(\'Save comment\'),
      \'configurable\' => FALSE,
      \'triggers\' => array(\'comment_insert\', \'comment_update\'),
    ),
  );
',
    ),
    'hook_actions_delete' =>
    array (
      'type' => 'hook',
      'name' => 'hook_actions_delete',
      'definition' => 'function hook_actions_delete($aid)',
      'description' => 'Executes code after an action is deleted.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  db_delete(\'actions_assignments\')
    ->condition(\'aid\', $aid)
    ->execute();
',
    ),
    'hook_action_info_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_action_info_alter',
      'definition' => 'function hook_action_info_alter(&$actions)',
      'description' => 'Alters the actions declared by another module.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $actions[\'node_unpublish_action\'][\'label\'] = t(\'Unpublish and remove from public view.\');
',
    ),
    'hook_archiver_info' =>
    array (
      'type' => 'hook',
      'name' => 'hook_archiver_info',
      'definition' => 'function hook_archiver_info()',
      'description' => 'Declare archivers to the system.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return array(
    \'tar\' => array(
      \'class\' => \'ArchiverTar\',
      \'extensions\' => array(\'tar\', \'tar.gz\', \'tar.bz2\'),
    ),
  );
',
    ),
    'hook_archiver_info_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_archiver_info_alter',
      'definition' => 'function hook_archiver_info_alter(&$info)',
      'description' => 'Alter archiver information declared by other modules.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $info[\'tar\'][\'extensions\'][] = \'tgz\';
',
    ),
    'hook_date_format_types' =>
    array (
      'type' => 'hook',
      'name' => 'hook_date_format_types',
      'definition' => 'function hook_date_format_types()',
      'description' => 'Define additional date types.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Define the core date format types.
  return array(
    \'long\' => t(\'Long\'),
    \'medium\' => t(\'Medium\'),
    \'short\' => t(\'Short\'),
  );
',
    ),
    'hook_date_format_types_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_date_format_types_alter',
      'definition' => 'function hook_date_format_types_alter(&$types)',
      'description' => 'Modify existing date types.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  foreach ($types as $name => $type) {
    $types[$name][\'locked\'] = 1;
  }
',
    ),
    'hook_date_formats' =>
    array (
      'type' => 'hook',
      'name' => 'hook_date_formats',
      'definition' => 'function hook_date_formats()',
      'description' => 'Define additional date formats.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return array(
    array(
      \'type\' => \'mymodule_extra_long\',
      \'format\' => \'l jS F Y H:i:s e\',
      \'locales\' => array(\'en-ie\'),
    ),
    array(
      \'type\' => \'mymodule_extra_long\',
      \'format\' => \'l jS F Y h:i:sa\',
      \'locales\' => array(\'en\', \'en-us\'),
    ),
    array(
      \'type\' => \'short\',
      \'format\' => \'F Y\',
      \'locales\' => array(),
    ),
  );
',
    ),
    'hook_date_formats_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_date_formats_alter',
      'definition' => 'function hook_date_formats_alter(&$formats)',
      'description' => 'Alter date formats declared by another module.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  foreach ($formats as $id => $format) {
    $formats[$id][\'locales\'][] = \'en-ca\';
  }
',
    ),
    'hook_page_delivery_callback_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_page_delivery_callback_alter',
      'definition' => 'function hook_page_delivery_callback_alter(&$callback)',
      'description' => 'Alters the delivery callback used to send the result of the page callback to the browser.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // jQuery sets a HTTP_X_REQUESTED_WITH header of \'XMLHttpRequest\'.
  // If a page would normally be delivered as an html page, and it is called
  // from jQuery, deliver it instead as an Ajax response.
  if (isset($_SERVER[\'HTTP_X_REQUESTED_WITH\']) && $_SERVER[\'HTTP_X_REQUESTED_WITH\'] == \'XMLHttpRequest\' && $callback == \'drupal_deliver_html_page\') {
    $callback = \'ajax_deliver\';
  }
',
    ),
    'hook_system_themes_page_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_system_themes_page_alter',
      'definition' => 'function hook_system_themes_page_alter(&$theme_groups)',
      'description' => 'Alters theme operation links.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  foreach ($theme_groups as $state => &$group) {
    foreach ($theme_groups[$state] as &$theme) {
      // Add a foo link to each list of theme operations.
      $theme->operations[] = array(
        \'title\' => t(\'Foo\'),
        \'href\' => \'admin/appearance/foo\',
        \'query\' => array(\'theme\' => $theme->name)
      );
    }
  }
',
    ),
    'hook_url_inbound_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_url_inbound_alter',
      'definition' => 'function hook_url_inbound_alter(&$path, $original_path, $path_language)',
      'description' => 'Alters inbound URL requests.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Create the path user/me/edit, which allows a user to edit their account.
  if (preg_match(\'|^user/me/edit(/.*)?|\', $path, $matches)) {
    global $user;
    $path = \'user/\' . $user->uid . \'/edit\' . $matches[1];
  }
',
    ),
    'hook_url_outbound_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_url_outbound_alter',
      'definition' => 'function hook_url_outbound_alter(&$path, &$options, $original_path)',
      'description' => 'Alters outbound URLs.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Use an external RSS feed rather than the Drupal one.
  if ($path == \'rss.xml\') {
    $path = \'http://example.com/rss.xml\';
    $options[\'external\'] = TRUE;
  }

  // Instead of pointing to user/[uid]/edit, point to user/me/edit.
  if (preg_match(\'|^user/([0-9]*)/edit(/.*)?|\', $path, $matches)) {
    global $user;
    if ($user->uid == $matches[1]) {
      $path = \'user/me/edit\' . $matches[2];
    }
  }
',
    ),
    'hook_username_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_username_alter',
      'definition' => 'function hook_username_alter(&$name, $account)',
      'description' => 'Alter the username that is displayed for a user.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Display the user\'s uid instead of name.
  if (isset($account->uid)) {
    $name = t(\'User !uid\', array(\'!uid\' => $account->uid));
  }
',
    ),
    'hook_tokens' =>
    array (
      'type' => 'hook',
      'name' => 'hook_tokens',
      'definition' => 'function hook_tokens($type, $tokens, array $data = array(), array $options = array())',
      'description' => 'Provide replacement values for placeholder tokens.',
      'destination' => '%module.tokens.inc',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $url_options = array(\'absolute\' => TRUE);
  if (isset($options[\'language\'])) {
    $url_options[\'language\'] = $options[\'language\'];
    $language_code = $options[\'language\']->language;
  }
  else {
    $language_code = NULL;
  }
  $sanitize = !empty($options[\'sanitize\']);

  $replacements = array();

  if ($type == \'node\' && !empty($data[\'node\'])) {
    $node = $data[\'node\'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the node.
        case \'nid\':
          $replacements[$original] = $node->nid;
          break;

        case \'title\':
          $replacements[$original] = $sanitize ? check_plain($node->title) : $node->title;
          break;

        case \'edit-url\':
          $replacements[$original] = url(\'node/\' . $node->nid . \'/edit\', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case \'author\':
          $name = ($node->uid == 0) ? variable_get(\'anonymous\', t(\'Anonymous\')) : $node->name;
          $replacements[$original] = $sanitize ? filter_xss($name) : $name;
          break;

        case \'created\':
          $replacements[$original] = format_date($node->created, \'medium\', \'\', NULL, $language_code);
          break;
      }
    }

    if ($author_tokens = token_find_with_prefix($tokens, \'author\')) {
      $author = user_load($node->uid);
      $replacements += token_generate(\'user\', $author_tokens, array(\'user\' => $author), $options);
    }

    if ($created_tokens = token_find_with_prefix($tokens, \'created\')) {
      $replacements += token_generate(\'date\', $created_tokens, array(\'date\' => $node->created), $options);
    }
  }

  return $replacements;
',
    ),
    'hook_tokens_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_tokens_alter',
      'definition' => 'function hook_tokens_alter(array &$replacements, array $context)',
      'description' => 'Alter replacement values for placeholder tokens.',
      'destination' => '%module.tokens.inc',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $options = $context[\'options\'];

  if (isset($options[\'language\'])) {
    $url_options[\'language\'] = $options[\'language\'];
    $language_code = $options[\'language\']->language;
  }
  else {
    $language_code = NULL;
  }
  $sanitize = !empty($options[\'sanitize\']);

  if ($context[\'type\'] == \'node\' && !empty($context[\'data\'][\'node\'])) {
    $node = $context[\'data\'][\'node\'];

    // Alter the [node:title] token, and replace it with the rendered content
    // of a field (field_title).
    if (isset($context[\'tokens\'][\'title\'])) {
      $title = field_view_field(\'node\', $node, \'field_title\', \'default\', $language_code);
      $replacements[$context[\'tokens\'][\'title\']] = drupal_render($title);
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
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $type = array(
    \'name\' => t(\'Nodes\'),
    \'description\' => t(\'Tokens related to individual nodes.\'),
    \'needs-data\' => \'node\',
  );

  // Core tokens for nodes.
  $node[\'nid\'] = array(
    \'name\' => t("Node ID"),
    \'description\' => t("The unique ID of the node."),
  );
  $node[\'title\'] = array(
    \'name\' => t("Title"),
    \'description\' => t("The title of the node."),
  );
  $node[\'edit-url\'] = array(
    \'name\' => t("Edit URL"),
    \'description\' => t("The URL of the node\'s edit page."),
  );

  // Chained tokens for nodes.
  $node[\'created\'] = array(
    \'name\' => t("Date created"),
    \'description\' => t("The date the node was posted."),
    \'type\' => \'date\',
  );
  $node[\'author\'] = array(
    \'name\' => t("Author"),
    \'description\' => t("The author of the node."),
    \'type\' => \'user\',
  );

  return array(
    \'types\' => array(\'node\' => $type),
    \'tokens\' => array(\'node\' => $node),
  );
',
    ),
    'hook_token_info_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_token_info_alter',
      'definition' => 'function hook_token_info_alter(&$data)',
      'description' => 'Alter the metadata about available placeholder tokens and token types.',
      'destination' => '%module.tokens.inc',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Modify description of node tokens for our site.
  $data[\'tokens\'][\'node\'][\'nid\'] = array(
    \'name\' => t("Node ID"),
    \'description\' => t("The unique ID of the article."),
  );
  $data[\'tokens\'][\'node\'][\'title\'] = array(
    \'name\' => t("Title"),
    \'description\' => t("The title of the article."),
  );

  // Chained tokens for nodes.
  $data[\'tokens\'][\'node\'][\'created\'] = array(
    \'name\' => t("Date created"),
    \'description\' => t("The date the article was posted."),
    \'type\' => \'date\',
  );
',
    ),
    'hook_batch_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_batch_alter',
      'definition' => 'function hook_batch_alter(&$batch)',
      'description' => 'Alter batch information before a batch is processed.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // If the current page request is inside the overlay, add ?render=overlay to
  // the success callback URL, so that it appears correctly within the overlay.
  if (overlay_get_mode() == \'child\') {
    if (isset($batch[\'url_options\'][\'query\'])) {
      $batch[\'url_options\'][\'query\'][\'render\'] = \'overlay\';
    }
    else {
      $batch[\'url_options\'][\'query\'] = array(\'render\' => \'overlay\');
    }
  }
',
    ),
    'hook_updater_info' =>
    array (
      'type' => 'hook',
      'name' => 'hook_updater_info',
      'definition' => 'function hook_updater_info()',
      'description' => 'Provide information on Updaters (classes that can update Drupal).',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return array(
    \'module\' => array(
      \'class\' => \'ModuleUpdater\',
      \'name\' => t(\'Update modules\'),
      \'weight\' => 0,
    ),
    \'theme\' => array(
      \'class\' => \'ThemeUpdater\',
      \'name\' => t(\'Update themes\'),
      \'weight\' => 0,
    ),
  );
',
    ),
    'hook_updater_info_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_updater_info_alter',
      'definition' => 'function hook_updater_info_alter(&$updaters)',
      'description' => 'Alter the Updater information array.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Adjust weight so that the theme Updater gets a chance to handle a given
  // update task before module updaters.
  $updaters[\'theme\'][\'weight\'] = -1;
',
    ),
    'hook_countries_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_countries_alter',
      'definition' => 'function hook_countries_alter(&$countries)',
      'description' => 'Alter the default country list.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Elbonia is now independent, so add it to the country list.
  $countries[\'EB\'] = \'Elbonia\';
',
    ),
    'hook_menu_site_status_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_menu_site_status_alter',
      'definition' => 'function hook_menu_site_status_alter(&$menu_site_status, $path)',
      'description' => 'Control site status before menu dispatching.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  // Allow access to my_module/authentication even if site is in offline mode.
  if ($menu_site_status == MENU_SITE_OFFLINE && user_is_anonymous() && $path == \'my_module/authentication\') {
    $menu_site_status = MENU_SITE_ONLINE;
  }
',
    ),
    'hook_filetransfer_info' =>
    array (
      'type' => 'hook',
      'name' => 'hook_filetransfer_info',
      'definition' => 'function hook_filetransfer_info()',
      'description' => 'Register information about FileTransfer classes provided by a module.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $info[\'sftp\'] = array(
    \'title\' => t(\'SFTP (Secure FTP)\'),
    \'file\' => \'sftp.filetransfer.inc\',
    \'class\' => \'FileTransferSFTP\',
    \'weight\' => 10,
  );
  return $info;
',
    ),
    'hook_filetransfer_info_alter' =>
    array (
      'type' => 'hook',
      'name' => 'hook_filetransfer_info_alter',
      'definition' => 'function hook_filetransfer_info_alter(&$filetransfer_info)',
      'description' => 'Alter the FileTransfer class registry.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  if (variable_get(\'paranoia\', FALSE)) {
    // Remove the FTP option entirely.
    unset($filetransfer_info[\'ftp\']);
    // Make sure the SSH option is listed first.
    $filetransfer_info[\'ssh\'][\'weight\'] = -10;
  }
',
    ),
    'callback_queue_worker' =>
    array (
      'type' => 'callback',
      'name' => 'callback_queue_worker',
      'definition' => 'function callback_queue_worker($queue_item_data)',
      'description' => 'Work on a single queue item.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  $node = node_load($queue_item_data);
  $node->title = \'Updated title\';
  node_save($node);
',
    ),
    'callback_entity_info_uri' =>
    array (
      'type' => 'callback',
      'name' => 'callback_entity_info_uri',
      'definition' => 'function callback_entity_info_uri($entity)',
      'description' => 'Return the URI for an entity.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return array(
    \'path\' => \'node/\' . $entity->nid,
  );
',
    ),
    'callback_entity_info_label' =>
    array (
      'type' => 'callback',
      'name' => 'callback_entity_info_label',
      'definition' => 'function callback_entity_info_label($entity, $entity_type)',
      'description' => 'Return the label of an entity.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return empty($entity->title) ? \'Untitled entity\' : $entity->title;
',
    ),
    'callback_entity_info_language' =>
    array (
      'type' => 'callback',
      'name' => 'callback_entity_info_language',
      'definition' => 'function callback_entity_info_language($entity, $entity_type)',
      'description' => 'Return the language code of the entity.',
      'destination' => '%module.module',
      'dependencies' =>
      array (
      ),
      'group' => 'system',
      'file_path' => '/Users/joachim/bin/drupal_hooks/7/system.api.php',
      'body' => '
  return $entity->language;
',
    ),
  ),
);
