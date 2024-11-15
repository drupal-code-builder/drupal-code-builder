<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\Render\PhpAttributes;

/**
 * Generator for an attribute plugin.
 *
 * This is a variant generator for the Plugin generator, and should not be
 * used directly.
 */
class PluginAttributeDiscovery extends PluginClassDiscovery {

  use PluginAttributeDiscoveryTrait;

}
