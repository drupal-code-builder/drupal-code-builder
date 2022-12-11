<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Generator\Render\DocBlock;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Unit tests for the DocBlock renderer.
 *
 * Note that this doesn't test class method docblocks as the DocBlock class
 * doesn't handle indentation within a class.
 */
class RenderDocBlockTest extends TestBase {

  protected $drupalMajorVersion = 9;

  /**
   * Tests basic formatting and wrapping.
   */
  public function testBasicDocBlock() {
    $docblock = DocBlock::class();
    $docblock[] = 'Short line.';
    $docblock[] = 'Longer line which as it is very long needs to be wrapped into a paragraph that will wrap over several lines.';

    $expected_text = <<<EOT
  /**
   * Short line.
   *
   * Longer line which as it is very long needs to be wrapped into a paragraph
   * that will wrap over several lines.
   */
  EOT;

    $rendered = $docblock->render();
    $this->assertEquals(explode("\n", $expected_text), $rendered);
  }

  public function testClassWithTags() {
    $docblock = DocBlock::class();
    $docblock[] = 'Short line.';
    $docblock[] = 'Longer line which as it is very long needs to be wrapped into a paragraph that will wrap over several lines.';

    $docblock->group('mymodule.');

    $rendered = $docblock->render();

    $code = $this->convertLinesToClassCode($rendered);

    $php_tester = new PHPTester($this->drupalMajorVersion, $code);
    $php_tester->assertDrupalCodingStandards();

    $this->assertStringContainsString(' * @group mymodule', $code);
  }

  /**
   * Tests a procedural function with @param tags.
   */
  public function testFunctionWithParams() {
    $docblock = DocBlock::function();
    $docblock[] = 'Short line.';
    $docblock[] = 'Longer line which as it is very long needs to be wrapped into a paragraph that will wrap over several lines.';
    $docblock->param('string', 'foo', 'The param description.');
    $docblock->param(NULL, 'bar', 'The param description which is very long needs to be wrapped into a paragraph that will wrap over several lines.');

    $rendered = $docblock->render();

    $code = $this->convertLinesToFunctionCode($rendered);

    $php_tester = new PHPTester($this->drupalMajorVersion, $code);
    $php_tester->assertDrupalCodingStandards([
      // Allows us to skip adding a @file tag.
      'Drupal.Commenting.FileComment.Missing',
      // Allows us to skip putting parameters in the dummy function declaration.
      'Drupal.Commenting.FunctionComment.ParamNameNoMatch',
    ]);

    $this->assertStringContainsString(' * @param string $foo', $code);
    // Coder doesn't seem to check indent on parameter descriptions, so do it
    // ourselves.
    $this->assertStringContainsString(' *   The param description.', $code);
  }

  /**
   * Tests a procedural function with @param and @return tags.
   */
  public function testFunctionWithParamsReturn() {
    $docblock = DocBlock::function();
    $docblock[] = 'Short line.';
    $docblock[] = 'Longer line which as it is very long needs to be wrapped into a paragraph that will wrap over several lines.';
    $docblock->param('string', 'foo', 'The param description.');
    $docblock->param(NULL, 'bar', 'The param description which is very long needs to be wrapped into a paragraph that will wrap over several lines.');

    $docblock->return('int', 'The return description which is very long needs to be wrapped into a paragraph that will wrap over several lines.');

    $rendered = $docblock->render();

    $code = $this->convertLinesToFunctionCode($rendered);

    $php_tester = new PHPTester($this->drupalMajorVersion, $code);
    $php_tester->assertDrupalCodingStandards([
      // Allows us to skip adding a @file tag.
      'Drupal.Commenting.FileComment.Missing',
      // Allows us to skip putting parameters in the dummy function declaration.
      'Drupal.Commenting.FunctionComment.ParamNameNoMatch',
    ]);

    $this->assertStringContainsString(' * @param string $foo', $code);
    // Coder doesn't seem to check indent on parameter descriptions, so do it
    // ourselves.
    $this->assertStringContainsString(' *   The param description.', $code);
  }


  public function testParamsReturn() {
    $docblock = DocBlock::class();
    $docblock[] = 'Short line.';
    $docblock[] = 'Longer line which as it is very long needs to be wrapped into a paragraph that will wrap over several lines.';
    $docblock->param('string', 'foo', 'The param description.');
    $docblock->param(NULL, 'bar', 'The param description which is very long needs to be wrapped into a paragraph that will wrap over several lines.');
    $docblock->return('int', 'The return description which is very long needs to be wrapped into a paragraph that will wrap over several lines.');

    $rendered = $docblock->render();

    $code = $this->convertLinesToClassCode($rendered);

    $this->assertStringContainsString(' * @return int', $code);
    $this->assertStringContainsString(' *   The return description which is very long', $code);
  }

  /**
   * Make docblock lines for a class look like PHP code.
   */
  protected function convertLinesToClassCode($lines): string {
    // Make this look like PHP so we can use PHP Code Sniffer.
    $lines = array_merge([
      '<?php',
      '',
      ],
      $lines, [
        'class FooClass {}',
    ]);
    $code = implode("\n", $lines) . "\n";
    return $code;
  }

  /**
   * Make docblock lines for a function look like PHP code.
   */
  protected function convertLinesToFunctionCode($lines): string {
    // Make this look like PHP so we can use PHP Code Sniffer.
    $lines = array_merge([
      '<?php',
      '',
      ],
      $lines,
      [
        'function foo() {',
        '}',
      ]
    );
    $code = implode("\n", $lines) . "\n";
    return $code;
  }

  /**
   * Make docblock lines for a method look like PHP code.
   */
  protected function convertLinesToMethodCode($lines): string {
    // Make this look like PHP so we can use PHP Code Sniffer.
    $lines = array_merge([
      '<?php',
      '',
      'class FooClass {',
      '',
    ],
    $lines,
    [
      '  public function foo() {',
      '  }',
      '',
      '}'
    ]);
    $code = implode("\n", $lines) . "\n";
    return $code;
  }


  protected function assertDrupalCodingStandardsForClass($docblock_lines) {
    // Make this look like PHP so we can use PHP Code Sniffer.
    $docblock_lines = array_merge([
      '<?php',
      '',
      ],
      $docblock_lines, [
        'class Foo {}',
    ]);
    $phptester_text = implode("\n", $docblock_lines) . "\n";

    $php_tester = new PHPTester($this->drupalMajorVersion, $phptester_text);
    $php_tester->assertDrupalCodingStandards();
  }

}
