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

  // TODO: some sample properties.

}
