<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Generator for PHP class files that define a class annotation.
 */
class AnnotationClass extends PHPClassFile {

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperties([
      // TODO: this is specific to plugins.
      'plugin_relative_namespace' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function getClassDocBlockLines() {
    $lines = parent::getClassDocBlockLines();

    $lines[] = "";
    $lines[] = "@Annotation";

    return $lines;
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
