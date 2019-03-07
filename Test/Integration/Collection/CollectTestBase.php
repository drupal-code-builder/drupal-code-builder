<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;

use Drupal\KernelTests\KernelTestBase;

/**
 * Integration tests test aspects that need a working Drupal site.
 *
 * These need to be run from Drupal's PHPUnit, rather than ours:
 * @code
 *  [drupal]/core $ ../vendor/bin/phpunit ../vendor/drupal-code-builder/drupal-code-builder/Test/Integration/Collection
 * @endcode
 */
class CollectTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Drupal doesn't know about DCB, so won't have it in its autoloader, so
    // rely on the Factory file's autoloader.
    $dcb_root = dirname(dirname(dirname(__DIR__)));
    require_once("$dcb_root/Factory.php");

    \DrupalCodeBuilder\Factory::setEnvironmentLocalClass('DrupalLibrary')
      ->setCoreVersionNumber(\Drupal::VERSION);

    $this->environment = \DrupalCodeBuilder\Factory::getEnvironment();

    parent::setUp();
  }

}
