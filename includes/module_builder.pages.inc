<?php

/**
 * @file
 * Menu callback for main module builder page.
 *
 */

/**
 * @defgroup module_builder_callback Functions which are the menu callbacks for this module
 */

/**
 * Displays module builder interface via a multi-step form.
 * The steps are:
 *
 * - input    => shows a form where the user can enter module options.
 * - module   => shows the generated module and info files.
 * - download => pushes a file for download.
 * - write    => writes files.
 *
 * @ingroup module_builder_callback
 * @param $form_values will be NULL when the page is first displayed,
 *   when the form is submitted, this will be an array of the submitted
 *   values.
 * @return
 *   One of three results depending on the state of this multi-step form.
 *   Form for entering module options
 *   Form showing built module and info file
 *   Nothing, but file is pushed to client for download
 */
function module_builder_page($form, &$form_state) {
  // Set up multistep form.
  // Once again, thank you examples.module!
  if (empty($form_state['step'])) {
    $form_state['step'] = 'input';

    // This array contains the function to be called at each step to get the
    // relevant form elements. It will also store state information for each
    // step.
    $form_state['step_information'] = array(
      'input'     => 'module_builder_page_input',
      'generate'  => 'module_builder_page_generate',
      // back: same as input
      // download: 'module_builder_page_download'
    );
  }

  // Call the function named in $form_state['step_information'] to get the
  // form elements to display for this step.
  $form_builder_function = $form_state['step_information'][$form_state['step']];
  $form = $form_builder_function($form, $form_state);

  return $form;
}

/**
 * Form builder for page 1.
 */
function module_builder_page_input($form, &$form_state) {
  // Get a MB factory.
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrupalUI');

  // Get our task handler, which checks hook data is ready, and our generate
  // handler, from which to get the list of properties to collect.
  try {
    $mb_task_handler_report = $mb_factory->getTask('ReportHookData');
    $mb_task_handler_generate = $mb_factory->getTask('Generate', 'module');
  }
  catch (ModuleBuilderException $e) {
    if ($e->needs_hooks_download) {
      drupal_set_message(t('No hooks were found. Please check the documentation path specified in the <a href="!settings-url">%administer >> %config >> %develop >> %modulebuilder</a> page.',
        array(
          '!settings-url' => url('admin/config/development/module_builder'),
          '%administer' => 'Administer',
          '%config' => 'Site configuration',
          '%develop' => 'Development',
          '%modulebuilder' => "Module builder",
        )
      ), 'warning');
      return $form;
    }
  }

  $root_generator = $mb_task_handler_generate->getRootGenerator();

  // Get the component data info.
  $component_data_info = $root_generator->getComponentDataInfo();

  $component_data = array();

  // Work through the component data info, assembling the component data array
  // Each property info needs to be prepared, so iterate by reference.
  foreach ($component_data_info as $property_name => &$property_info) {
    // Prepare the single property: get options, default value, etc.
    $root_generator->prepareComponentDataProperty($property_name, $property_info, $component_data);
    //dsm($component_data, $property_name);

    $element = array(
      '#title' => $property_info['label'],
      '#default_value' => $component_data[$property_name],
      '#required' => $property_info['required'],
    );

    // The type of the form element depends on the format of the component data
    // property.
    if ($property_info['format'] == 'array') {
      if (isset($property_info['options'])) {
        $element['#type'] = 'checkboxes';
        $element['#options'] = $property_info['options'];
      }
      else {
        $element['#type'] = 'textarea';
        $element['#description'] = t("Enter one item per line.");
      }
    }
    elseif ($property_info['format'] == 'boolean') {
      $element['#type'] = 'checkbox';
    }
    else {
      $element['#type'] = 'textfield';
    }

    // Ajax. WIP!
    //$element['#ajax']['callback'] = 'module_builder_builder_form_ajax';

    $form[$property_name] = $element;
  }

  // Get the hook names for the presets for our Javascript to work with.
  $mb_task_handler_report_presets = $mb_factory->getTask('ReportHookPresets');
  $hook_presets = $mb_task_handler_report_presets->getHookPresets();
  foreach ($hook_presets as $hook_preset_name => $hook_preset_data) {
    // Build a reverse array whose keys are hooks and values are arrays
    // of one of more preset names.
    // This allows us further down the line to put classes on each hook
    // checkbox for the presets they belong to: this is what the JS then uses.
    foreach ($hook_preset_data['hooks'] as $hook) {
      $hook_presets_reverse[$hook][] = "preset-$hook_preset_name";
    }
  }
  //dsm($hook_presets_reverse);

  // Rework the hooks form element into fieldsets with checkboxes.
  $hook_groups = $component_data_info['hooks']['options'];
  $form['hooks'] = array(
    '#title' => t('Use the following specific hooks'),
    '#tree' => TRUE,
  );
  foreach ($hook_groups as $hook_group => $hooks) {
    $form['hooks'][$hook_group] = array(
      '#type' => 'fieldset',
      '#title' => $hook_group . ' hooks',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      //'#theme'  => 'module_builder_hook_list',
      '#tree' => TRUE,
    );
    foreach ($hooks as $hook_name => $hook_description) {
      $form['hooks'][$hook_group][$hook_name] = array(
        '#type' => 'checkbox',
        '#title' => $hook_name,
        '#description' => $hook_description,
        '#repopulate' => TRUE,

        // TODO: For some reason this gives me some wacky error under PHP 5:
        // Fatal error: Cannot use string offset as an array
        //'#default_value' => $edit['hooks'][$hook_group][$hook],

        // TODO: I would *like* to do something like the following, so some of the long
        // descriptions don't totally mangle the page output, but need a way to do like
        // a "onmouseover" effect to display the full thing. Note that 'title' is not
        // a valid attribute for divs. :\
        //'#description' => truncate_utf8($hook_description, 40, TRUE, TRUE),

      );
      // If this hook belongs to any presets, add the classes to it for the
      // Javascript to find.
      if (isset($hook_presets_reverse[$hook_name])) {
        $form['hooks'][$hook_group][$hook_name]['#attributes'] = array(
          'class' => $hook_presets_reverse[$hook_name],
        );
      }

      // Set some default hooks
      if ($hook_name == 'hook_menu') {
        $form['hooks'][$hook_group][$hook_name]['#default_value'] = 1;
      }
    }
    // Sort list alphabetically
    ksort($form['hooks'][$hook_group]);
  }

  // Add our own (longer) descriptions and defaults.
  // TODO: consider moving some of these to the Module generator.
  $form['module_root_name']['#description'] = t('This string is used to name the module files and to prefix all of your functions. This must only contain letters, numbers, and underscores, and may not start with a number.');
  $form['module_readable_name']['#description'] = t('Name of your module as it will appear on the module admin page.');
  $form['module_short_description']['#description'] = t('This text will appear in the module admin list at <a href="!adminmodules">%administer >> %modules</a>.',
    array(
      '!adminmodules' => url('admin/modules'),
      '%administer' => 'Administer',
      '%modules' => 'Modules',
    ));
  $form['module_short_description']['#default_value'] = t('Does awesome things. Makes tea. Washes up. Favours of a personal nature.');
  $form['module_help_text']['#description'] = t('Help text (HTML) to appear within the system help at <a href="!help">%administer >> %help >> module_name</a>',
    array(
      '!help' => url('admin/help'),
      '%administer' => 'Administer',
      '%help' => 'Help',
    ));
  $form['module_dependencies']['#description'] = t('Space separated list of other modules that your module requires.');
  $form['module_package']['#description'] = t('If your module comes with other modules or is meant to be used exclusively with other modules, enter the package name here. Suggested package names: Audio, Bot, CCK, Chat, E-Commerce, Event, Feed parser, Organic groups, Station, Video, Views and Voting.'
    . '<br />'
    . 'For more information on package names, see the <a href="!url-package">documentation on Drupal.org</a>.', array(
      '!url-package' => 'http://drupal.org/node/542202#package',
    ));

  $form['#attached']['js'] = array(
    drupal_get_path('module', 'module_builder') . '/theme/module_builder.js',
  );

  // Include CSS for formatting
  drupal_add_css(drupal_get_path('module', 'module_builder') . '/theme/module_builder.css');

  // Mark form as fresh to enable JS clearing of fields with sample text.
  // TODO: multiform re-fill may work differently on D7.
  // Failing that, look into ctools?
  $form['#attributes'] = array('class' => 'fresh');

  $form['generate_module'] = array(
    '#type' => 'submit',
    '#name' => 'generate',
    '#value' => t('Generate'),
  );
  $form['#submit'] = array('module_builder_page_input_submit');

  //dsm($form);
  return $form;

  // ================================ Old form code below.

  /*
  // built some default values.
  // these are either hardcoded or what the user already put into the form on a previous go round.
  $form_default_values = array(
    'module_root_name' => 'mymodule',
  );
  if (isset($form_state['storage']['input'])) {
    $form_default_values = array_merge($form_default_values, $form_state['storage']['input']);
  }

  #dsm($form_default_values);
  #dsm($form_state['storage']['input']);
  dsm($form_state);
  */

  // Get list of hooks from downloaded documentation, organized in fieldsets.
  // include the data processing file
  // TODO: only need the data loading.
  $hook_groups = $mb_task_handler_report->listHookData();

  // Include CSS for formatting
  drupal_add_css(drupal_get_path('module', 'module_builder') . '/theme/module_builder.css');

  // Mark form as fresh to enable JS clearing of fields with sample text.
  // TODO: multiform re-fill may work differently on D7.
  // Failing that, look into ctools?
  $form['#attributes'] = array('class' => 'fresh');
  /*
  if ($form_state['clicked_button']['#name'] != 'back') {
    $form['#attributes'] = array('class' => 'fresh');
  }
  */

  // Module properties
  $form['module_root_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Machine-readable name'),
    '#description' => t('This string is used to name the module files and to prefix all of your functions. This must only contain letters, numbers, and underscores, and may not start with a number.'),
    '#required' => TRUE,
    '#default_value' => t('mymodule'), # $form_default_values['module_root_name'],
    '#repopulate' => TRUE,
  );
  $form['module_readable_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#description' => t('Name of your module as it will appear on the module admin page.'),
    '#required' => TRUE,
    '#default_value' => t('My Module'),
    '#repopulate' => TRUE,
  );
  $form['module_short_description'] = array(
    '#type' => 'textfield',
    '#title' => t('Description'),
    '#description' => t('This text will appear in the module admin list at <a href="!adminmodules">%administer >> %modules</a>.',
      array(
        '!adminmodules' => url('admin/modules'),
        '%administer' => 'Administer',
        '%modules' => 'Modules',
      )),
    '#required' => TRUE,
    '#default_value' => t('Does awesome things. Makes tea. Washes up. Favours of a personal nature.'),
    '#repopulate' => TRUE,
  );
  $form['module_help_text'] = array(
    '#type' => 'textarea',
    '#title' => t('Help text'),
    '#description' => t('Help text (HTML) to appear within the system help at <a href="!help">%administer >> %help >> module_name</a>',
      array(
        '!help' => url('admin/help'),
        '%administer' => 'Administer',
        '%help' => 'Help',
      )),
    '#repopulate' => TRUE,
  );
  $form['module_dependencies'] = array(
    '#type' => 'textfield',
    '#title' => t('Dependencies'),
      '#description' => t('Space separated list of other modules that your module requires.'),
    '#repopulate' => TRUE,
  );
  $form['module_package'] = array(
    '#type' => 'textfield',
    '#title' => t('Package'),
    '#description' => t('If your module comes with other modules or is meant to be used exclusively with other modules, enter the package name here. Suggested package names: Audio, Bot, CCK, Chat, E-Commerce, Event, Feed parser, Organic groups, Station, Video, Views and Voting.'
      . '<br />'
      . t('For more information on package names, see the <a href="!url-package">documentation on Drupal.org</a>.', array(
        '!url-package' => 'http://drupal.org/node/542202#package',
      ))),
    '#repopulate' => TRUE,
  );

  // Get the hook presets list.
  try {
    $mb_task_handler_report_presets = $mb_factory->getTask('ReportHookPresets');
  }
  catch (ModuleBuilderException $e) {
    // We've already sanity-checked, so doubtful we come here.
    return $form;
  }

  $hook_presets = $mb_task_handler_report_presets->getHookPresets();

  $form['hook_presets'] = array(
    '#type' => 'fieldset',
    '#title' => t('Hook presets'),
    '#description' => t('Selecting one or more of these features will automatically select appropriate hooks for you.'),
  );

  $path = drupal_get_path('module', 'module_builder');
  drupal_add_js($path . '/theme/module_builder.js');

  foreach ($hook_presets as $hook_preset_name => $hook_preset_data) {
    // Build a reverse array whose keys are hooks and values are arrays
    // of one of more preset names.
    // This allows us further down the line to put classes on each hook
    // checkbox for the presets they belong to: this is what the JS then uses.
    foreach ($hook_preset_data['hooks'] as $hook) {
      $hook_presets_reverse[$hook][] = "preset-$hook_preset_name";
    }

    $form['hook_presets'][$hook_preset_name] = array(
      '#type' => 'checkbox',
      '#title' => $hook_preset_data['label'],

      // TODO: For some reason this gives me some wacky error under PHP 5:
      // Fatal error: Cannot use string offset as an array
      //'#default_value' => $edit['hook_groups'][$i],
    );
  }

  // Build hooks list
  $form['hooks'] = array(
    '#title' => t('Use the following specific hooks'),
    '#tree' => TRUE,
  );

  foreach ($hook_groups as $hook_group => $hooks) {
    $form['hooks'][$hook_group] = array(
      '#type' => 'fieldset',
      '#title' => $hook_group . ' hooks',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      //'#theme'  => 'module_builder_hook_list',
      '#tree' => TRUE,
    );
    foreach ($hooks as $hook) {
      $name = $hook['name'];
      $desc = $hook['description'];
      $form['hooks'][$hook_group][$name] = array(
        '#type' => 'checkbox',
        '#title' => preg_replace('/^hook_/', '', $name),
        '#description' => $desc,
        '#repopulate' => TRUE,

        // TODO: For some reason this gives me some wacky error under PHP 5:
        // Fatal error: Cannot use string offset as an array
        //'#default_value' => $edit['hooks'][$hook_group][$hook],

        // TODO: I would *like* to do something like the following, so some of the long
        // descriptions don't totally mangle the page output, but need a way to do like
        // a "onmouseover" effect to display the full thing. Note that 'title' is not
        // a valid attribute for divs. :\
        //'#description' => truncate_utf8($desc, 40, TRUE, TRUE),

      );
      // If this hook belongs to any presets, add the classes to it for the
      // Javascript to find.
      if (isset($hook_presets_reverse[$name])) {
        $form['hooks'][$hook_group][$name]['#attributes'] = array(
          'class' => $hook_presets_reverse[$name],
        );
      }

      // Set some default hooks
      if ($name == 'hook_menu') {
        $form['hooks'][$hook_group][$name]['#default_value'] = 1;
      }
    }
    // Sort list alphabetically
    ksort($form['hooks'][$hook_group]);
  }

  $form['generate_module'] = array(
    '#type' => 'submit',
    '#name' => 'generate',
    '#value' => t('Generate'),
  );
  $form['#submit'] = array('module_builder_page_input_submit');

  #if ($form_state['rebuild']) { // fails as a test!?!?!
  /*
  if ($form_state['values']) {
    #dsm('rebuild');
    $form = _form_repopulate($form, $form_state);
    #dsm($form_state['storage']['input']);
  }
  */

  return $form;
}

/**
 * Repopulate form with user values.
 */
function _form_repopulate($form, $form_state) {
  #dsm($form);
  #dsm(element_children($form));
  #dsm($form_state);
  foreach (element_children($form) as $key) {
    if (isset($form[$key]['#repopulate'])) {
      #dsm('repop: ');
      #dsm($key);
      #$form[$key]['#default_value'] = 'repop!'; // this obviously works
      #$form[$key]['#default_value'] = $form_state['values'][$key]; // arg! here we only have values from page 2!
      $form[$key]['#default_value'] = $form_state['storage']['input'][$key]; // this obviously works
    }
    // recurse into children
    $form[$key] = _form_repopulate($form[$key], $form_state);
  } // each element_children
  return $form;
}


/**
 * Theme function for hook list
 */
// FIXME: this goes into an endless loop with theme()!
/*
function Xtheme_module_builder_hook_list($form) {
  $output = "<ul class=\"hook-group clear-block\">\n";
  foreach (element_children($form) as $key) {
    $output .= "  <li>". drupal_render($form[$key]) ."</li>\n";
  }
  $output .= "</ul>\n";
  return $output;
}
*/


/**
 * Form submit handler for page 1
 */
function module_builder_page_input_submit($form, &$form_state) {
  // Trigger multistep.
  $form_state['rebuild'] = TRUE;
  // Set the next step.
  $form_state['step'] = 'generate';

  // stash input.... these values will follow us around everywhere like a little dog...
  $form_state['storage']['input'] = $form_state['values'];
  foreach (array('generate', 'generate_module', 'form_build_id', 'form_token', 'form_id') as $key) {
    unset($form_state['storage']['input'][$key]);
  }
}

/**
 * Form builder for page 2: generate code.
 */
function module_builder_page_generate($form, &$form_state) {
  $mb_factory = module_builder_get_factory('ModuleBuilderEnvironmentDrupalUI');

  // Get our task handler. The form builder will already have checked sanity.
  $mb_task_handler_generate = $mb_factory->getTask('Generate', 'module');

  // Get the component data info.
  $root_generator = $mb_task_handler_generate->getRootGenerator();
  $component_data_info = $root_generator->getComponentDataInfo();

  // No need to call prepareComponentDataProperty() here.

  // Build module data
  $module_data = module_data_from_form($form, $form_state['values'], $component_data_info);

  // Presets get expanded with JS.
  unset($module_data['module_hook_presets']);

  // Perform any final processing on the component data.
  // This prepares data, for example expands options such as hook presets.
  $root_generator->processComponentData($component_data_info, $module_data);

  // Get the files.
  $files = $mb_task_handler_generate->generateComponent($module_data);
  //dsm($files);

  /*
  $form['back'] = array(
    '#type' => 'submit',
    '#value' => t('Back'),
    '#name' => 'back',
  );
  */

  foreach ($files as $filename => $code) {
    $form['code_instructions_' . $filename] = array(
      '#markup' => t('Please copy and paste the following text into a file called !module.', array('!module' => $filename)),
      '#prefix' => '<div class="module-message">',
      '#suffix' => '</div>',
    );
    $form['module_code_'  . $filename] = array(
      '#type' => 'textarea',
      '#title' => t($filename .' code'),
      '#rows' => count(explode("\n", $code)),
      '#default_value' => $code,
      '#prefix' => '<div class="module-code">',
      '#suffix' => '</div>',
    );
  }

  /*
  $form['code_instructions'] = array(
    '#value' => t('Please copy and paste the following text into a file called !module.', array('!module' => $form_state['values']['module_root_name'] .'.module')),
    '#prefix' => '<div id="module-message">',
    '#suffix' => '</div>',
  );
  $form['module_code'] = array(
    '#type' => 'textarea',
    '#title' => t('Module code'),
    '#rows' => 20,
    '#default_value' => $code,
    '#prefix' => '<div id="module-code">',
    '#suffix' => '</div>',
  );
  */
  /*
  $form['download_module'] = array(
    '#type' => 'submit',
    '#value' => t('Download module'),
  );
  $form['write_module'] = array(
    '#type' => 'button',
    '#value' => t('Write module file'),
  );
  */

  /*
  $form['download_info'] = array(
    '#type' => 'submit',
    '#name' => 'op',
    '#value' => t('Download info file'),
  );
  $form['write_info'] = array(
    '#type' => 'button',
    '#value' => t('Write info file'),
  );
  */

  // handle the write buttons
 ## $form['#after_build'] = array('module_builder_write_buttons');

  return $form;
}

/**
 * Helper function: creates an array of all the data needed to build the module
 * from form values, suitable for passing to Generate::generateComponent().
 */
function module_data_from_form($form, $form_values, $component_data_info) {
  // Most things come in as we want them from the form.
  $module_data = $form_values;

  // Hooks need flattening.
  $module_data['hooks'] = array();
  foreach ($form_values['hooks'] as $hook_group) {
    $module_data['hooks'] += array_keys(array_filter($hook_group));
  }

  // Build list.
  // The UI always gets the full code.
  $module_data['requested_build'] = array('code' => TRUE, 'info' => TRUE);

  // Generic processing.
  foreach ($component_data_info as $property_name => $property_info) {
    // Skip hooks, they got handled specially.
    if ($property_name == 'hooks') {
      continue;
    }

    if ($property_info['format'] == 'array') {
      if (isset($property_info['options'])) {
        // Anything with options needs to have the crud filtered out.
        $module_data[$property_name] = array_filter($module_data[$property_name]);
      }
      else {
        // Textareas need splitting on newlines into arrays.
        $module_data[$property_name] = preg_split("@\s*\n\s*@", $module_data[$property_name], -1, PREG_SPLIT_NO_EMPTY);
      }
    }
  }

  return $module_data;
}
