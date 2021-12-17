<?php

namespace DrupalCodeBuilder\Environment;

use Psr\Container\ContainerInterface;

/**
 * Environment class for tests using prepared sample hook data.
 */
class TestsSampleLocation extends Tests {

  /**
   * The short class name of the storage helper to use.
   */
  protected $storageType = 'TestExportInclude';

  /**
   * The mocked Drupal service container.
   *
   * @var \Psr\Container\ContainerInterface
   */
  protected $container;

  /**
   * Set the hooks directory.
   */
  function getHooksDirectorySetting() {
    // Set the folder for the hooks. This contains a prepared file for the tests
    // to use.
    $directory = dirname(dirname(__FILE__)) . '/Test/sample_hook_definitions/' . $this->getCoreMajorVersion();

    $this->hooks_directory = $directory;
  }

  /**
   * Sets the mocked Drupal service container.
   *
   * @param \Psr\Container\ContainerInterface $container
   */
  public function setContainer(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function getContainer() {
    return $this->container;
  }

}
