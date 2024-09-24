<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the DependencyInjection analysis class.
 */
class UnitDependencyInjectionAnalysisTest extends TestCase {

  /**
   * Tests the method analysis.
   *
   * @dataProvider dataDependencyInjection
   *
   * @param object $class_object
   *   An instance of an anonymous class to analyse. This is an object because
   *   passing the class name of anonymous class is fiddly.
   * @param array $result
   *   The expected result.
   */
  public function testDependencyInjection($class_object, $result) {
    $parent_construction_parameters = \DrupalCodeBuilder\Utility\CodeAnalysis\DependencyInjection::getInjectedParameters($class_object::class, 0);

    $this->assertEquals($result, $parent_construction_parameters);
  }

  /**
   * Data provider for testDependencyInjection().
   */
  public static function dataDependencyInjection() {
    return [
      'nothing' => [
        new class {},
        [],
      ],
      'single_service' => [
        // We use anonymous classes so that the code to be analysed can be
        // inline, but this means the anonymous class must be instantiated
        // because anonymous classes aren't first-class citizens. In particular,
        // that means we need to pass in dummy arguments to the constructor.
        new class(new MyService()) {

          public function __construct(MyService $my_service) {
            // The DependencyInjection class doesn't inspect the code in the
            // constructor so we don't need anything here.
          }

          public static function create($container) {
            return new static(
              $container->get('service')
            );
          }

        },
        [
          [
            'type' => MyService::class,
            'name' => 'my_service',
            'extraction' => "\$container->get('service')",
          ],
        ],
      ],
      'single_service_trailing_comma' => [
        new class(new MyService()) {

          public function __construct(MyService $my_service) {}

          public static function create($container) {
            return new static(
              $container->get('service'),
            );
          }

        },
        [
          [
            'type' => MyService::class,
            'name' => 'my_service',
            'extraction' => "\$container->get('service')",
          ],
        ],
      ],
      'complex_extraction' => [
        new class(new MyExtractedObject()) {

          public function __construct(MyExtractedObject $my_extracted) {}

          public static function create($container) {
            return new static(
              $container->get('service')->extract('thing')
            );
          }

        },
        [
          [
            'type' => MyExtractedObject::class,
            'name' => 'my_extracted',
            'extraction' => "\$container->get('service')->extract('thing')",
          ],
        ],

      ],
    ];
  }

}

// Dummy classes for parameter types.
class MyService {}
class MyExtractedObject {}
