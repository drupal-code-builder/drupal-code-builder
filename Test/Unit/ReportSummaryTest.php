<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests ReportSummary task.
 *
 * Note that this is working with the test sample data, so doesn't need to
 * check the details of the data, just that it's properly returned by the task.
 */
class ReportSummaryTest extends TestBase {

  protected $drupalMajorVersion = 8;

  /**
   * Tests the report data.
   */
  public function testReportData() {
    $report = \DrupalCodeBuilder\Factory::getTask('ReportSummary');

    $data = $report->listStoredData();

    $this->assertArrayHasKey('hooks', $data);
    $this->assertArrayHasKey('plugins', $data);
    $this->assertArrayHasKey('admin_routes', $data);
    $this->assertArrayHasKey('services', $data);
    $this->assertArrayHasKey('service_tag_types', $data);
    $this->assertArrayHasKey('field_types', $data);
    $this->assertArrayHasKey('data_types', $data);

    foreach ($data as $key => $section_data) {
      $this->assertArrayHasKey('label', $section_data);
      $this->assertArrayHasKey('list', $section_data);
      $this->assertArrayHasKey('count', $section_data);
      $this->assertArrayHasKey('weight', $section_data);
    }

    $this->assertArrayHasKey('block', $data['plugins']['list']);
    $this->assertArrayHasKey('system.admin_config_system', $data['admin_routes']['list']);
    $this->assertArrayHasKey('entity_type.manager', $data['services']['list']);
    $this->assertArrayHasKey('breadcrumb_builder', $data['service_tag_types']['list']);
    $this->assertArrayHasKey('string', $data['field_types']['list']);
    $this->assertArrayHasKey('boolean', $data['data_types']['list']);
    // TODO: hooks.
  }

}
