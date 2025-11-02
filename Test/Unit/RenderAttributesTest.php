<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;

/**
 * Unit tests for the attribute renderer.
 */
class RenderAttributesTest extends TestCase {

  /**
   * Tests an attribute with nested objects.
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
        'star_trek_name' => "T'Pau",
        'fluffy' => TRUE,
        'class' => '\Drupal\my_module\KittyHandler::class',
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

    // Note that the class won't get extracted as fully-qualified class
    // extraction is handled at the PHP file level.
    $expected_attribute = <<<EOT
    #[\Drupal\Core\Block\Attribute\Block(
      // The plugin ID.
      id: 'cat',
      // The noise it makes.
      admin_label: new \Drupal\Core\StringTranslation\TranslatableMarkup('Miaow'),
      // This is a comment that is too long because it is going over the limit of 80
      // characters in a line here we go.
      extra: [
        'purr' => 'value',
      ],
      star_trek_name: "T'Pau",
      fluffy: TRUE,
      class: \Drupal\my_module\KittyHandler::class,
    )]
    EOT;

    $this->assertEquals($expected_attribute, $attribute);
  }

  /**
   * Test attributes with inline values.
   */
  public function testAttributeInline() {
    // With inline implicit.
    $attribute = PhpAttributes::class(
      '\Attribute',
      '\Attribute::TARGET_CLASS'
    );
    $lines = $attribute->render();

    $this->assertCount(1, $lines);
    $this->assertEquals('#[\Attribute(\Attribute::TARGET_CLASS)]', $lines[0]);

    // With inline forced.
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
