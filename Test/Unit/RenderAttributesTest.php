<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;

/**
 * Unit tests for the attribute renderer.
 */
class RenderAttributesTest extends TestCase {

  /**
   * Tests an annotation with nested annotations.
   */
  public function testAttributeWithNesting() {
    $attribute = PhpAttributes::class(
      'Drupal\Core\Block\Attribute\Block',
      [
        'id' => 'cat',
        'admin_label' => PhpAttributes::object(
          'Drupal\Core\StringTranslation\TranslatableMarkup',
          'Miaow',
        ),
        'extra' => [
          'purr' => 'value',
        ],
      ],
      [
        'id' => 'The plugin ID.',
        'admin_label' => 'The noise it makes.',
        // Keep words short around the wrap point as potential bugs are likely
        // to be a small amount off.
        'extra' => 'This is a comment that is too long because it is going over the limit of 80 characters in a line here we go.',
      ],
    );

    $lines = $attribute->render();

    foreach ($lines as $line) {
      $this->assertLessThanOrEqual(80, strlen($line), "Line '$line' is wrapped to 80 characters.");
    }

    $attribute = implode("\n", $lines);

    $expected_attribute = <<<EOT
    #[\Drupal\Core\Block\Attribute\Block(
      // The plugin ID.
      id: 'cat',
      // The noise it makes.
      admin_label: new \Drupal\Core\StringTranslation\TranslatableMarkup("Miaow"),
      // This is a comment that is too long because it is going over the limit of 80
      // characters in a line here we go.
      extra: [
        'purr' => 'value',
      ],
    )]
    EOT;

    $this->assertEquals($expected_attribute, $attribute);
  }

  public function testAttributeInline() {
    $attribute = PhpAttributes::class(
      'Drupal\Core\Block\Attribute\Block',
      [
        'id' => 'cat',
        'label' => 'Dog',
      ],
    );
    $attribute->forceInline();

    $lines = $attribute->render();

    $this->assertCount(1, $lines);
    $this->assertEquals("#[\Drupal\Core\Block\Attribute\Block(id: 'cat', label: 'Dog')]", $lines[0]);
  }

}
