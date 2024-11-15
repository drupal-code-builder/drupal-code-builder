<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\Render\ClassAnnotation;
use DrupalCodeBuilder\Generator\Render\PhpAttributes;

/**
 * Base class for specific class-based plugins.
 *
 * This needs to inherit from the base class for class discovery plugins, and
 * dynamically switch for attributes or annotations because attribute plugins
 * use annotations on older Drupal core version.
 */
class PluginClassDiscoveryHybrid extends PluginClassDiscovery {

  use PluginAttributeDiscoveryTrait {
    PluginAttributeDiscoveryTrait::getClassAttributes as traitGetClassAttributes;
  }
  use PluginAnnotationDiscoveryTrait {
    PluginAnnotationDiscoveryTrait::classAnnotation as traitClassAnnotation;
  }

  /**
   * {@inheritdoc}
   */
  protected function getClassAttributes(): ?PhpAttributes {
    if (!empty($this->plugin_type_data['plugin_definition_attribute_name'])) {
      return $this->traitGetClassAttributes();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  function classAnnotation(): ?ClassAnnotation {
    if (empty($this->plugin_type_data['plugin_definition_attribute_name'])) {
      return $this->traitClassAnnotation();
    }

    return NULL;
  }

}
