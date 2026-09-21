<?php

namespace DrupalCodeBuilder\Enum;

/**
 * Whether a plugin type's plugins can be configurable.
 */
enum PluginConfigurable {

  /**
   * The plugin type was not detected to be configurable.
   */
  case NotConfigurable;

  /**
   * The plugins are all configurable.
   */
  case BaseConfigurable;

  /**
   * Plugins may be configurable.
   */
  case OptionallyConfigurable;

}
