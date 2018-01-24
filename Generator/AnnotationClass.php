<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for PHP class files that define a class annotation.
 */
class AnnotationClass extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      // TODO: this is specific to plugins.
      'plugin_relative_namespace' => [
        'acquired' => TRUE,
      ],
    ];
  }

  protected function class_doc_block() {
    $docblock_code = [];

    $docblock_code[] = $this->component_data['docblock_first_line'];
    $docblock_code[] = "";
    // TODO: this is specific to plugins. Cleaning this up will require being
    // able to pass more class docs in than the first line.
    $docblock_code[] = "Plugin namespace: {$this->component_data['plugin_relative_namespace']}.";
    $docblock_code[] = "";

    $docblock_code[] = "@Annotation";

    return $this->docBlock($docblock_code);
  }

  /**
   * {@inheritdoc}
   */
  protected function collectSectionBlocks() {
    // Set up properties.
    // TODO: these properties are only for plugin annotations, but so far
    // nothing else uses this generator.
    $this->properties[] = $this->createPropertyBlock(
      'id',
      'string',
      [
        'docblock_first_line' => 'The plugin ID.',
        'prefixes' => ['public'],
      ]
    );

    $this->properties[] = $this->createPropertyBlock(
      'label',
      '\Drupal\Core\Annotation\Translation',
      [
        'docblock_first_line' => 'The human-readable name of the plugin.',
        'prefixes' => ['public'],
      ]
      /*
      // TODO: needs:
      '@ingroup plugin_translatable',
      */
    );
  }

}
