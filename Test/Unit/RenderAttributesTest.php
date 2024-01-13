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
        'admin_label' => PhpAttributes::nested(
          'Drupal\Core\StringTranslation\TranslatableMarkup',
          "Miaow",
        ),
        'extra' => [
          'purr' => 'value',
        ],
      ],
    );

    $lines = $attribute->render();
    $attribute = implode("\n", $lines);

    $expected_attribute = <<<EOT
    #[\Drupal\Core\Block\Attribute\Block(
      id: "cat",
      admin_label: new \Drupal\Core\StringTranslation\TranslatableMarkup("Miaow"),
      extra: [
        'purr' => "value",
      ],
    )]
    EOT;

    $this->assertEquals($expected_attribute, $attribute);
  }

}
