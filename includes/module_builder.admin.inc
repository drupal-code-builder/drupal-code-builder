<?php

/**
 * @file
 *   Menu callbacks for admin pages.
 */

/**
 * Admin settings page.
 *
 * @ingroup module_builder_core
 */
function module_builder_admin_settings($form, &$form_state) {
  $public_dir = drupal_realpath(file_default_scheme() . '://');

  $form['module_builder_hooks_directory'] = array(
    '#type' => 'textfield',
    '#title' => t('Path to hook documentation directory'),
    '#description' => t('Subdirectory in the directory "%dir" where local copies of hook documentation should be stored.', array('%dir' => $public_dir)),
    '#default_value' => variable_get('module_builder_hooks_directory', 'hooks'),
  );

  $form['module_builder_write_directory'] = array(
    '#type' => 'textfield',
    '#title' => t('Path to write module files'),
    '#description' => t('Subdirectory in the directory "%dir" where module files should be written.', array('%dir' => $public_dir)),
    '#default_value' => variable_get('module_builder_write_directory', 'modules'),
  );

  $form['module_builder_footer'] = array(
    '#type' => 'textarea',
    '#title' => t('Module footer'),
    '#description' => t('This is the code that will be displayed at the bottom of your module file.'),
    '#rows' => 15,
    '#default_value' => variable_get('module_builder_footer', ''),
  );
  $form['module_builder_detail'] = array(
    '#type' => 'radios',
    '#title' => t('Code detail level'),
    '#description' => t('This setting will either display or suppress additional explanatory comments in the resulting module code to help new developers.'),
    '#options' => array(
      1 => t("<strong>Beginner</strong>: I'm just starting out with Drupal development; please display lots of helpful comments in my module code!"),
      0 => t("<strong>Advanced</strong>: I already know what I'm doing; don't put in a bunch of crap in my module file that I don't need!"),
    ),
    '#default_value' => variable_get('module_builder_detail', 0),
  );
  /*
  $form['module_builder_download'] = array(
    '#type' => 'radios',
    '#title' => t('Download module file checkbox defaults to'),
    '#description' => t('When checked, this will automatically generate your module file for you and prompt your browser to download it.'),
    '#options' => array(
      1 => t('Enabled'),
      0 => t('Disabled'),
    ),
    '#default_value' => variable_get('module_builder_download', 1),
  );
  */
  return system_settings_form($form);
}

/**
 * Admin hook update form.
 */
function module_builder_admin_update($form, &$form_state) {
  \ModuleBuilder\Factory::setEnvironmentClass('DrupalUI', VERSION);

  // Get our task, and check hook data is ready.
  try {
    $mb_task_handler_report = \ModuleBuilder\Factory::getTask('ReportHookDataFolder');
  }
  catch (\ModuleBuilder\Exception\SanityException $e) {
    $failed_sanity_level = $e->getFailedSanityLevel();
    switch ($failed_sanity_level) {
      case 'hook_directory':
        drupal_set_message(t("The hook data directory could not be created or is not writable."), 'error');
        break;
      case 'hook_data':
        drupal_set_message(t("Hook data is not available. Use the 'Process data' tab to get data about hooks and plugins."), 'error');
        break;
    }
    return $form;
  }

  // The task handler returns sane values for these even if there's no hook
  // data.
  $last_update = $mb_task_handler_report->lastUpdatedDate();
  $doc_files = $mb_task_handler_report->listHookFiles();
  $directory = \ModuleBuilder\Factory::getEnvironment()->getHooksDirectory();


  $form['last_update'] = array(
    '#markup' => '<p>' . (
      $last_update ?
        t('Your last hook documentation update was %date.', array(
          '%date' => format_date($last_update, 'large'),
        )) :
        t('The hook documentation has not yet been downloaded.')
      ) . '</p>',
  );

  if (count($doc_files)) {
    $form["files"] = array(
      '#markup' => '<p>' . t('You have the following hook definition files saved in %dir: ', array('%dir' => $directory)) .
         theme('item_list', array('items' => $doc_files)) . '</p>',
    );
  }

  $form['update'] = array(
    '#type' => 'submit',
    '#value' => 'Update',
  );

  return $form;
}

/**
 * Admin hook update form submit handler.
 */
function module_builder_admin_update_submit($form, $form_state) {
  \ModuleBuilder\Factory::setEnvironmentClass('DrupalUI', VERSION);

  // Safe to do this without exception handling: it's already been checked in
  // the form builder.
  $mb_task_handler_collect = \ModuleBuilder\Factory::getTask('Collect');
  $mb_task_handler_collect->collectComponentData();
}
