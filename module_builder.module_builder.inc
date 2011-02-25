<?php

/**
 * Implementation of hook_module_builder_info().
 *
 * Provide information about hook definition files to Module builder.
 *
 * On D6, define where hook definition files may be downloaded from and specify
 * the destination for their hooks. Only files defined here will be processed.
 *
 * On D7, specify the destination files for hooks that do not go in the default
 * %module.module file. All module.api.php files will be processed; this hook
 * merely provides extra information.
 *
 * This hook should go in a MODULE.module_builder.inc file in your module folder.
 * Is it only loaded by Module builder when the user goes to get new hook data.
 *
 * This hook serves a fairly different purpose on D7 compared to prior versions.
 * The same hook name is kept in case some awkward contrib modules continue to keep their hook definitions on a
 * remote server. There's a reason, honest!
 * The keys are different so here we spludge both together so this code
 * can run on any version with Drush.
 * Other modules implementing this shouldn't do this, as Module Builder invokes
 * and builds for the version of the current site.
 *
 * @return
 *   An array of data, keyed by module name.
 *   On D6, the subsequent array should specify:
 *    - url: a general url to fetch files from.
 *      Use tokens to insert filenames and branch: %file, %branch 
 *    - branch: the current branch of the module, eg DRUPAL-6--1, HEAD.
 *      TODO: find a neat way to grab this with a CVS id token?
 *    - group: the UI group these hooks should go in. This should probably be the
 *      name of your module, but you can use '#filenames' to specify that each
 *      of your files should form a group.
 *      Eg 'core.php' goes in the group 'core'.
 *    - hook_files: an array of files to fetch. The filename is the key
 *      and the value is the file where the hook code should eventually be stored.
 *      Usually this will be '%module.module' but for instance,
 *      'install.php' has hooks that should go in '%module.install'.
 *   On D7, the subsequent array should specify one or both of:
 *    - 'destination': the destination file for a hook's implementation, 
 *      eg '%module.module', '%module.views.inc'. This applies to all hooks in 
 *      the named file, unless:
 *    - 'hook_destinations': override destination for specific hooks here. This 
 *      is an array whose keys are destination strings, and values are themselves
 *      flat arrays of full hook names. Eg:
 *      '%module.install' => array(hook_install)
 */
function module_builder_module_builder_info() {
  // Versions 5 and 6.
  $data['common'] = array(
    // Hooks on behalf of Drupal core.
    'system' => array(
      'url' => 'http://drupalcode.org/viewvc/drupal/contributions/docs/developer/hooks/%file?view=co&pathrev=%branch',
      'branch' => 'DRUPAL-6--1',
      'group' => '#filenames',
      'hook_files' => array(
        // List of files we should slurp from the url for hook defs.
        // and the destination file for processed code.
        'core.php' =>    '%module.module',
        'node.php' =>    '%module.module',      
        'install.php' => '%module.install',      
      ),
    ),
    // We need to do our own stuff now we have a hook!
    'module_builder' => array(
      'url' => 'http://drupalcode.org/viewvc/drupal/contributions/modules/module_builder/hooks/%file?view=co&pathrev=%branch',
      'branch' => 'DRUPAL-6--2',
      'group' => 'module builder',      
      'hook_files' => array(
        'module_builder.php' => '%module.module_builder.inc',
      ),
    ),
    
    // Support for some contrib modules (the ones I use ;) -- for more please
    // file a patch either here or with the module in question.
    // Views
    'views' => array(
      'url' => 'http://drupalcode.org/viewvc/drupal/contributions/modules/views/docs/%file?view=co&pathrev=%branch',
      'branch' => 'HEAD',
      'group' => 'views',      
      'hook_files' => array(
        'docs.php' => '%module.module',
        // other files here: view.inc, views.default.inc
      ),
      // hooks that go in files other than %module.module
      'hook_destinations' => array(
        '%module.views.inc' => array(
          'hook_views_data',
          'hook_views_data_alter',
          'hook_views_admin_links_alter',
          'hook_views_handlers',
          'hook_views_plugins',
          'hook_views_preview_info_alter',
          'hook_views_query_alter',
        ),
        '%module.views_convert.inc' => array(
          'hook_views_convert',
        ),
        '%module.views_default.inc' => array(
          'hook_views_default_views',
        ),
      ),
    ),
    // Ubercart
    'ubercart' => array(
      'url' => 'http://drupalcode.org/viewvc/drupal/contributions/modules/ubercart/docs/%file?view=co&pathrev=%branch',
      'branch' => 'DRUPAL-6--2',
      'group' => 'ubercart',      
      'hook_files' => array(
        'hooks.php' => '%module.module',
      ),
    ),
    // Signup
    'signup' => array(
      'url' => 'http://drupalcode.org/viewvc/drupal/contributions/modules/signup/%file?view=co&pathrev=%branch',
      'branch' => 'DRUPAL-6--1',
      'group' => 'signup',      
      'hook_files' => array(
        'signup.api.php' => '%module.module',
      ),
    ),
  );

  // For D7, keys should match the filename MODULE.api.php
  $data['7'] = array(
    // Hooks on behalf of Drupal core.
    'system' => array(
      'hook_destinations' => array(
        '%module.install' => array(
          'hook_requirements',
          'hook_schema',
          'hook_schema_alter',
          'hook_install',
          'hook_update_N',
          'hook_update_last_removed',
          'hook_uninstall',
          'hook_enable',
          'hook_disable',
        ),     
      ),
    ),
  );

  // Return the data for the current version.
  $version = _module_builder_drupal_major_version();
  if (isset($data[$version])) {
    return $data[$version];
  }
  else {
    return $data['common'];
  }
}
