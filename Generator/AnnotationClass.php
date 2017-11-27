<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for PHP class files that define a class annotation.
 */
class AnnotationClass extends PHPClassFile {

  protected function class_doc_block() {
    $docblock_code = [];

    // TODO
    $docblock_code[] = $this->component_data['docblock_first_line'];
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
      'The plugin ID.',
      ['public']
    );

    $this->properties[] = $this->createPropertyBlock(
      'label',
      '\Drupal\Core\Annotation\Translation',
      'The human-readable name of the plugin.',
      /*
      // TODO: needs:
      '@ingroup plugin_translatable',
      */
      ['public']
    );
  }

}
