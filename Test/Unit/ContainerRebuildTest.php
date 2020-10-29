<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests rebuilding the cached container.
 * @group container
 */
class ContainerRebuildTest extends TestCase {

  /**
   * Test rebuilding the cached container.
   */
  public function testRebuildContainer() {
    $cached_file = realpath('DependencyInjection/cache/DrupalCodeBuilderCompiledContainer.php');
    $this->assertTrue(file_exists($cached_file));

    $original_cached_container_timestamp = filemtime($cached_file);

    unlink($cached_file);

    $environment = new \DrupalCodeBuilder\Environment\TestsSampleLocation;
    $version_helper = new \DrupalCodeBuilder\Environment\VersionHelperTestsPHPUnit;
    $version_helper->setFakeCoreMajorVersion(7);
    \DrupalCodeBuilder\Factory::setEnvironment($environment)->setCoreVersionHelper($version_helper);

    $container = \DrupalCodeBuilder\Factory::getContainer();

    $new_cached_container_timestamp = filemtime($cached_file);

    $this->assertNotEquals($original_cached_container_timestamp, $new_cached_container_timestamp);
  }

}
