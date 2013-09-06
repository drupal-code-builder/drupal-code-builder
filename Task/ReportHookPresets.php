<?php

/**
 * @file
 * Definition of ModuleBuider\Task\ReportHookPresets.
 */

namespace ModuleBuider\Task;

/**
 * Task handler for reporting on hook presets and templates.
 *
 * (Replaces parts of process.inc.)
 */
class ReportHookPresets extends Base {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'hook_data';

  /**
   * Returns the contents of a template file.
   *
   * TODO: this will eventually return either the user version or the module version.
   * TODO: IS THIS IN THE RIGHT FILE???
   *
   * (Replaces module_builder_get_template().)
   *
   * @param $filename
   *  The filename of the template file to read.
   *
   * @return
   *  The contents of the file, or NULL if the file is not found.
   */
  function getTemplate($filename) {
    $pieces = array('templates', $this->environment->major_version, $filename);
    $path = $this->environment->getPath(implode('/', $pieces));

    if (file_exists($path)) {
      $template_file = file_get_contents($path);
      return $template_file;
    }
    else {
      return NULL;
    }
  }

  /**
   * Get the definition of hook presets.
   *
   * A preset is a collection of hooks with a machine name and a descriptive
   * label. Presets allow quick selection of hooks that are commonly used
   * together, eg those used to define a node type, or blocks.
   *
   * (Replaces module_builder_get_hook_presets().)
   *
   * @return
   *  An array keyed by preset name, whose values are arrays of the form:
   *    'label': The label shown to the user.
   *    'hooks': A flat array of full hook names, eg 'hook_menu'.
   */
  function getHookPresets() {
    // TODO: read user file preferentially.
    $presets_template = $this->getTemplate('hook_groups.template');
    $hook_presets = json_decode(preg_replace("@//.*@", '', $presets_template), TRUE);
    if (is_null($hook_presets)) {
      // @TODO: do something here to say its gone wrong. Throw Exception?
      drupal_set_message(t('Problem reading json file.'), 'error');
    }
    return $hook_presets;
  }

}