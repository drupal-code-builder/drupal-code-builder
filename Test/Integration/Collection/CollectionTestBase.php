<?php

namespace DrupalCodeBuilder\Test\Integration\Collection;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for collection integration tests.
 *
 * This type of test tests aspects that need a working Drupal site.
 *
 * These need to be run from Drupal's PHPUnit, rather than ours:
 * @code
 *  [drupal]/core $ ../vendor/bin/phpunit ../vendor/drupal-code-builder/drupal-code-builder/Test/Integration/Collection/CollectHooksTest.php
 * @endcode
 */
class CollectionTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    \DrupalCodeBuilder\Factory::setEnvironmentLocalClass('DrupalLibrary')
      ->setCoreVersionNumber(\Drupal::VERSION);

    $this->environment = \DrupalCodeBuilder\Factory::getEnvironment();

    parent::setUp();
  }

}
