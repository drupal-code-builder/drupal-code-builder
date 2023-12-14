<?php

/**
 * @file
 * Contains Basic7Test.
 */

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Basic test class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit Test/Basic7Test.php
 * @endcode
 */
class Basic7Test extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected $drupalMajorVersion = 7;

  /**
   * Test the component data is correctly formed.
   */
  public function testComponentData() {
    $component_data = $this->getRootComponentBlankData('module');

    $this->assertEquals('module', $component_data->getName());
  }

  /**
   * Test the hook data is reported correctly.
   */
  public function testReportHookData() {
    $hooks_directory = \DrupalCodeBuilder\Factory::getEnvironment()->getDataDirectory();

    $mb_task_handler_report = \DrupalCodeBuilder\Factory::getTask('ReportHookData');
    $this->assertTrue(is_object($mb_task_handler_report), "A task handler object was returned.");

    $hook_groups = $mb_task_handler_report->listHookData();
    $this->assertTrue(is_array($hook_groups) || !empty($hook_groups), "An non-empty array of hook data was returned.");
  }

}
