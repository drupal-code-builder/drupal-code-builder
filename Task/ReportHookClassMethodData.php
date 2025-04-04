<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Definition\OptionDefinition;
use MutableTypedData\Definition\OptionSetDefininitionInterface;
use DrupalCodeBuilder\Task\Report\SectionReportInterface;

/**
 * Reports data on hooks that can be implemented as class methods.
 */
class ReportHookClassMethodData extends ReportHookData implements OptionSetDefininitionInterface {

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    $options = [];

    $data = $this->listHookData();
    foreach ($data as $group => $hooks) {
      foreach ($hooks as $key => $hook) {
        // Skip an obligate procedural hook.
        if (!empty($hook['procedural'])) {
          continue;
        }

        if ($hook['core'] && isset($hook['original_file_path'])) {
          $url = 'https://api.drupal.org/api/drupal/' .
            str_replace('/', '!', $hook['original_file_path']) .
            '/function/' .
            $hook['name'] .
            '/' . $this->environment->getCoreMajorVersion();
        }

        $options[$hook['name']] = OptionDefinition::create(
          $hook['name'],
          $hook['name'],
          description: $hook['description'] ?? '',
          api_url: $url ?? NULL,
        );
      }
    }

    return $options;
  }

}
