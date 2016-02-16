<?php

/**
 * @file
 * Contains ModuleBuilder\BaseEnvironment\VersionHelper7.
 */

namespace ModuleBuilder\Environment;

/**
 * Environment helper for Drupal 7.
 *
 * @ingroup module_builder_environment_version_helpers
 */
class VersionHelper7 extends VersionHelper8 {

  protected $major_version = 7;

  /**
   * A version-independent wrapper for drupal_system_listing().
   */
  function systemListing($mask, $directory, $key = 'name', $min_depth = 1) {
    return drupal_system_listing($mask, $directory, $key, $min_depth);
  }


  /**
   * Invoke hook_module_builder_info().
   */
  function invokeInfoHook() {
    $major_version = $this->major_version;

    // TODO: just get ours if no bootstrap?
    $mask = '/\.module_builder.inc$/';
    $mb_files = drupal_system_listing($mask, 'modules');
    //print_r($mb_files);

    $module_data = array();

    foreach ($mb_files as $file) {
      // Our system listing wrapper ensured that there is a uri property on all versions.
      include_once($file->uri);
      // Use a property of the (badly-documented!) $file object that is common to both D6 and D7.
      $module = str_replace('.module_builder', '', $file->name);
      // Note that bad data got back from the hook breaks things.
      if ($result = module_invoke($module, 'module_builder_info', $major_version)) {
        $module_data = array_merge($module_data, $result);
      }
    }

    return $module_data;
  }

  /**
   * Get a user preference setting.
   *
   * On Drupal 7 and below, this is a wrapper around variable_get().
   */
  public function getSetting($name, $default = NULL) {
    $setting_name_lookup = array(
      'data_directory'  => 'module_builder_hooks_directory',
      'detail_level'    => 'module_builder_detail',
      'footer'          => 'module_builder_footer',
    );

    if (isset($setting_name_lookup[$name])) {
      return variable_get($setting_name_lookup[$name], $default);
    }
    else {
      return $default;
    }

  }

}
