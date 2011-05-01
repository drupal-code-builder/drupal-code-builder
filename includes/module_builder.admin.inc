<?php

/**
 * @file
 *   Menu callbacks for admin pages.
 *
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

  $form['module_builder_header'] = array(
    '#type' => 'textarea',
    '#title' => t('Module header'),
    '#description' => t('This is the code that will be displayed at the top of your module file.'),
    '#rows' => 15,
    '#default_value' => variable_get('module_builder_header', MODULE_BUILDER_HEADER_DEFAULT),
    // TODO: kill MODULE_BUILDER_HEADER_DEFAULT
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
  // Check settings; this will create the folder if needed.
  try {
    _module_builder_sanity_check();
  }
  catch (ModuleBuilderException $e) {
    if ($e->needs_hooks_download) {
      $last_update = FALSE;
    }
  }

  // Include generating component file.
  module_builder_include('process');
  if (!isset($last_update)) {
    $last_update = module_builder_get_hook_data_last_updated();
  }

  $form['last_update'] = array(
    '#markup' => '<p>' . (
      $last_update ?
        t('Your last hook documentation update was %date.', array('%date' => $last_update)) :
        t('The hook documentation has not yet been downloaded.')
      ) . '</p>',
  );

  $doc_files = module_builder_get_doc_files();
  $dir = _module_builder_get_hooks_directory();
  if (count($doc_files)) {
    $form["files"] = array(
      '#markup' => '<p>' . t('You have the following hook definition files saved in %dir: ', array('%dir' => $dir)) .
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
  module_builder_update_data();
}
