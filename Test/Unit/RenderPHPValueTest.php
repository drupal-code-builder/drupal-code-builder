<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Generator\Render\PhpValue;

/**
 * Unit tests for the value renderer.
 */
class RenderPHPValueTest extends TestCase {

  /**
   * Tests inline rendering as a single string.
   */
  public function testInline(): void {
    $this->assertSame('42', PhpValue::create(42)->renderInline());
    $this->assertSame("'foo'", PhpValue::create('foo')->renderInline());
    $this->assertSame('FALSE', PhpValue::create(FALSE)->renderInline());
    $this->assertSame('TRUE', PhpValue::create(TRUE)->renderInline());
    $this->assertSame('NULL', PhpValue::create(NULL)->renderInline());

    $this->assertSame("[42]", PhpValue::create([42])->renderInline());
    $this->assertSame("['foo']", PhpValue::create(['foo'])->renderInline());
    $this->assertSame("['key' => 'value']", PhpValue::create(['key' => 'value'])->renderInline());
  }

  /**
   * Tests multiline rendering as an array of strings.
   */
  public function testMultiline(): void {
    $this->assertSame(['42'], PhpValue::create(42)->renderMultiline());
    $this->assertSame(["'foo'"], PhpValue::create('foo')->renderMultiline());
    $this->assertSame(['FALSE'], PhpValue::create(FALSE)->renderMultiline());
    $this->assertSame(['NULL'], PhpValue::create(NULL)->renderMultiline());

    $this->assertSame([
      '[',
      "  42,",
      ']',
    ], PhpValue::create([42])->renderMultiline());

    $this->assertSame([
      '[',
      "  'key' => 'value',",
      ']',
    ], PhpValue::create(['key' => 'value'])->renderMultiline());
  }

  public function testNestedMultiline() {
    $this->assertSame([
      '[',
      "  'outer' => [",
      "    'inner' => 'value',",
      '  ],',
      ']',
    ], PhpValue::create(['outer' => ['inner' => 'value']])->renderMultiline());
  }

}
