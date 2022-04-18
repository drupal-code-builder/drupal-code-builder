<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Generator\PHPFile as RealPHPFile;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

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
    $data_item = $this->prophesize(\MutableTypedData\Data\DataItem::class);
    $php_file_generator = new PHPFile($data_item->reveal());

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

  /**
   * Tests the ordering of class imports.
   */
  public function testClassImportsOrder() {
    // We want to get a bunch of fully-qualified class names into a generated
    // PHP file. The simplest way to do this (probably!) is to mock a hook
    // definition with the classes we want in the hook's sample code. When we
    // request this hook, its sample code is used in the generated PHP file,
    // and the fully-qualified class names will be extracted and sorted.
    $report_hook_data = $this->prophesize(get_class($this->container->get('ReportHookData')));
    $report_hook_data->getSanityLevel()->willReturn('none');
    $report_hook_data->getHookDeclarations()->willReturn([
      'hook_cake' => [
        'type' => 'hook',
        'name' => 'hook_cake',
        'definition' => 'function hook_cake()',
        'description' => 'Makes cake',
        'destination' => '%module.module',
        'body' => <<<'EOT'
          // First line gets trimmed.
          // Class names, intentionally put in the wrong order here so it's not
          // just accidental that they end up in the expected order.
          // Vendor classes.
          $eggs = new \Beta\Eggs;
          $flour = new \Alpha\Flour;
          $pistachios = new \Zeta\Pistachios;
          $marzipan = new \Mu\Marzipan;
          // Module classes.
          $ground_almonds = new \Drupal\beta_module\Almonds;
          $sugar = new \Drupal\alpha_module\Sugar;
          // Drupal core and component classes.
          $cinammon = new \Drupal\Core\Spices\Cinammon;
          $chocolate = new \Drupal\Core\Chocolate\Dark;
          $apricots = new \Drupal\Component\Fruit\DriedApricots;
          $yeast = new \Drupal\Component\Baking\Yeast;
          // Last line gets trimmed.
        EOT,
      ],
    ]);
    $report_hook_data->listHookNamesOptions()->willReturn([
      'cake' => 'hook_cake()',
    ]);

    $this->container->set('ReportHookData', $report_hook_data->reveal());

    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => [
        'hook_cake',
      ],
    ];
    $files = $this->generateModuleFiles($module_data);

    $module_file = $files['test_module.module'];

    $php_tester = new PHPTester($this->drupalMajorVersion, $module_file);
    $php_tester->assertImportsSorted();
  }

}

/**
 * Non-abstract version of PHPFile generator.
 */
class PHPFile extends RealPHPFile {

  function code_body() {
    return [];
  }

}
