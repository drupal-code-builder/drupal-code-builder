<?php

/**
 * @file
 * Contains ModuleBuilder\Exception.
 */

namespace ModuleBuilder;

/**
 * Custom exception class.
 */
class Exception extends \Exception {
  // Flag set to TRUE if hook data needs downloading (and the folders are ok).
  // This allows us to recover gracefully.
  public $needs_hooks_download;
}
