<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Task\Collect\MethodCollector;
use DrupalCodeBuilder\Test\Fixtures\MethodCollectorInterface;

/**
 * Unit tests for the MethodCollector class.
 */
class UnitMethodCollectorTest extends TestCase {

  /**
   * Tests the method analysis.
   *
   * @dataProvider providerMethodCollector
   */
  public function testMethodCollector($method_name, $declaration_result) {
    $method_collector = new MethodCollector();

    $class_reflection = new \ReflectionClass(MethodCollectorInterface::class);
    $method_reflection = $class_reflection->getMethod($method_name);
    $method_analysis_data = $method_collector->getMethodData($method_reflection);

    $this->assertEquals($method_name, $method_analysis_data['name']);
    $this->assertEquals($declaration_result, $method_analysis_data['declaration']);
  }

  /**
   * Data provider for testMethodCollector().
   */
  public function providerMethodCollector() {
    return [
      'no params' => [
        // Method name.
        'noParams',
        // Expected declaration in analysis data.
        'public function noParams();',
      ],
      'scalar params' => [
        'scalarParams',
        'public function scalarParams($one, $two);',
      ],
      'qualified root namespace params' => [
        'qualifiedRootNamespaceParams',
        'public function qualifiedRootNamespaceParams(\DateTime $one, \Reflection $two);'
      ],
      'imported root namespace params' => [
        'importedRootNamespaceParams',
        'public function importedRootNamespaceParams(\Iterator $one, \Exception $two);'
      ],
      'namespace params' => [
        'namespaceParams',
        'public function namespaceParams(\DrupalCodeBuilder\Test\Fixtures\Typehint\Alpha $one, \DrupalCodeBuilder\Test\Fixtures\Typehint\Beta $two);',
      ],
    ];
  }

}
