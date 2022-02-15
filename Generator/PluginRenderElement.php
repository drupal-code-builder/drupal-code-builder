<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a render element constraint plugin.
 *
 * This handles generating additional code such as hook_theme(), template, etc.
 *
 * This is a variant generator for the Plugin generator, and should not be
 * used directly.
 */
class PluginRenderElement extends PluginAnnotationDiscovery {

  /**
   * Return an array of subcomponent types.
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    if (empty($this->component_data['replace_parent_plugin'])) {
      $components['theme_hook'] = [
        'component_type' => 'ThemeHook',
        'theme_hook_name' => $this->component_data['plugin_name'],
      ];
    }


    return $components;
  }

}
