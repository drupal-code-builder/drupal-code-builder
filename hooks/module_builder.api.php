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
 * @param $version
 * The major version of Drupal to return data for.
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
