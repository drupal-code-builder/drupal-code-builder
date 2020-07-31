<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Exception\InvalidInputException;
use CaseConverter\CaseString;

/**
 * Provides common methods for plugin generators.
 *
 * Expects $this->discoveryType to be defined.
 *
 * TODO: remove.
 */
trait PluginTrait {

  /**
   * Provides the property definition for the plugin type property.
   *
   * @return array
   *   A property definition array.
   */
  protected static function getPluginTypePropertyDefinition() {
    return [
      'label' => 'Plugin type',
      'description' => "The identifier of the plugin type. This can be either the manager service name with the 'plugin.manager.' prefix removed, " .
        ' or the subdirectory name.',
      'required' => TRUE,
      'options' => function(&$property_info) {
        $mb_task_handler_report_plugins = \DrupalCodeBuilder\Factory::getTask('ReportPluginData');

        $options = $mb_task_handler_report_plugins->listPluginNamesOptions(static::$discoveryType);

        return $options;
      },
    ];
  }

}
