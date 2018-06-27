<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Generator\Render\ClassAnnotation;

/**
 * Unit tests for the ClassAnnotation renderer.
 */
class RenderClassAnnotationTest extends TestCase {

  /**
   * Tests a single-line annotation, as used for Views plugins.
   */
  public function testStringAnnotation() {
    $annotation = ClassAnnotation::StringAnnotation('cat');
    $annotation_lines = $annotation->render();
    $annotation_text = $this->renderDocblock($annotation_lines);

    $this->assertEquals(' * @StringAnnotation("cat")', $annotation_text);
  }

  /**
   * Tests an annotation with nested annotations.
   */
  public function testAnnotationWithNesting() {
    $annotation = ClassAnnotation::ContentEntityType([
      'id' => 'cat',
      'label' => ClassAnnotation::Translation("Cat"),
      'label_count' => ClassAnnotation::PluralTranslation([
        'singular' => "@count content item",
        'plural' => "@count content items",
      ]),
      'handlers' => [
        "storage" => "Drupal\cat\CatStorage",
        "form" => [
          "default" => "Drupal\cat\CatForm",
          "delete" => "Drupal\cat\Form\CatDeleteForm",
        ],
        "list_builder" => "Drupal\cat\CatListBuilder",
      ],
      'entity_keys' => [
        'id' => 'nid',
        'bundle' => 'type',
      ],
      // Numeric keys should not be output.
      'config_export' => [
        'whiskers',
        'paws',
      ],
    ]);
    $annotation_lines = $annotation->render();
    $annotation_text = $this->renderDocblock($annotation_lines);

    $expected_annotation = <<<EOT
 * @ContentEntityType(
 *   id = "cat",
 *   label = @Translation("Cat"),
 *   label_count = @PluralTranslation(
 *     singular = "@count content item",
 *     plural = "@count content items",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\cat\CatStorage",
 *     "form" = {
 *       "default" = "Drupal\cat\CatForm",
 *       "delete" = "Drupal\cat\Form\CatDeleteForm",
 *     },
 *     "list_builder" = "Drupal\cat\CatListBuilder",
 *   },
 *   entity_keys = {
 *     "id" = "nid",
 *     "bundle" = "type",
 *   },
 *   config_export = {
 *     "whiskers",
 *     "paws",
 *   },
 * )
EOT;

    $this->assertEquals($expected_annotation, $annotation_text);
  }

  /**
   * Helper to create a partial docblock from the annotation lines.
   *
   * @param string[] $lines
   *   The lines from the renderer.
   *
   * @return string
   *   The docblock text as a single string.
   */
  protected function renderDocblock($lines) {
    $lines = array_map(function ($line) {
      return " * $line";
    }, $lines);

    return implode("\n", $lines);
  }

}
