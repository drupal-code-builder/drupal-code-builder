<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests rebuilding the cached container.
 * @group container
 */
class ContainerRebuildTest extends TestCase {

  /**
   * The original location of the cached compiled container file.
   *
   * This is renamed by the test, and restored in tearDown().
   *
   * @var string
   */
  protected $cachedContainerFilename;

  /**
   * Test rebuilding the cached container.
   */
  public function testRebuildContainer() {
    $cached_file = realpath(__DIR__ . '/../../DependencyInjection/cache/DrupalCodeBuilderCompiledContainer.php');
    $this->cachedContainerFilename = $cached_file;

    $this->assertTrue(file_exists($cached_file));

    $original_cached_container_timestamp = filemtime($cached_file);

    // Move the original cached container so we can restore it at the end of the
    // test, and so not leave the codebase broken.
    // TODO: add a parameter for the cached container filename?
    rename($cached_file, $cached_file . '-temp');

    $environment = new \DrupalCodeBuilder\Environment\TestsSampleLocation;
    $version_helper = new \DrupalCodeBuilder\Environment\VersionHelperTestsPHPUnit;
    $version_helper->setFakeCoreMajorVersion(7);
    \DrupalCodeBuilder\Factory::setEnvironment($environment)->setCoreVersionHelper($version_helper);

    $container = \DrupalCodeBuilder\Factory::getContainer();

    $new_cached_container_timestamp = filemtime($cached_file);

    $this->assertNotEquals($original_cached_container_timestamp, $new_cached_container_timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    // Restore the cached container so the codebase is left in its original
    // state.
    rename($this->cachedContainerFilename . '-temp', $this->cachedContainerFilename);
  }

}
