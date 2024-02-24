<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\ReportHookData.
 */

namespace DrupalCodeBuilder\Task;

use MutableTypedData\Definition\OptionDefinition;
use DrupalCodeBuilder\Definition\OptionsProviderInterface;
use DrupalCodeBuilder\Task\Report\SectionReportInterface;

/**
 * Task handler for reporting on hook data.
 *
 * TODO: revisit some of these and clean up names / clean up how many we have.
 */
class ReportHookData extends ReportHookDataFolder implements OptionsProviderInterface, SectionReportInterface {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

   /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      'key' => 'hooks',
      'label' => 'Hooks',
      'weight' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSummary(): array {
    $data = $this->listHookData();

    $list = [];
    $count = 0;
    foreach ($data as $file => $hooks) {
      $list_group = [];
      foreach ($hooks as $key => $hook) {
        $list_group[$hook['name']] = $hook['description'];
      }

      $count += count($hooks);
      $list["Group $file:"] = $list_group;
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getCount(): int {
    $count = 0;
    foreach ($this->getDataSummary() as $group) {
        $count+= count($group);
    }
    return $count;
  }

  /**
   * The cached hook data.
   *
   * @var array
   */
  protected $hook_data;

  /**
   * Get the list of hook data.
   *
   * @return
   *  The processed hook data.
   */
  function listHookData() {
    // We may come here several times, so cache this.
    if (!empty($this->hook_data)) {
      return $this->hook_data;
    }

    $this->hook_data = $this->environment->getStorage()->retrieve('hooks');
    return $this->hook_data;
  }

  /**
   * Get just hook names.
   *
   * @param $style
   *   Whether to return hook names as just 'init' or 'hook_init'. One of:
   *    - 'short': Return short names, i.e., 'init'.
   *    - 'full': Return full hook names, i.e., 'hook_init'.
   *
   * @return
   *   A flat array of strings.
   */
  function listHookNames($style = 'full') {
    $data = $this->getHookDeclarations();
    $names = array_keys($data);

    if ($style == 'short') {
      foreach ($names as $key => $hook_name) {
        $names[$key] = str_replace('hook_', '', $hook_name);
      }
    }

    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    $options = [];

    $data = $this->listHookData();
    foreach ($data as $group => $hooks) {
      foreach ($hooks as $key => $hook) {
        $options[$hook['name']] = OptionDefinition::create(
          $hook['name'],
          $hook['name'],
          $hook['description'] ?? ''
        );
      }
    }

    return $options;
  }

  /**
   * Get hooks as a list of options.
   *
   * @deprecated Use getOptions() instead.
   *
   * @return
   *   An array of hooks as options suitable for FormAPI, where each key is a
   *   full hook name, and each value is a description.
   */
  function listHookNamesOptions() {
    $data = $this->getHookDeclarations();

    $return = [];
    foreach ($data as $hook_name => $hook_info) {
      $return[$hook_name] = $hook_info['description'];
    }

    return $return;
  }

  /**
   * Get hooks as a grouped list with data about each item.
   *
   * @return
   *   An array keyed by hook group, whose items are in turn arrays keyed by
   *   hook name standardized to lowercase, and whose items in turn are arrays
   *   with the following properties:
   *    - 'name': The hook name in the original case.
   *    - 'type' One of 'hook' or 'callback'.
   *    - 'description' The first line from the hook definition's docblock.
   */
  public function listHookOptionsStructured() {
    $data = $this->getHookDeclarations();

    $return = [];
    foreach ($data as $hook_name => $hook_info) {
      $return[$hook_info['group']][$hook_name] = [
        'name' => $hook_info['name'],
        'description' => $hook_info['description'],
        'type' => $hook_info['type'],
        'core' => $hook_info['core'],
      ];
    }

    return $return;
  }

  /**
   * Get stored hook declarations, keyed by hook name, with destination.
   *
   * @return
   *  An array of hook information, keyed by the full name of the hook
   *  standardized to lower case.
   *  Each item has the keys:
   *  - 'type': One of 'hook' or 'callback'.
   *  - 'name': The full name of the hook in the original case,
   *    eg 'hook_form_FORM_ID_alter'.
   *  - 'definition': The full function declaration.
   *  - 'description': The first line of the hook docblock.
   *  - 'destination': The file this hook should be placed in, as a module file
   *    pattern such as '%module.module'.
   *  - 'dependencies': An array of other hooks and callbacks which should also
   *    be generated if this hook is generated.
   *  - 'group': Erm write this later.
   *  - 'file_path': The absolute path of the file this definition was taken
   *    from.
   *  - 'body': The hook function body, taken from the API file.
   */
  function getHookDeclarations() {
    $data = $this->listHookData();

    $return = [];
    foreach ($data as $group => $hooks) {
      foreach ($hooks as $key => $hook) {
        // Standardize to lowercase.
        $hook_name = strtolower($hook['name']);

        $return[$hook_name] = $hook;
      }
    }

    return $return;
  }

}
