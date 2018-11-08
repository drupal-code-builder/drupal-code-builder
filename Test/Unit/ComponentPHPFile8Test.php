<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Generator\PHPFile;

/**
 * Tests the PHP File generator class.
 */
class ComponentPHPFile8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test the qualified class name extraction.
   *
   * @dataProvider providerQualifiedClassNameExtraction
   *
   * @param string $code
   *   The code to extract class names from.
   * @param string $expected_changed_code
   *   The original code, with the expected alteration. NULL if no extraction
   *   is expected.
   * @param string|string[]|null $expected_qualified_class_name
   *   One of:
   *   - The single expected qualified class name that will be extracted
   *     from the code,
   *   - An array of all the qualified class names that are expected to be
   *     extracted.
   *   - NULL if we do not expect extraction.
   */
  public function testQualifiedClassNameExtraction($code, $expected_changed_code, $expected_qualified_class_names) {
    // Make the protected method we're testing callable.
    $method = new \ReflectionMethod(PHPFile::class, 'extractFullyQualifiedClasses');
    $method->setAccessible(TRUE);

    // Create a PHP file generator with some dummy constructor parameters.
    $php_file_generator = new PHPFile('name', [], 'fake_generator');

    // Our code is a single line, but the method expects an array of lines.
    $code_lines = [$code];

    $imported_classes = [];

    $method->invokeArgs($php_file_generator, [&$code_lines, &$imported_classes, 'Current\Namespace']);

    if (is_null($expected_qualified_class_names)) {
      $this->assertEmpty($imported_classes, "No class name was extracted.");
    }
    else {
      if (!is_array($expected_qualified_class_names)) {
        $expected_qualified_class_names = [$expected_qualified_class_names];
      }
      $this->assertEquals($expected_qualified_class_names, $imported_classes, "The qualified class name was extracted.");

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
      // Both forms of this exist in core. No idea which one is correct.
      // See https://www.drupal.org/project/coding_standards/issues/2948521
      'PHPStorm type var first' => [
        // E.g. seen in hook_entity_type_build().
        ' /** @var $entity_types \Drupal\Core\Entity\EntityTypeInterface[] */',
        NULL,
        NULL,
      ],
      'PHPStorm type class first' => [
        // E.g. seen in hook_entity_type_build().
        ' /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */',
        NULL,
        NULL,
      ],
      'parameter typehint' => [
        'function myfunc(\Foo\Bar $param_1, \Bar\Bax\Biz $param_2, \BuiltIn $param_3) {',
        'function myfunc(Bar $param_1, Biz $param_2, \BuiltIn $param_3) {',
        [
          'Foo\Bar',
          'Bar\Bax\Biz',
        ]
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
      'repeated' => [
        '$foo = new \Foo\Bar();
          $bar = new \Foo\Bar();',
        '$foo = new Bar();
          $bar = new Bar();',
        'Foo\Bar',
      ],
      'current' => [
        '$foo = new \Current\Namespace\Bar()',
        '$foo = new Bar()',
        NULL,
      ],
      'docblock param' => [
        ' * @param \Foo\Bar $param',
        NULL,
        NULL,
      ],
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
    ];
  }

}
