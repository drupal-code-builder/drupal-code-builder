<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Generator\Render\FormAPIArrayRenderer;

/**
 * Unit tests for the form element renderer.
 */
class RenderFormArrayTest extends TestCase {

  /**
   * Tests rendering of form elements.
   */
  public function testFormElements(): void {
    $form_array = [
      'element' => [
        '#type' => 'checkboxes',
        '#options' => [],
        '#fish' => TRUE,
      ],
    ];

    $form_renderer = new FormAPIArrayRenderer($form_array);

    $lines = $form_renderer->render();

    $this->assertContains("  '#options' => [],", $lines);
  }

}
