<?php

/**
 * @file
 * Contains ModuleBuilder\Task\ReportHookPresets.
 */

namespace ModuleBuilder\Task;

/**
 * Task handler for reporting on hook presets and templates.
 */
class ReportHookPresets extends Base {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * Returns the contents of a template file.
   *
   * TODO: this will eventually return either the user version or the module version.
   * TODO: IS THIS IN THE RIGHT FILE???
   *
   * @param $filename
   *  The filename of the template file to read.
   *
   * @return
   *  The contents of the file, or NULL if the file is not found.
   *
   * @throws \Exception
   *  Throws an exception if the file can't be found.
   */
  protected function loadPresetsFile($filename) {
    $pieces = array('templates', $this->environment->getCoreMajorVersion(), $filename);
    $path = $this->environment->getPath(implode('/', $pieces));

    if (!file_exists($path)) {
      throw new \Exception(strtr("Unable to find template at !path.", array(
        '!path' => htmlspecialchars($path, ENT_QUOTES, 'UTF-8'),
      )));
    }

    $template_file = file_get_contents($path);
    return $template_file;
  }

  /**
   * Get the definition of hook presets.
   *
   * A preset is a collection of hooks with a machine name and a descriptive
   * label. Presets allow quick selection of hooks that are commonly used
   * together, eg those used to define a node type, or blocks.
   *
   * @return
   *  An array keyed by preset name, whose values are arrays of the form:
   *    'label': The label shown to the user.
   *    'hooks': A flat array of full hook names, eg 'hook_menu'.
   */
  function getHookPresets() {
    // TODO: read user file preferentially.
    $presets_template = $this->loadPresetsFile('hook_groups.template');
    $hook_presets = json_decode(preg_replace("@//.*@", '', $presets_template), TRUE);
    if (is_null($hook_presets)) {
      // @TODO: do something here to say its gone wrong. Throw Exception?
      drupal_set_message(t('Problem reading json file.'), 'error');
    }
    return $hook_presets;
  }

}