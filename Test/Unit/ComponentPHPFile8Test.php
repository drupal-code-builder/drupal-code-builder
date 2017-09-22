<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Generator\PHPFile;

/**
 * Tests the PHP File generator class.
 */
class ComponentPHPFile8Test extends TestBase {

  protected function setUp() {
    $this->setupDrupalCodeBuilder(8);
  }

  /**
   * Test the qualified class name extraction.
   *
   * @dataProvider providerQualifiedClassNameExtraction
   *
   * @param $code
   *   The code to extract class names from.
   * @param $expected_changed_code
   *   The original code, with the expected alteration.
   * @param $expected_qualified_class_name
   *   The expected qualified class name that will be extracted from the code.
   */
  public function testQualifiedClassNameExtraction($code, $expected_changed_code, $expected_qualified_class_name) {
    // Make the protected method we're testing callable.
    $method = new \ReflectionMethod(PHPFile::class, 'extractFullyQualifiedClasses');
    $method->setAccessible(TRUE);

    // Create a PHP file generator with some dummy constructor parameters.
    $php_file_generator = new PHPFile('name', [], 'fake_generator');

    // Our code is a single line, but the method expects an array of lines.
    $code_lines = [$code];

    $imported_classes = [];

    $method->invokeArgs($php_file_generator, [&$code_lines, &$imported_classes]);

    if (is_null($expected_qualified_class_name)) {
      $this->assertEmpty($imported_classes, "No class name was extracted.");
    }
    else {
      $this->assertContains($expected_qualified_class_name, $imported_classes, "The qualified class name was extracted.");

      $changed_code = array_pop($code_lines);
      $this->assertEquals($expected_changed_code, $changed_code, "The code was changed to use the short class name.");
    }
  }

  /**
   * Data provider for testQualifiedClassNameExtraction().
   */
  public function providerQualifiedClassNameExtraction() {
    return [
      'nothing' => [
        '$foo = 1 + 2;',
        NULL,
        NULL,
      ],
      'parameter typehint' => [
        'function myfunc(\Foo\Bar $param) {',
        'function myfunc(Bar $param) {',
        'Foo\Bar',
      ],
      'static call' => [
        '$foo = \Foo\Bar::myfunc();',
        '$foo = Bar::myfunc();',
        'Foo\Bar',
      ],
      'class' => [
        '\Foo\Bar::class;',
        'Bar::class;',
        'Foo\Bar',
      ],
      'new' => [
        '$foo = new \Foo\Bar()',
        '$foo = new Bar()',
        'Foo\Bar',
      ],
      'docblock param' => [
        ' * @param \Foo\Bar $param',
        NULL,
        NULL,
      ],
      /*
      // These currently fail!
      'comment' => [
        '// We call \Foo\Bar::myfunc() at this point.',
        NULL,
        NULL,
      ],
      'quoted class name' => [
        "\$class = '\Foo\Bar'",
        NULL,
        NULL,
      ],
      'double quoted class name' => [
        '$class = "\Foo\Bar"',
        NULL,
        NULL,
      ],
      */
    ];
  }

}
