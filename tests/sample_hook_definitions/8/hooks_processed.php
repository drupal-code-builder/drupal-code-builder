a:3:{s:5:"block";a:5:{s:21:"hook_block_view_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:21:"hook_block_view_alter";s:10:"definition";s:93:"function hook_block_view_alter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block)";s:11:"description";s:58:"Alter the result of \Drupal\Core\Block\BlockBase::build().";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/block.api.php";s:4:"body";s:155:"
  // Remove the contextual links on all blocks that provide them.
  if (isset($build['#contextual_links'])) {
    unset($build['#contextual_links']);
  }
";}s:35:"hook_block_view_BASE_BLOCK_ID_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:35:"hook_block_view_BASE_BLOCK_ID_alter";s:10:"definition";s:107:"function hook_block_view_BASE_BLOCK_ID_alter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block)";s:11:"description";s:54:"Provide a block plugin specific block_view alteration.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/block.api.php";s:4:"body";s:96:"
  // Change the title of the specific block.
  $build['#title'] = t('New title of the block');
";}s:22:"hook_block_build_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:22:"hook_block_build_alter";s:10:"definition";s:94:"function hook_block_build_alter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block)";s:11:"description";s:58:"Alter the result of \Drupal\Core\Block\BlockBase::build().";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/block.api.php";s:4:"body";s:116:"
  // Add the 'user' cache context to some blocks.
  if ($some_condition) {
    $build['#contexts'][] = 'user';
  }
";}s:36:"hook_block_build_BASE_BLOCK_ID_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:36:"hook_block_build_BASE_BLOCK_ID_alter";s:10:"definition";s:108:"function hook_block_build_BASE_BLOCK_ID_alter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block)";s:11:"description";s:55:"Provide a block plugin specific block_build alteration.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/block.api.php";s:4:"body";s:102:"
  // Explicitly enable placeholdering of the specific block.
  $build['#create_placeholder'] = TRUE;
";}s:17:"hook_block_access";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:17:"hook_block_access";s:10:"definition";s:121:"function hook_block_access(\Drupal\block\Entity\Block $block, $operation, \Drupal\Core\Session\AccountInterface $account)";s:11:"description";s:35:"Control access to a block instance.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/block.api.php";s:4:"body";s:367:"
  // Example code that would prevent displaying the 'Powered by Drupal' block in
  // a region different than the footer.
  if ($operation == 'view' && $block->getPluginId() == 'system_powered_by_block') {
    return AccessResult::forbiddenIf($block->getRegion() != 'footer')->cacheUntilEntityChanges($block);
  }

  // No opinion.
  return AccessResult::neutral();
";}}s:4:"help";a:1:{s:9:"hook_help";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:9:"hook_help";s:10:"definition";s:86:"function hook_help($route_name, \Drupal\Core\Routing\RouteMatchInterface $route_match)";s:11:"description";s:25:"Provide online user help.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:4:"help";s:9:"file_path";s:46:"/Users/joachim/bin/drupal_hooks/8/help.api.php";s:4:"body";s:1153:"
  switch ($route_name) {
    // Main module help for the block module.
    case 'help.page.block':
      return '<p>' . t('Blocks are boxes of content rendered into an area, or region, of a web page. The default theme Bartik, for example, implements the regions "Sidebar first", "Sidebar second", "Featured", "Content", "Header", "Footer", etc., and a block may appear in any one of these areas. The <a href=":blocks">blocks administration page</a> provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions.', array(':blocks' => \Drupal::url('block.admin_display'))) . '</p>';

    // Help for another path in the block module.
    case 'block.admin_display':
      return '<p>' . t('This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.') . '</p>';
  }
";}}s:6:"system";a:1:{s:29:"hook_system_themes_page_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:29:"hook_system_themes_page_alter";s:10:"definition";s:54:"function hook_system_themes_page_alter(&$theme_groups)";s:11:"description";s:29:"Alters theme operation links.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:6:"system";s:9:"file_path";s:48:"/Users/joachim/bin/drupal_hooks/8/system.api.php";s:4:"body";s:351:"
  foreach ($theme_groups as $state => &$group) {
    foreach ($theme_groups[$state] as &$theme) {
      // Add a foo link to each list of theme operations.
      $theme->operations[] = array(
        'title' => t('Foo'),
        'url' => Url::fromRoute('system.themes_page'),
        'query' => array('theme' => $theme->getName())
      );
    }
  }
";}}}