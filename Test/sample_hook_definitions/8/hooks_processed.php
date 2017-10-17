a:6:{s:5:"block";a:5:{s:21:"hook_block_view_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:21:"hook_block_view_alter";s:10:"definition";s:93:"function hook_block_view_alter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block)";s:11:"description";s:58:"Alter the result of \Drupal\Core\Block\BlockBase::build().";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/block.api.php";s:4:"body";s:155:"
  // Remove the contextual links on all blocks that provide them.
  if (isset($build['#contextual_links'])) {
    unset($build['#contextual_links']);
  }
";}s:35:"hook_block_view_BASE_BLOCK_ID_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:35:"hook_block_view_BASE_BLOCK_ID_alter";s:10:"definition";s:107:"function hook_block_view_BASE_BLOCK_ID_alter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block)";s:11:"description";s:54:"Provide a block plugin specific block_view alteration.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/block.api.php";s:4:"body";s:96:"
  // Change the title of the specific block.
  $build['#title'] = t('New title of the block');
";}s:22:"hook_block_build_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:22:"hook_block_build_alter";s:10:"definition";s:94:"function hook_block_build_alter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block)";s:11:"description";s:58:"Alter the result of \Drupal\Core\Block\BlockBase::build().";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/block.api.php";s:4:"body";s:125:"
  // Add the 'user' cache context to some blocks.
  if ($some_condition) {
    $build['#cache']['contexts'][] = 'user';
  }
";}s:36:"hook_block_build_BASE_BLOCK_ID_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:36:"hook_block_build_BASE_BLOCK_ID_alter";s:10:"definition";s:108:"function hook_block_build_BASE_BLOCK_ID_alter(array &$build, \Drupal\Core\Block\BlockPluginInterface $block)";s:11:"description";s:55:"Provide a block plugin specific block_build alteration.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/block.api.php";s:4:"body";s:102:"
  // Explicitly enable placeholdering of the specific block.
  $build['#create_placeholder'] = TRUE;
";}s:17:"hook_block_access";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:17:"hook_block_access";s:10:"definition";s:121:"function hook_block_access(\Drupal\block\Entity\Block $block, $operation, \Drupal\Core\Session\AccountInterface $account)";s:11:"description";s:35:"Control access to a block instance.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:5:"block";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/block.api.php";s:4:"body";s:366:"
  // Example code that would prevent displaying the 'Powered by Drupal' block in
  // a region different than the footer.
  if ($operation == 'view' && $block->getPluginId() == 'system_powered_by_block') {
    return AccessResult::forbiddenIf($block->getRegion() != 'footer')->addCacheableDependency($block);
  }

  // No opinion.
  return AccessResult::neutral();
";}}s:9:"core:form";a:7:{s:24:"callback_batch_operation";a:10:{s:4:"type";s:8:"callback";s:4:"name";s:24:"callback_batch_operation";s:10:"definition";s:62:"function callback_batch_operation($MULTIPLE_PARAMS, &$context)";s:11:"description";s:33:"Perform a single batch operation.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:2:{i:0;s:23:"callback_batch_finished";i:1;s:23:"callback_batch_finished";}s:5:"group";s:9:"core:form";s:4:"core";b:1;s:9:"file_path";s:120:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/form.api.php";s:4:"body";s:1593:"
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $database = \Drupal::database();

  if (!isset($context['sandbox']['progress'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['current_node'] = 0;
    $context['sandbox']['max'] = $database->query('SELECT COUNT(DISTINCT nid) FROM {node}')->fetchField();
  }

  // For this example, we decide that we can safely process
  // 5 nodes at a time without a timeout.
  $limit = 5;

  // With each pass through the callback, retrieve the next group of nids.
  $result = $database->queryRange("SELECT nid FROM {node} WHERE nid > :nid ORDER BY nid ASC", 0, $limit, [':nid' => $context['sandbox']['current_node']]);
  foreach ($result as $row) {

    // Here we actually perform our processing on the current node.
    $node_storage->resetCache([$row['nid']]);
    $node = $node_storage->load($row['nid']);
    $node->value1 = $options1;
    $node->value2 = $options2;
    node_save($node);

    // Store some result for post-processing in the finished callback.
    $context['results'][] = $node->title;

    // Update our progress information.
    $context['sandbox']['progress']++;
    $context['sandbox']['current_node'] = $node->nid;
    $context['message'] = t('Now processing %node', ['%node' => $node->title]);
  }

  // Inform the batch engine that we are not finished,
  // and provide an estimation of the completion level we reached.
  if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
";}s:23:"callback_batch_finished";a:10:{s:4:"type";s:8:"callback";s:4:"name";s:23:"callback_batch_finished";s:10:"definition";s:65:"function callback_batch_finished($success, $results, $operations)";s:11:"description";s:25:"Complete a batch process.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:1:{i:0;s:24:"callback_batch_operation";}s:5:"group";s:9:"core:form";s:4:"core";b:1;s:9:"file_path";s:120:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/form.api.php";s:4:"body";s:738:"
  if ($success) {
    // Here we do something meaningful with the results.
    $message = t("@count items were processed.", [
      '@count' => count($results),
      ]);
    $list = [
      '#theme' => 'item_list',
      '#items' => $results,
    ];
    $message .= drupal_render($list);
    drupal_set_message($message);
  }
  else {
    // An error occurred.
    // $operations contains the operations that remained unprocessed.
    $error_operation = reset($operations);
    $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
      '%error_operation' => $error_operation[0],
      '@arguments' => print_r($error_operation[1], TRUE)
    ]);
    drupal_set_message($message, 'error');
  }
";}s:22:"hook_ajax_render_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:22:"hook_ajax_render_alter";s:10:"definition";s:45:"function hook_ajax_render_alter(array &$data)";s:11:"description";s:55:"Alter the Ajax command data that is sent to the client.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:9:"core:form";s:4:"core";b:1;s:9:"file_path";s:120:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/form.api.php";s:4:"body";s:287:"
  // Inject any new status messages into the content area.
  $status_messages = ['#type' => 'status_messages'];
  $command = new \Drupal\Core\Ajax\PrependCommand('#block-system-main .content', \Drupal::service('renderer')->renderRoot($status_messages));
  $data[] = $command->render();
";}s:15:"hook_form_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:15:"hook_form_alter";s:10:"definition";s:92:"function hook_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id)";s:11:"description";s:46:"Perform alterations before a form is rendered.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:9:"core:form";s:4:"core";b:1;s:9:"file_path";s:120:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/form.api.php";s:4:"body";s:637:"
  if (isset($form['type']) && $form['type']['#value'] . '_node_settings' == $form_id) {
    $upload_enabled_types = \Drupal::config('mymodule.settings')->get('upload_enabled_types');
    $form['workflow']['upload_' . $form['type']['#value']] = [
      '#type' => 'radios',
      '#title' => t('Attachments'),
      '#default_value' => in_array($form['type']['#value'], $upload_enabled_types) ? 1 : 0,
      '#options' => [t('Disabled'), t('Enabled')],
    ];
    // Add a custom submit handler to save the array of types back to the config file.
    $form['actions']['submit']['#submit'][] = 'mymodule_upload_enabled_types_submit';
  }
";}s:23:"hook_form_FORM_ID_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:23:"hook_form_FORM_ID_alter";s:10:"definition";s:100:"function hook_form_FORM_ID_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id)";s:11:"description";s:75:"Provide a form-specific alteration instead of the global hook_form_alter().";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:9:"core:form";s:4:"core";b:1;s:9:"file_path";s:120:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/form.api.php";s:4:"body";s:410:"
  // Modification for the form with the given form ID goes here. For example, if
  // FORM_ID is "user_register_form" this code would run only on the user
  // registration form.

  // Add a checkbox to registration form about agreeing to terms of use.
  $form['terms_of_use'] = [
    '#type' => 'checkbox',
    '#title' => t("I agree with the website's terms and conditions."),
    '#required' => TRUE,
  ];
";}s:28:"hook_form_BASE_FORM_ID_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:28:"hook_form_BASE_FORM_ID_alter";s:10:"definition";s:105:"function hook_form_BASE_FORM_ID_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id)";s:11:"description";s:61:"Provide a form-specific alteration for shared ('base') forms.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:9:"core:form";s:4:"core";b:1;s:9:"file_path";s:120:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/form.api.php";s:4:"body";s:417:"
  // Modification for the form with the given BASE_FORM_ID goes here. For
  // example, if BASE_FORM_ID is "node_form", this code would run on every
  // node form, regardless of node type.

  // Add a checkbox to the node form about agreeing to terms of use.
  $form['terms_of_use'] = [
    '#type' => 'checkbox',
    '#title' => t("I agree with the website's terms and conditions."),
    '#required' => TRUE,
  ];
";}s:16:"hook_batch_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:16:"hook_batch_alter";s:10:"definition";s:34:"function hook_batch_alter(&$batch)";s:11:"description";s:52:"Alter batch information before a batch is processed.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:9:"core:form";s:4:"core";b:1;s:9:"file_path";s:120:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/form.api.php";s:4:"body";s:1:"
";}}s:11:"core:module";a:18:{s:14:"hook_hook_info";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:14:"hook_hook_info";s:10:"definition";s:25:"function hook_hook_info()";s:11:"description";s:55:"Defines one or more hooks that are exposed by a module.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:128:"
  $hooks['token_info'] = [
    'group' => 'tokens',
  ];
  $hooks['tokens'] = [
    'group' => 'tokens',
  ];
  return $hooks;
";}s:28:"hook_module_implements_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:28:"hook_module_implements_alter";s:10:"definition";s:63:"function hook_module_implements_alter(&$implementations, $hook)";s:11:"description";s:50:"Alter the registry of modules implementing a hook.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:487:"
  if ($hook == 'form_alter') {
    // Move my_module_form_alter() to the end of the list.
    // \Drupal::moduleHandler()->getImplementations()
    // iterates through $implementations with a foreach loop which PHP iterates
    // in the order that the items were added, so to move an item to the end of
    // the array, we remove it and then add it.
    $group = $implementations['my_module'];
    unset($implementations['my_module']);
    $implementations['my_module'] = $group;
  }
";}s:22:"hook_system_info_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:22:"hook_system_info_alter";s:10:"definition";s:92:"function hook_system_info_alter(array &$info, \Drupal\Core\Extension\Extension $file, $type)";s:11:"description";s:67:"Alter the information parsed from module and theme .info.yml files.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:160:"
  // Only fill this in if the .info.yml file does not define a 'datestamp'.
  if (empty($info['datestamp'])) {
    $info['datestamp'] = $file->getMTime();
  }
";}s:22:"hook_module_preinstall";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:22:"hook_module_preinstall";s:10:"definition";s:40:"function hook_module_preinstall($module)";s:11:"description";s:55:"Perform necessary actions before a module is installed.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:27:"
  mymodule_cache_clear();
";}s:22:"hook_modules_installed";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:22:"hook_modules_installed";s:10:"definition";s:41:"function hook_modules_installed($modules)";s:11:"description";s:54:"Perform necessary actions after modules are installed.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:121:"
  if (in_array('lousy_module', $modules)) {
    \Drupal::state()->set('mymodule.lousy_module_compatibility', TRUE);
  }
";}s:12:"hook_install";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:12:"hook_install";s:10:"definition";s:23:"function hook_install()";s:11:"description";s:49:"Perform setup tasks when the module is installed.";s:11:"destination";s:15:"%module.install";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:301:"
  // Create the styles directory and ensure it's writable.
  $directory = file_default_scheme() . '://styles';
  $mode = isset($GLOBALS['install_state']['mode']) ? $GLOBALS['install_state']['mode'] : NULL;
  file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS, $mode);
";}s:24:"hook_module_preuninstall";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:24:"hook_module_preuninstall";s:10:"definition";s:42:"function hook_module_preuninstall($module)";s:11:"description";s:57:"Perform necessary actions before a module is uninstalled.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:27:"
  mymodule_cache_clear();
";}s:24:"hook_modules_uninstalled";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:24:"hook_modules_uninstalled";s:10:"definition";s:43:"function hook_modules_uninstalled($modules)";s:11:"description";s:56:"Perform necessary actions after modules are uninstalled.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:146:"
  if (in_array('lousy_module', $modules)) {
    \Drupal::state()->delete('mymodule.lousy_module_compatibility');
  }
  mymodule_cache_rebuild();
";}s:14:"hook_uninstall";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:14:"hook_uninstall";s:10:"definition";s:25:"function hook_uninstall()";s:11:"description";s:44:"Remove any information that the module sets.";s:11:"destination";s:15:"%module.install";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:128:"
  // Remove the styles directory and generated images.
  file_unmanaged_delete_recursive(file_default_scheme() . '://styles');
";}s:18:"hook_install_tasks";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:18:"hook_install_tasks";s:10:"definition";s:44:"function hook_install_tasks(&$install_state)";s:11:"description";s:68:"Return an array of tasks to be performed by an installation profile.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:3474:"
  // Here, we define a variable to allow tasks to indicate that a particular,
  // processor-intensive batch process needs to be triggered later on in the
  // installation.
  $myprofile_needs_batch_processing = \Drupal::state()->get('myprofile.needs_batch_processing', FALSE);
  $tasks = [
    // This is an example of a task that defines a form which the user who is
    // installing the site will be asked to fill out. To implement this task,
    // your profile would define a function named myprofile_data_import_form()
    // as a normal form API callback function, with associated validation and
    // submit handlers. In the submit handler, in addition to saving whatever
    // other data you have collected from the user, you might also call
    // \Drupal::state()->set('myprofile.needs_batch_processing', TRUE) if the
    // user has entered data which requires that batch processing will need to
    // occur later on.
    'myprofile_data_import_form' => [
      'display_name' => t('Data import options'),
      'type' => 'form',
    ],
    // Similarly, to implement this task, your profile would define a function
    // named myprofile_settings_form() with associated validation and submit
    // handlers. This form might be used to collect and save additional
    // information from the user that your profile needs. There are no extra
    // steps required for your profile to act as an "installation wizard"; you
    // can simply define as many tasks of type 'form' as you wish to execute,
    // and the forms will be presented to the user, one after another.
    'myprofile_settings_form' => [
      'display_name' => t('Additional options'),
      'type' => 'form',
    ],
    // This is an example of a task that performs batch operations. To
    // implement this task, your profile would define a function named
    // myprofile_batch_processing() which returns a batch API array definition
    // that the installer will use to execute your batch operations. Due to the
    // 'myprofile.needs_batch_processing' variable used here, this task will be
    // hidden and skipped unless your profile set it to TRUE in one of the
    // previous tasks.
    'myprofile_batch_processing' => [
      'display_name' => t('Import additional data'),
      'display' => $myprofile_needs_batch_processing,
      'type' => 'batch',
      'run' => $myprofile_needs_batch_processing ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ],
    // This is an example of a task that will not be displayed in the list that
    // the user sees. To implement this task, your profile would define a
    // function named myprofile_final_site_setup(), in which additional,
    // automated site setup operations would be performed. Since this is the
    // last task defined by your profile, you should also use this function to
    // call \Drupal::state()->delete('myprofile.needs_batch_processing') and
    // clean up the state that was used above. If you want the user to pass
    // to the final Drupal installation tasks uninterrupted, return no output
    // from this function. Otherwise, return themed output that the user will
    // see (for example, a confirmation page explaining that your profile's
    // tasks are complete, with a link to reload the current page and therefore
    // pass on to the final Drupal installation tasks when the user is ready to
    // do so).
    'myprofile_final_site_setup' => [
    ],
  ];
  return $tasks;
";}s:24:"hook_install_tasks_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:24:"hook_install_tasks_alter";s:10:"definition";s:58:"function hook_install_tasks_alter(&$tasks, $install_state)";s:11:"description";s:42:"Alter the full list of installation tasks.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:233:"
  // Replace the entire site configuration form provided by Drupal core
  // with a custom callback function defined by this installation profile.
  $tasks['install_configure_form']['function'] = 'myprofile_install_configure_form';
";}s:13:"hook_update_N";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:13:"hook_update_N";s:10:"definition";s:33:"function hook_update_N(&$sandbox)";s:11:"description";s:47:"Perform a single update between minor versions.";s:11:"destination";s:15:"%module.install";s:12:"dependencies";a:1:{i:0;s:24:"callback_batch_operation";}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:2048:"
  // For non-batch updates, the signature can simply be:
  // function hook_update_N() {

  // Example function body for adding a field to a database table, which does
  // not require a batch operation:
  $spec = [
    'type' => 'varchar',
    'description' => "New Col",
    'length' => 20,
    'not null' => FALSE,
  ];
  $schema = Database::getConnection()->schema();
  $schema->addField('mytable1', 'newcol', $spec);

  // Example of what to do if there is an error during your update.
  if ($some_error_condition_met) {
    throw new UpdateException('Something went wrong; here is what you should do.');
  }

  // Example function body for a batch update. In this example, the values in
  // a database field are updated.
  if (!isset($sandbox['progress'])) {
    // This must be the first run. Initialize the sandbox.
    $sandbox['progress'] = 0;
    $sandbox['current_pk'] = 0;
    $sandbox['max'] = Database::getConnection()->query('SELECT COUNT(myprimarykey) FROM {mytable1}')->fetchField() - 1;
  }

  // Update in chunks of 20.
  $records = Database::getConnection()->select('mytable1', 'm')
    ->fields('m', ['myprimarykey', 'otherfield'])
    ->condition('myprimarykey', $sandbox['current_pk'], '>')
    ->range(0, 20)
    ->orderBy('myprimarykey', 'ASC')
    ->execute();
  foreach ($records as $record) {
    // Here, you would make an update something related to this record. In this
    // example, some text is added to the other field.
    Database::getConnection()->update('mytable1')
      ->fields(['otherfield' => $record->otherfield . '-suffix'])
      ->condition('myprimarykey', $record->myprimarykey)
      ->execute();

    $sandbox['progress']++;
    $sandbox['current_pk'] = $record->myprimarykey;
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

  // To display a message to the user when the update is completed, return it.
  // If you do not want to display a completion message, return nothing.
  return t('All foo bars were updated with the new suffix');
";}s:21:"hook_post_update_NAME";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:21:"hook_post_update_NAME";s:10:"definition";s:41:"function hook_post_update_NAME(&$sandbox)";s:11:"description";s:67:"Executes an update which is intended to update data, like entities.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:1077:"
  // Example of updating some content.
  $node = \Drupal\node\Entity\Node::load(123);
  $node->setTitle('foo');
  $node->save();

  $result = t('Node %nid saved', ['%nid' => $node->id()]);

  // Example of disabling blocks with missing condition contexts. Note: The
  // block itself is in a state which is valid at that point.
  // @see block_update_8001()
  // @see block_post_update_disable_blocks_with_missing_contexts()
  $block_update_8001 = \Drupal::keyValue('update_backup')->get('block_update_8001', []);

  $block_ids = array_keys($block_update_8001);
  $block_storage = \Drupal::entityManager()->getStorage('block');
  $blocks = $block_storage->loadMultiple($block_ids);
  /** @var $blocks \Drupal\block\BlockInterface[] */
  foreach ($blocks as $block) {
    // This block has had conditions removed due to an inability to resolve
    // contexts in block_update_8001() so disable it.

    // Disable currently enabled blocks.
    if ($block_update_8001[$block->id()]['status']) {
      $block->setStatus(FALSE);
      $block->save();
    }
  }

  return $result;
";}s:24:"hook_update_dependencies";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:24:"hook_update_dependencies";s:10:"definition";s:35:"function hook_update_dependencies()";s:11:"description";s:64:"Return an array of information about module update dependencies.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:909:"
  // Indicate that the mymodule_update_8001() function provided by this module
  // must run after the another_module_update_8003() function provided by the
  // 'another_module' module.
  $dependencies['mymodule'][8001] = [
    'another_module' => 8003,
  ];
  // Indicate that the mymodule_update_8002() function provided by this module
  // must run before the yet_another_module_update_8005() function provided by
  // the 'yet_another_module' module. (Note that declaring dependencies in this
  // direction should be done only in rare situations, since it can lead to the
  // following problem: If a site has already run the yet_another_module
  // module's database updates before it updates its codebase to pick up the
  // newest mymodule code, then the dependency declared here will be ignored.)
  $dependencies['yet_another_module'][8005] = [
    'mymodule' => 8002,
  ];
  return $dependencies;
";}s:24:"hook_update_last_removed";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:24:"hook_update_last_removed";s:10:"definition";s:35:"function hook_update_last_removed()";s:11:"description";s:64:"Return a number which is no longer available as hook_update_N().";s:11:"destination";s:15:"%module.install";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:153:"
  // We've removed the 8.x-1.x version of mymodule, including database updates.
  // The next update function is mymodule_update_8200().
  return 8103;
";}s:17:"hook_updater_info";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:17:"hook_updater_info";s:10:"definition";s:28:"function hook_updater_info()";s:11:"description";s:65:"Provide information on Updaters (classes that can update Drupal).";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:274:"
  return [
    'module' => [
      'class' => 'Drupal\Core\Updater\Module',
      'name' => t('Update modules'),
      'weight' => 0,
    ],
    'theme' => [
      'class' => 'Drupal\Core\Updater\Theme',
      'name' => t('Update themes'),
      'weight' => 0,
    ],
  ];
";}s:23:"hook_updater_info_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:23:"hook_updater_info_alter";s:10:"definition";s:44:"function hook_updater_info_alter(&$updaters)";s:11:"description";s:36:"Alter the Updater information array.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:156:"
  // Adjust weight so that the theme Updater gets a chance to handle a given
  // update task before module updaters.
  $updaters['theme']['weight'] = -1;
";}s:17:"hook_requirements";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:17:"hook_requirements";s:10:"definition";s:34:"function hook_requirements($phase)";s:11:"description";s:56:"Check installation requirements and do status reporting.";s:11:"destination";s:15:"%module.install";s:12:"dependencies";a:0:{}s:5:"group";s:11:"core:module";s:4:"core";b:1;s:9:"file_path";s:122:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/module.api.php";s:4:"body";s:1587:"
  $requirements = [];

  // Report Drupal version
  if ($phase == 'runtime') {
    $requirements['drupal'] = [
      'title' => t('Drupal'),
      'value' => \Drupal::VERSION,
      'severity' => REQUIREMENT_INFO
    ];
  }

  // Test PHP version
  $requirements['php'] = [
    'title' => t('PHP'),
    'value' => ($phase == 'runtime') ? \Drupal::l(phpversion(), new Url('system.php')) : phpversion(),
  ];
  if (version_compare(phpversion(), DRUPAL_MINIMUM_PHP) < 0) {
    $requirements['php']['description'] = t('Your PHP installation is too old. Drupal requires at least PHP %version.', ['%version' => DRUPAL_MINIMUM_PHP]);
    $requirements['php']['severity'] = REQUIREMENT_ERROR;
  }

  // Report cron status
  if ($phase == 'runtime') {
    $cron_last = \Drupal::state()->get('system.cron_last');

    if (is_numeric($cron_last)) {
      $requirements['cron']['value'] = t('Last run @time ago', ['@time' => \Drupal::service('date.formatter')->formatTimeDiffSince($cron_last)]);
    }
    else {
      $requirements['cron'] = [
        'description' => t('Cron has not run. It appears cron jobs have not been setup on your system. Check the help pages for <a href=":url">configuring cron jobs</a>.', [':url' => 'https://www.drupal.org/cron']),
        'severity' => REQUIREMENT_ERROR,
        'value' => t('Never run'),
      ];
    }

    $requirements['cron']['description'] .= ' ' . t('You can <a href=":cron">run cron manually</a>.', [':cron' => \Drupal::url('system.run_cron')]);

    $requirements['cron']['title'] = t('Cron maintenance tasks');
  }

  return $requirements;
";}}s:10:"core:theme";a:24:{s:37:"hook_form_system_theme_settings_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:37:"hook_form_system_theme_settings_alter";s:10:"definition";s:104:"function hook_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state)";s:11:"description";s:55:"Allow themes to alter the theme-specific settings form.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:320:"
  // Add a checkbox to toggle the breadcrumb trail.
  $form['toggle_breadcrumb'] = [
    '#type' => 'checkbox',
    '#title' => t('Display the breadcrumb'),
    '#default_value' => theme_get_setting('features.breadcrumb'),
    '#description'   => t('Show a trail of links from the homepage to the current page.'),
  ];
";}s:15:"hook_preprocess";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:15:"hook_preprocess";s:10:"definition";s:44:"function hook_preprocess(&$variables, $hook)";s:11:"description";s:41:"Preprocess theme variables for templates.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:870:"
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
";}s:20:"hook_preprocess_HOOK";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:20:"hook_preprocess_HOOK";s:10:"definition";s:42:"function hook_preprocess_HOOK(&$variables)";s:11:"description";s:53:"Preprocess theme variables for a specific theme hook.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:167:"
  // This example is from rdf_preprocess_image(). It adds an RDF attribute
  // to the image hook's variables.
  $variables['attributes']['typeof'] = ['foaf:Image'];
";}s:27:"hook_theme_suggestions_HOOK";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:27:"hook_theme_suggestions_HOOK";s:10:"definition";s:54:"function hook_theme_suggestions_HOOK(array $variables)";s:11:"description";s:63:"Provides alternate named suggestions for a specific theme hook.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:118:"
  $suggestions = [];

  $suggestions[] = 'hookname__' . $variables['elements']['#langcode'];

  return $suggestions;
";}s:28:"hook_theme_suggestions_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:28:"hook_theme_suggestions_alter";s:10:"definition";s:83:"function hook_theme_suggestions_alter(array &$suggestions, array $variables, $hook)";s:11:"description";s:45:"Alters named suggestions for all theme hooks.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:165:"
  // Add an interface-language specific suggestion to all theme hooks.
  $suggestions[] = $hook . '__' . \Drupal::languageManager()->getCurrentLanguage()->getId();
";}s:33:"hook_theme_suggestions_HOOK_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:33:"hook_theme_suggestions_HOOK_alter";s:10:"definition";s:81:"function hook_theme_suggestions_HOOK_alter(array &$suggestions, array $variables)";s:11:"description";s:51:"Alters named suggestions for a specific theme hook.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:91:"
  if (empty($variables['header'])) {
    $suggestions[] = 'hookname__' . 'no_header';
  }
";}s:21:"hook_themes_installed";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:21:"hook_themes_installed";s:10:"definition";s:43:"function hook_themes_installed($theme_list)";s:11:"description";s:34:"Respond to themes being installed.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:77:"
  foreach ($theme_list as $theme) {
    block_theme_initialize($theme);
  }
";}s:23:"hook_themes_uninstalled";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:23:"hook_themes_uninstalled";s:10:"definition";s:47:"function hook_themes_uninstalled(array $themes)";s:11:"description";s:36:"Respond to themes being uninstalled.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:143:"
  // Remove some state entries depending on the theme.
  foreach ($themes as $theme) {
    \Drupal::state()->delete('example.' . $theme);
  }
";}s:14:"hook_extension";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:14:"hook_extension";s:10:"definition";s:25:"function hook_extension()";s:11:"description";s:65:"Declare a template file extension to be used with a theme engine.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:72:"
  // Extension for template base names in Twig.
  return '.html.twig';
";}s:20:"hook_render_template";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:20:"hook_render_template";s:10:"definition";s:57:"function hook_render_template($template_file, $variables)";s:11:"description";s:41:"Render a template using the theme engine.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:120:"
  $twig_service = \Drupal::service('twig');

  return $twig_service->loadTemplate($template_file)->render($variables);
";}s:23:"hook_element_info_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:23:"hook_element_info_alter";s:10:"definition";s:46:"function hook_element_info_alter(array &$info)";s:11:"description";s:57:"Alter the element type information returned from modules.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:133:"
  // Decrease the default size of textfields.
  if (isset($info['textfield']['#size'])) {
    $info['textfield']['#size'] = 40;
  }
";}s:13:"hook_js_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:13:"hook_js_alter";s:10:"definition";s:88:"function hook_js_alter(&$javascript, \Drupal\Core\Asset\AttachedAssetsInterface $assets)";s:11:"description";s:73:"Perform necessary alterations to the JavaScript before it is presented on";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:190:"
  // Swap out jQuery to use an updated version of the library.
  $javascript['core/assets/vendor/jquery/jquery.min.js']['data'] = drupal_get_path('module', 'jquery_update') . '/jquery.js';
";}s:23:"hook_library_info_build";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:23:"hook_library_info_build";s:10:"definition";s:34:"function hook_library_info_build()";s:11:"description";s:32:"Add dynamic library definitions.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:1342:"
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
";}s:22:"hook_js_settings_build";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:22:"hook_js_settings_build";s:10:"definition";s:101:"function hook_js_settings_build(array &$settings, \Drupal\Core\Asset\AttachedAssetsInterface $assets)";s:11:"description";s:48:"Modify the JavaScript settings (drupalSettings).";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:114:"
  // Manipulate settings.
  if (isset($settings['dialog'])) {
    $settings['dialog']['autoResize'] = FALSE;
  }
";}s:22:"hook_js_settings_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:22:"hook_js_settings_alter";s:10:"definition";s:101:"function hook_js_settings_alter(array &$settings, \Drupal\Core\Asset\AttachedAssetsInterface $assets)";s:11:"description";s:74:"Perform necessary alterations to the JavaScript settings (drupalSettings).";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:187:"
  // Add settings.
  $settings['user']['uid'] = \Drupal::currentUser();

  // Manipulate settings.
  if (isset($settings['dialog'])) {
    $settings['dialog']['autoResize'] = FALSE;
  }
";}s:23:"hook_library_info_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:23:"hook_library_info_alter";s:10:"definition";s:57:"function hook_library_info_alter(&$libraries, $extension)";s:11:"description";s:41:"Alter libraries provided by an extension.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:1320:"
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
      $new_js = [];
      $replacements = [
        $old_path . '/farbtastic.js' => $new_path . '/farbtastic-2.0.js',
      ];
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
";}s:14:"hook_css_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:14:"hook_css_alter";s:10:"definition";s:82:"function hook_css_alter(&$css, \Drupal\Core\Asset\AttachedAssetsInterface $assets)";s:11:"description";s:51:"Alter CSS files before they are output on the page.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:102:"
  // Remove defaults.css file.
  unset($css[drupal_get_path('module', 'system') . '/defaults.css']);
";}s:21:"hook_page_attachments";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:21:"hook_page_attachments";s:10:"definition";s:51:"function hook_page_attachments(array &$attachments)";s:11:"description";s:67:"Add attachments (typically assets) to a page before it is rendered.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:291:"
  // Unconditionally attach an asset to the page.
  $attachments['#attached']['library'][] = 'core/domready';

  // Conditionally attach an asset to the page.
  if (!\Drupal::currentUser()->hasPermission('may pet kittens')) {
    $attachments['#attached']['library'][] = 'core/jquery';
  }
";}s:27:"hook_page_attachments_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:27:"hook_page_attachments_alter";s:10:"definition";s:57:"function hook_page_attachments_alter(array &$attachments)";s:11:"description";s:69:"Alter attachments (typically assets) to a page before it is rendered.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:249:"
  // Conditionally remove an asset.
  if (in_array('core/jquery', $attachments['#attached']['library'])) {
    $index = array_search('core/jquery', $attachments['#attached']['library']);
    unset($attachments['#attached']['library'][$index]);
  }
";}s:13:"hook_page_top";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:13:"hook_page_top";s:10:"definition";s:40:"function hook_page_top(array &$page_top)";s:11:"description";s:46:"Add a renderable array to the top of the page.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:62:"
  $page_top['mymodule'] = ['#markup' => 'This is the top.'];
";}s:16:"hook_page_bottom";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:16:"hook_page_bottom";s:10:"definition";s:46:"function hook_page_bottom(array &$page_bottom)";s:11:"description";s:49:"Add a renderable array to the bottom of the page.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:68:"
  $page_bottom['mymodule'] = ['#markup' => 'This is the bottom.'];
";}s:10:"hook_theme";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:10:"hook_theme";s:10:"definition";s:52:"function hook_theme($existing, $type, $theme, $path)";s:11:"description";s:51:"Register a module or theme's theme implementations.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:527:"
  return [
    'forum_display' => [
      'variables' => ['forums' => NULL, 'topics' => NULL, 'parents' => NULL, 'tid' => NULL, 'sortby' => NULL, 'forum_per_page' => NULL],
    ],
    'forum_list' => [
      'variables' => ['forums' => NULL, 'parents' => NULL, 'tid' => NULL],
    ],
    'forum_icon' => [
      'variables' => ['new_posts' => NULL, 'num_posts' => 0, 'comment_mode' => 0, 'sticky' => 0],
    ],
    'status_report' => [
      'render element' => 'requirements',
      'file' => 'system.admin.inc',
    ],
  ];
";}s:25:"hook_theme_registry_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:25:"hook_theme_registry_alter";s:10:"definition";s:52:"function hook_theme_registry_alter(&$theme_registry)";s:11:"description";s:64:"Alter the theme registry information returned from hook_theme().";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:319:"
  // Kill the next/previous forum topic navigation links.
  foreach ($theme_registry['forum_topic_navigation']['preprocess functions'] as $key => $value) {
    if ($value == 'template_preprocess_forum_topic_navigation') {
      unset($theme_registry['forum_topic_navigation']['preprocess functions'][$key]);
    }
  }
";}s:48:"hook_template_preprocess_default_variables_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:48:"hook_template_preprocess_default_variables_alter";s:10:"definition";s:70:"function hook_template_preprocess_default_variables_alter(&$variables)";s:11:"description";s:64:"Alter the default, hook-independent variables for all templates.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:theme";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/theme.api.php";s:4:"body";s:98:"
  $variables['is_admin'] = \Drupal::currentUser()->hasPermission('access administration pages');
";}}s:10:"core:token";a:4:{s:11:"hook_tokens";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:11:"hook_tokens";s:10:"definition";s:126:"function hook_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata)";s:11:"description";s:50:"Provide replacement values for placeholder tokens.";s:11:"destination";s:18:"%module.tokens.inc";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:token";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/token.api.php";s:4:"body";s:1793:"
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = [];

  if ($type == 'node' && !empty($data['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the node.
        case 'nid':
          $replacements[$original] = $node->nid;
          break;

        case 'title':
          $replacements[$original] = $node->getTitle();
          break;

        case 'edit-url':
          $replacements[$original] = $node->url('edit-form', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $node->getOwner() ? $node->getOwner() : User::load(0);
          $replacements[$original] = $account->label();
          $bubbleable_metadata->addCacheableDependency($account);
          break;

        case 'created':
          $replacements[$original] = format_date($node->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $node->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $node->getCreatedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
";}s:17:"hook_tokens_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:17:"hook_tokens_alter";s:10:"definition";s:125:"function hook_tokens_alter(array &$replacements, array $context, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata)";s:11:"description";s:48:"Alter replacement values for placeholder tokens.";s:11:"destination";s:18:"%module.tokens.inc";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:token";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/token.api.php";s:4:"body";s:649:"
  $options = $context['options'];

  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  if ($context['type'] == 'node' && !empty($context['data']['node'])) {
    $node = $context['data']['node'];

    // Alter the [node:title] token, and replace it with the rendered content
    // of a field (field_title).
    if (isset($context['tokens']['title'])) {
      $title = $node->field_title->view('default');
      $replacements[$context['tokens']['title']] = drupal_render($title);
    }
  }
";}s:15:"hook_token_info";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:15:"hook_token_info";s:10:"definition";s:26:"function hook_token_info()";s:11:"description";s:71:"Provide information about available placeholder tokens and token types.";s:11:"destination";s:18:"%module.tokens.inc";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:token";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/token.api.php";s:4:"body";s:717:"
  $type = [
    'name' => t('Nodes'),
    'description' => t('Tokens related to individual nodes.'),
    'needs-data' => 'node',
  ];

  // Core tokens for nodes.
  $node['nid'] = [
    'name' => t("Node ID"),
    'description' => t("The unique ID of the node."),
  ];
  $node['title'] = [
    'name' => t("Title"),
  ];
  $node['edit-url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the node's edit page."),
  ];

  // Chained tokens for nodes.
  $node['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $node['author'] = [
    'name' => t("Author"),
    'type' => 'user',
  ];

  return [
    'types' => ['node' => $type],
    'tokens' => ['node' => $node],
  ];
";}s:21:"hook_token_info_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:21:"hook_token_info_alter";s:10:"definition";s:38:"function hook_token_info_alter(&$data)";s:11:"description";s:70:"Alter the metadata about available placeholder tokens and token types.";s:11:"destination";s:18:"%module.tokens.inc";s:12:"dependencies";a:0:{}s:5:"group";s:10:"core:token";s:4:"core";b:1;s:9:"file_path";s:121:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/token.api.php";s:4:"body";s:497:"
  // Modify description of node tokens for our site.
  $data['tokens']['node']['nid'] = [
    'name' => t("Node ID"),
    'description' => t("The unique ID of the article."),
  ];
  $data['tokens']['node']['title'] = [
    'name' => t("Title"),
    'description' => t("The title of the article."),
  ];

  // Chained tokens for nodes.
  $data['tokens']['node']['created'] = [
    'name' => t("Date created"),
    'description' => t("The date the article was posted."),
    'type' => 'date',
  ];
";}}s:4:"help";a:2:{s:9:"hook_help";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:9:"hook_help";s:10:"definition";s:86:"function hook_help($route_name, \Drupal\Core\Routing\RouteMatchInterface $route_match)";s:11:"description";s:25:"Provide online user help.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:4:"help";s:4:"core";b:1;s:9:"file_path";s:120:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/help.api.php";s:4:"body";s:1148:"
  switch ($route_name) {
    // Main module help for the block module.
    case 'help.page.block':
      return '<p>' . t('Blocks are boxes of content rendered into an area, or region, of a web page. The default theme Bartik, for example, implements the regions "Sidebar first", "Sidebar second", "Featured", "Content", "Header", "Footer", etc., and a block may appear in any one of these areas. The <a href=":blocks">blocks administration page</a> provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions.', [':blocks' => \Drupal::url('block.admin_display')]) . '</p>';

    // Help for another path in the block module.
    case 'block.admin_display':
      return '<p>' . t('This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.') . '</p>';
  }
";}s:28:"hook_help_section_info_alter";a:10:{s:4:"type";s:4:"hook";s:4:"name";s:28:"hook_help_section_info_alter";s:10:"definition";s:45:"function hook_help_section_info_alter(&$info)";s:11:"description";s:60:"Perform alterations on help page section plugin definitions.";s:11:"destination";s:14:"%module.module";s:12:"dependencies";a:0:{}s:5:"group";s:4:"help";s:4:"core";b:1;s:9:"file_path";s:120:"/Users/joachim/Sites/8-drupal/vendor/drupal-code-builder/drupal-code-builder/Test/sample_hook_definitions/8/help.api.php";s:4:"body";s:117:"
  // Alter the header for the module overviews section.
  $info['hook_help']['header'] = t('Overviews of modules');
";}}}