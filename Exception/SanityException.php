<?php

/**
 * @file
 * Contains ModuleBuilder\Exception\SanityException.
 */

namespace ModuleBuilder\Exception;

/**
 * Thrown when the sanity level is not met by the environment.
 */
class SanityException extends \Exception {

  protected $failed_sanity_level;

  /**
   * Constructor.
   *
   * @param $failed_sanity_level
   *  The sanity level that was not met. See
   *  BaseEnvironment::verifyEnvironment().
   */
  public function __construct($failed_sanity_level) {
    parent::__construct();

    $this->failed_sanity_level = $failed_sanity_level;
  }

  /**
   * Returns the sanity level that failed, and caused this exception.
   */
  public function getFailedSanityLevel() {
    return $this->failed_sanity_level;
  }

}
