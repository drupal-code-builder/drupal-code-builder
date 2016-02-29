<?php

/**
 * @file
 * Contains Basic7Test.
 */

// Can't be bothered to figure out autoloading for tests.
require_once __DIR__ . '/DrupalCodeBuilderTestBase.php';

/**
 * Basic test class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit  tests/Basic7Test.php
 * @endcode
 */
class Basic7Test extends DrupalCodeBuilderTestBase {

  /**
   * Test the hook data is reported correctly.
   */
  public function testReportHookData() {
    $this->setupDrupalCodeBuilder(7);

    $hooks_directory = \DrupalCodeBuilder\Factory::getEnvironment()->getHooksDirectory();

    $mb_task_handler_report = \DrupalCodeBuilder\Factory::getTask('ReportHookData');
    $this->assertTrue(is_object($mb_task_handler_report), "A task handler object was returned.");

    $hook_groups = $mb_task_handler_report->listHookData();
    $this->assertTrue(is_array($hook_groups) || !empty($hook_groups), "An non-empty array of hook data was returned.");
  }

}
