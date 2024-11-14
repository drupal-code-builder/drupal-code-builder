<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Generator\Render\FluentMethodCall;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the fluent method call renderer.
 */
class RenderFluentMethodTest extends TestCase {

  public function testFluentMethodCallRenderer(): void {
    $calls = new FluentMethodCall();
    $calls
      ->setString('my string')
      ->setTranslated(FluentMethodCall::t('Translated'))
      ->setBoolean(TRUE)
      ->setArray([])
      ->setMultipleParameters('string', FALSE, ['key' => 'value']);

    $this->assertEquals(
      [
        "  ->setString('my string')",
        '  ->setTranslated(t("Translated"))',
        "  ->setBoolean(TRUE)",
        "  ->setArray([])",
        "  ->setMultipleParameters('string', FALSE, ['key' => 'value']);",
      ],
      $calls->getCodeLines()
    );
  }

}
