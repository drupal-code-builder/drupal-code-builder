<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests the container.
 *
 * In particular, this needs to test that two separate test cases with different
 * core major versions set on the environment don't cross-pollute the
 * container's versionned services.
 * @group container
 */
class ContainerTest extends TestCase {

  /**
   * Test the container with major version set to 7.
   */
  public function testContainer7() {
    $environment = new \DrupalCodeBuilder\Environment\TestsSampleLocation;
    $version_helper = new \DrupalCodeBuilder\Environment\VersionHelperTestsPHPUnit;
    $version_helper->setFakeCoreMajorVersion(7);
    \DrupalCodeBuilder\Factory::setEnvironment($environment)->setCoreVersionHelper($version_helper);

    $container = \DrupalCodeBuilder\Factory::getContainer();

    $this->assertEquals(7, $container->get('environment')->getCoreMajorVersion());
    $this->assertEquals(\DrupalCodeBuilder\Task\ReportPluginData::class, get_class($container->get('ReportPluginData')));
    // There is no Collect7 class, so we expect the plain class here.
    $this->assertEquals(\DrupalCodeBuilder\Task\Collect::class, get_class($container->get('Collect')));
    $this->assertEquals(\DrupalCodeBuilder\Task\Collect\HooksCollector7::class, get_class($container->get('Collect\HooksCollector')));
  }

  /**
   * Test the container with major version set to 8.
   */
  public function testContainer8() {
    $environment = new \DrupalCodeBuilder\Environment\TestsSampleLocation;
    $version_helper = new \DrupalCodeBuilder\Environment\VersionHelperTestsPHPUnit;
    $version_helper->setFakeCoreMajorVersion(8);
    \DrupalCodeBuilder\Factory::setEnvironment($environment)->setCoreVersionHelper($version_helper);

    $container = \DrupalCodeBuilder\Factory::getContainer();

    $this->assertEquals(8, $container->get('environment')->getCoreMajorVersion());
    $this->assertEquals(\DrupalCodeBuilder\Task\ReportPluginData::class, get_class($container->get('ReportPluginData')));
    $this->assertEquals(\DrupalCodeBuilder\Task\Collect8::class, get_class($container->get('Collect')));
    $this->assertEquals(\DrupalCodeBuilder\Task\Collect\HooksCollector8::class, get_class($container->get('Collect\HooksCollector')));

    $generate_module = $container->get('Generate|module');
    $this->assertEquals(\DrupalCodeBuilder\Task\Generate::class, get_class($generate_module));

    $r = new \ReflectionObject($generate_module);
    $p = $r->getProperty('base');
    $p->setAccessible(TRUE);
    $this->assertEquals('module', $p->getValue($generate_module));

    $generate_profile = $container->get('Generate|profile');
    $this->assertEquals(\DrupalCodeBuilder\Task\Generate::class, get_class($generate_profile));

    $r = new \ReflectionObject($generate_profile);
    $p = $r->getProperty('base');
    $p->setAccessible(TRUE);
    $this->assertEquals('profile', $p->getValue($generate_profile));
  }

}
