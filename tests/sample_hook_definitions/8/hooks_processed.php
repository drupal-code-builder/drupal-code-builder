a:4:{s:5:"block";a:5:{s:21:"hook_block_view_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:21:"hook_block_view_alter";s:10:"definition";s:93:"function hook_block_view_alter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block)";s:11:"description";s:58:"Alter the result of \Drupal\Core\Block\BlockBase::build().";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/block.api.php";s:4:"body";s:155:"
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
";}s:17:"hook_block_access";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:17:"hook_block_access";s:10:"definition";s:121:"function hook_block_access(\Drupal\block\Entity\Block $block, $operation, \Drupal\Core\Session\AccountInterface $account)";s:11:"description";s:35:"Control access to a block instance.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/block.api.php";s:4:"body";s:366:"
  // Example code that would prevent displaying the 'Powered by Drupal' block in
  // a region different than the footer.
  if ($operation == 'view' && $block->getPluginId() == 'system_powered_by_block') {
    return AccessResult::forbiddenIf($block->getRegion() != 'footer')->addCacheableDependency($block);
  }

  // No opinion.
  return AccessResult::neutral();
";}}s:4:"help";a:2:{s:9:"hook_help";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:9:"hook_help";s:10:"definition";s:86:"function hook_help($route_name, \Drupal\Core\Routing\RouteMatchInterface $route_match)";s:11:"description";s:25:"Provide online user help.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:4:"help";s:9:"file_path";s:46:"/Users/joachim/bin/drupal_hooks/8/help.api.php";s:4:"body";s:1153:"
  switch ($route_name) {
    // Main module help for the block module.
    case 'help.page.block':
      return '<p>' . t('Blocks are boxes of content rendered into an area, or region, of a web page. The default theme Bartik, for example, implements the regions "Sidebar first", "Sidebar second", "Featured", "Content", "Header", "Footer", etc., and a block may appear in any one of these areas. The <a href=":blocks">blocks administration page</a> provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions.', array(':blocks' => \Drupal::url('block.admin_display'))) . '</p>';

    // Help for another path in the block module.
    case 'block.admin_display':
      return '<p>' . t('This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.') . '</p>';
  }
";}s:28:"hook_help_section_info_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:28:"hook_help_section_info_alter";s:10:"definition";s:45:"function hook_help_section_info_alter(&$info)";s:11:"description";s:60:"Perform alterations on help page section plugin definitions.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:4:"help";s:9:"file_path";s:46:"/Users/joachim/bin/drupal_hooks/8/help.api.php";s:4:"body";s:117:"
  // Alter the header for the module overviews section.
  $info['hook_help']['header'] = t('Overviews of modules');
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
";}}s:0:"";a:24:{s:37:"hook_form_system_theme_settings_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:37:"hook_form_system_theme_settings_alter";s:10:"definition";s:104:"function hook_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state)";s:11:"description";s:55:"Allow themes to alter the theme-specific settings form.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:325:"
  // Add a checkbox to toggle the breadcrumb trail.
  $form['toggle_breadcrumb'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display the breadcrumb'),
    '#default_value' => theme_get_setting('features.breadcrumb'),
    '#description'   => t('Show a trail of links from the homepage to the current page.'),
  );
";}s:15:"hook_preprocess";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:15:"hook_preprocess";s:10:"definition";s:44:"function hook_preprocess(&$variables, $hook)";s:11:"description";s:41:"Preprocess theme variables for templates.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:869:"
 static $hooks;

  // Add contextual links to the variables, if the user has permission.

  if (!\Drupal::currentUser()->hasPermission('access contextual links')) {
    return;
  }

  if (!isset($hooks)) {
    $hooks = theme_get_registry();
  }

  // Determine the primary theme function argument.
  if (isset($hooks[$hook]['variables'])) {
    $keys = array_keys($hooks[$hook]['variables']);
    $key = $keys[0];
  }
  else {
    $key = $hooks[$hook]['render element'];
  }

  if (isset($variables[$key])) {
    $element = $variables[$key];
  }

  if (isset($element) && is_array($element) && !empty($element['#contextual_links'])) {
    $variables['title_suffix']['contextual_links'] = contextual_links_view($element);
    if (!empty($variables['title_suffix']['contextual_links'])) {
      $variables['attributes']['class'][] = 'contextual-links-region';
    }
  }
";}s:20:"hook_preprocess_HOOK";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:20:"hook_preprocess_HOOK";s:10:"definition";s:42:"function hook_preprocess_HOOK(&$variables)";s:11:"description";s:53:"Preprocess theme variables for a specific theme hook.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:172:"
  // This example is from rdf_preprocess_image(). It adds an RDF attribute
  // to the image hook's variables.
  $variables['attributes']['typeof'] = array('foaf:Image');
";}s:27:"hook_theme_suggestions_HOOK";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:27:"hook_theme_suggestions_HOOK";s:10:"definition";s:54:"function hook_theme_suggestions_HOOK(array $variables)";s:11:"description";s:63:"Provides alternate named suggestions for a specific theme hook.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:119:"
  $suggestions = array();

  $suggestions[] = 'node__' . $variables['elements']['#langcode'];

  return $suggestions;
";}s:28:"hook_theme_suggestions_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:28:"hook_theme_suggestions_alter";s:10:"definition";s:83:"function hook_theme_suggestions_alter(array &$suggestions, array $variables, $hook)";s:11:"description";s:45:"Alters named suggestions for all theme hooks.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:165:"
  // Add an interface-language specific suggestion to all theme hooks.
  $suggestions[] = $hook . '__' . \Drupal::languageManager()->getCurrentLanguage()->getId();
";}s:33:"hook_theme_suggestions_HOOK_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:33:"hook_theme_suggestions_HOOK_alter";s:10:"definition";s:81:"function hook_theme_suggestions_HOOK_alter(array &$suggestions, array $variables)";s:11:"description";s:51:"Alters named suggestions for a specific theme hook.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:91:"
  if (empty($variables['header'])) {
    $suggestions[] = 'hookname__' . 'no_header';
  }
";}s:21:"hook_themes_installed";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:21:"hook_themes_installed";s:10:"definition";s:43:"function hook_themes_installed($theme_list)";s:11:"description";s:34:"Respond to themes being installed.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:77:"
  foreach ($theme_list as $theme) {
    block_theme_initialize($theme);
  }
";}s:23:"hook_themes_uninstalled";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:23:"hook_themes_uninstalled";s:10:"definition";s:47:"function hook_themes_uninstalled(array $themes)";s:11:"description";s:36:"Respond to themes being uninstalled.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:143:"
  // Remove some state entries depending on the theme.
  foreach ($themes as $theme) {
    \Drupal::state()->delete('example.' . $theme);
  }
";}s:14:"hook_extension";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:14:"hook_extension";s:10:"definition";s:25:"function hook_extension()";s:11:"description";s:65:"Declare a template file extension to be used with a theme engine.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:72:"
  // Extension for template base names in Twig.
  return '.html.twig';
";}s:20:"hook_render_template";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:20:"hook_render_template";s:10:"definition";s:57:"function hook_render_template($template_file, $variables)";s:11:"description";s:41:"Render a template using the theme engine.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:120:"
  $twig_service = \Drupal::service('twig');

  return $twig_service->loadTemplate($template_file)->render($variables);
";}s:23:"hook_element_info_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:23:"hook_element_info_alter";s:10:"definition";s:47:"function hook_element_info_alter(array &$types)";s:11:"description";s:57:"Alter the element type information returned from modules.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:135:"
  // Decrease the default size of textfields.
  if (isset($types['textfield']['#size'])) {
    $types['textfield']['#size'] = 40;
  }
";}s:13:"hook_js_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:13:"hook_js_alter";s:10:"definition";s:88:"function hook_js_alter(&$javascript, \Drupal\Core\Asset\AttachedAssetsInterface $assets)";s:11:"description";s:73:"Perform necessary alterations to the JavaScript before it is presented on";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:190:"
  // Swap out jQuery to use an updated version of the library.
  $javascript['core/assets/vendor/jquery/jquery.min.js']['data'] = drupal_get_path('module', 'jquery_update') . '/jquery.js';
";}s:23:"hook_library_info_build";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:23:"hook_library_info_build";s:10:"definition";s:34:"function hook_library_info_build()";s:11:"description";s:32:"Add dynamic library definitions.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:1342:"
  $libraries = [];
  // Add a library whose information changes depending on certain conditions.
  $libraries['mymodule.zombie'] = [
    'dependencies' => [
      'core/backbone',
    ],
  ];
  if (Drupal::moduleHandler()->moduleExists('minifyzombies')) {
    $libraries['mymodule.zombie'] += [
      'js' => [
        'mymodule.zombie.min.js' => [],
      ],
      'css' => [
        'base' => [
          'mymodule.zombie.min.css' => [],
        ],
      ],
    ];
  }
  else {
    $libraries['mymodule.zombie'] += [
      'js' => [
        'mymodule.zombie.js' => [],
      ],
      'css' => [
        'base' => [
          'mymodule.zombie.css' => [],
        ],
      ],
    ];
  }

  // Add a library only if a certain condition is met. If code wants to
  // integrate with this library it is safe to (try to) load it unconditionally
  // without reproducing this check. If the library definition does not exist
  // the library (of course) not be loaded but no notices or errors will be
  // triggered.
  if (Drupal::moduleHandler()->moduleExists('vampirize')) {
    $libraries['mymodule.vampire'] = [
      'js' => [
        'js/vampire.js' => [],
      ],
      'css' => [
        'base' => [
          'css/vampire.css',
        ],
      ],
      'dependencies' => [
        'core/jquery',
      ],
    ];
  }
  return $libraries;
";}s:22:"hook_js_settings_build";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:22:"hook_js_settings_build";s:10:"definition";s:101:"function hook_js_settings_build(array &$settings, \Drupal\Core\Asset\AttachedAssetsInterface $assets)";s:11:"description";s:48:"Modify the JavaScript settings (drupalSettings).";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:114:"
  // Manipulate settings.
  if (isset($settings['dialog'])) {
    $settings['dialog']['autoResize'] = FALSE;
  }
";}s:22:"hook_js_settings_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:22:"hook_js_settings_alter";s:10:"definition";s:101:"function hook_js_settings_alter(array &$settings, \Drupal\Core\Asset\AttachedAssetsInterface $assets)";s:11:"description";s:74:"Perform necessary alterations to the JavaScript settings (drupalSettings).";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:187:"
  // Add settings.
  $settings['user']['uid'] = \Drupal::currentUser();

  // Manipulate settings.
  if (isset($settings['dialog'])) {
    $settings['dialog']['autoResize'] = FALSE;
  }
";}s:23:"hook_library_info_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:23:"hook_library_info_alter";s:10:"definition";s:57:"function hook_library_info_alter(&$libraries, $extension)";s:11:"description";s:41:"Alter libraries provided by an extension.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:1330:"
  // Update Farbtastic to version 2.0.
  if ($extension == 'core' && isset($libraries['jquery.farbtastic'])) {
    // Verify existing version is older than the one we are updating to.
    if (version_compare($libraries['jquery.farbtastic']['version'], '2.0', '<')) {
      // Update the existing Farbtastic to version 2.0.
      $libraries['jquery.farbtastic']['version'] = '2.0';
      // To accurately replace library files, the order of files and the options
      // of each file have to be retained; e.g., like this:
      $old_path = 'assets/vendor/farbtastic';
      // Since the replaced library files are no longer located in a directory
      // relative to the original extension, specify an absolute path (relative
      // to DRUPAL_ROOT / base_path()) to the new location.
      $new_path = '/' . drupal_get_path('module', 'farbtastic_update') . '/js';
      $new_js = array();
      $replacements = array(
        $old_path . '/farbtastic.js' => $new_path . '/farbtastic-2.0.js',
      );
      foreach ($libraries['jquery.farbtastic']['js'] as $source => $options) {
        if (isset($replacements[$source])) {
          $new_js[$replacements[$source]] = $options;
        }
        else {
          $new_js[$source] = $options;
        }
      }
      $libraries['jquery.farbtastic']['js'] = $new_js;
    }
  }
";}s:14:"hook_css_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:14:"hook_css_alter";s:10:"definition";s:82:"function hook_css_alter(&$css, \Drupal\Core\Asset\AttachedAssetsInterface $assets)";s:11:"description";s:51:"Alter CSS files before they are output on the page.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:102:"
  // Remove defaults.css file.
  unset($css[drupal_get_path('module', 'system') . '/defaults.css']);
";}s:21:"hook_page_attachments";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:21:"hook_page_attachments";s:10:"definition";s:51:"function hook_page_attachments(array &$attachments)";s:11:"description";s:67:"Add attachments (typically assets) to a page before it is rendered.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:291:"
  // Unconditionally attach an asset to the page.
  $attachments['#attached']['library'][] = 'core/domready';

  // Conditionally attach an asset to the page.
  if (!\Drupal::currentUser()->hasPermission('may pet kittens')) {
    $attachments['#attached']['library'][] = 'core/jquery';
  }
";}s:27:"hook_page_attachments_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:27:"hook_page_attachments_alter";s:10:"definition";s:57:"function hook_page_attachments_alter(array &$attachments)";s:11:"description";s:69:"Alter attachments (typically assets) to a page before it is rendered.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:249:"
  // Conditionally remove an asset.
  if (in_array('core/jquery', $attachments['#attached']['library'])) {
    $index = array_search('core/jquery', $attachments['#attached']['library']);
    unset($attachments['#attached']['library'][$index]);
  }
";}s:13:"hook_page_top";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:13:"hook_page_top";s:10:"definition";s:40:"function hook_page_top(array &$page_top)";s:11:"description";s:46:"Add a renderable array to the top of the page.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:62:"
  $page_top['mymodule'] = ['#markup' => 'This is the top.'];
";}s:16:"hook_page_bottom";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:16:"hook_page_bottom";s:10:"definition";s:46:"function hook_page_bottom(array &$page_bottom)";s:11:"description";s:49:"Add a renderable array to the bottom of the page.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:68:"
  $page_bottom['mymodule'] = ['#markup' => 'This is the bottom.'];
";}s:10:"hook_theme";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:10:"hook_theme";s:10:"definition";s:52:"function hook_theme($existing, $type, $theme, $path)";s:11:"description";s:51:"Register a module or theme's theme implementations.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:567:"
  return array(
    'forum_display' => array(
      'variables' => array('forums' => NULL, 'topics' => NULL, 'parents' => NULL, 'tid' => NULL, 'sortby' => NULL, 'forum_per_page' => NULL),
    ),
    'forum_list' => array(
      'variables' => array('forums' => NULL, 'parents' => NULL, 'tid' => NULL),
    ),
    'forum_icon' => array(
      'variables' => array('new_posts' => NULL, 'num_posts' => 0, 'comment_mode' => 0, 'sticky' => 0),
    ),
    'status_report' => array(
      'render element' => 'requirements',
      'file' => 'system.admin.inc',
    ),
  );
";}s:25:"hook_theme_registry_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:25:"hook_theme_registry_alter";s:10:"definition";s:52:"function hook_theme_registry_alter(&$theme_registry)";s:11:"description";s:64:"Alter the theme registry information returned from hook_theme().";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:319:"
  // Kill the next/previous forum topic navigation links.
  foreach ($theme_registry['forum_topic_navigation']['preprocess functions'] as $key => $value) {
    if ($value == 'template_preprocess_forum_topic_navigation') {
      unset($theme_registry['forum_topic_navigation']['preprocess functions'][$key]);
    }
  }
";}s:48:"hook_template_preprocess_default_variables_alter";a:9:{s:4:"type";s:4:"hook";s:4:"name";s:48:"hook_template_preprocess_default_variables_alter";s:10:"definition";s:70:"function hook_template_preprocess_default_variables_alter(&$variables)";s:11:"description";s:64:"Alter the default, hook-independent variables for all templates.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";N;s:9:"file_path";s:47:"/Users/joachim/bin/drupal_hooks/8/theme.api.php";s:4:"body";s:98:"
  $variables['is_admin'] = \Drupal::currentUser()->hasPermission('access administration pages');
";}}}