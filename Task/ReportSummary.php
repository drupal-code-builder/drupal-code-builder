<?php

namespace DrupalCodeBuilder\Task;

/**
 * Task handler for reporting a summary of all stored analysis data.
 *
 * TODO: change the base class; this only extends from ReportHookDataFolder to
 * get the lastUpdatedDate() method.
 */
class ReportSummary extends ReportHookDataFolder {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * Returns a listing of all stored data, with counts.
   *
   * @return
   *   An array whose keys are short identifiers for the types of data, where
   *   each value is itself an array with:
   *   - 'label': A title case label for the type of data, e.g. 'Hooks'.
   *   - 'count': The number of stored definitions of this type.
   *   - 'list': A list of all the items. This is in the same format as Drupal
   *     FormAPI options, i.e. either:
   *     - an array of machine keys and label values.
   *     - a nested array where keys are group labels, and values are keys and
   *       labels as in the non-nested format.
   */
  public function listStoredData() {
    $return = [];

    // Hooks.
    $task_report_hooks = \DrupalCodeBuilder\Factory::getTask('ReportHookData');
    $data = $task_report_hooks->listHookData();

    $item = [
      'label' => 'Hooks',
    ];
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
    $item['list'] = $list;
    $item['count'] = $count;

    $return['hooks'] = $item;

    // Services
    $task_report_service_data = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
    $data = $task_report_service_data->listServiceNamesOptionsAll();

    $return['services'] = [
      'label' => 'Services',
      'list' => $data,
      'count' => count($data),
    ];

    // Tagged service types.
    $data = $task_report_service_data->listServiceTypeData();
    $list = [];
    foreach ($data as $tag => $item) {
      $list[$tag] = $item['label'];
    }

    $return['tags'] = [
      'label' => 'Service tag types',
      'list' => $list,
      'count' => count($data),
    ];

    // Plugin types.
    $task = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');
    $data = $task->listPluginNamesOptions();

    $return['plugins'] = [
      'label' => 'Plugin types',
      'list' => $data,
      'count' => count($data),
    ];

    // Field types.
    $task = \DrupalCodeBuilder\Factory::getTask('ReportFieldTypes');
    $data = $task->listFieldTypesOptions();

    $list = [];
    foreach ($data as $key => $label) {
      $list[$key] = $label;
    }

    $return['fields'] = [
      'label' => 'Field types',
      'list' => $task->listFieldTypesOptions(),
      'count' => count($data),
    ];

    // Admin routes.
    $task = \DrupalCodeBuilder\Factory::getTask('ReportAdminRoutes');
    $data = $task->listAdminRoutesOptions();

    $return['admin_routes'] = [
      'label' => 'Admin routes',
      'list' => $data,
      'count' => count($data),
    ];

    return $return;
  }

}
