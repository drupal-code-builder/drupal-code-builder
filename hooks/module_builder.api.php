<?php

/**
 * @file
 * These are the hooks that are invoked by the Module builder.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Provide information about hook definition files to Module builder.
 * This hook should go in a MODULE.module_builder.inc file in your module folder.
 * Is it only loaded by Module builder when the user goes to download new hook data.
 *
 * @param $version
 * The major version of Drupal to return data for.
 *
 * @return
 *   An array of data, keyed by module name.
 *   The subsequent array should specify:
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
 *      Use the token %module for the module machine name.
 *      Usually this will be '%module.module' but for instance,
 *      'install.php' has hooks that should go in '%module.install'.
 */
function hook_module_builder_info($version) {
  $data = array(
    // Hooks on behalf of Drupal core.
    'system' => array(
      'url' => 'http://cvs.drupal.org/viewvc.py/drupal/contributions/docs/developer/hooks/%file?view=co&pathrev=%branch',
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
      'url' => 'http://cvs.drupal.org/viewvc.py/drupal/contributions/modules/module_builder/hooks/%file?view=co&pathrev=%branch',
      'branch' => 'DRUPAL-6--2',
      'group' => 'module builder',      
      'hook_files' => array(
        'module_builder.php' => '%module.module_builder.inc',
      ),
    ),
  );
  
  return $data;
}
