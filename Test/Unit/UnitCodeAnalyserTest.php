<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests the CodeAnalyser task helper.
 */
class UnitCodeAnalyserTest extends TestCase {
  use ProphecyTrait;

  /**
   * Tests the CodeAnalyser task helper.
   */
  public function testCodeAnalyser() {
    // Sanity check that we can run this test.
    $cwd = getcwd();
    if (!file_exists($cwd . '/vendor')) {
      $this->markTestSkipped(
        'This test must be run from the Composer project root.'
      );
    }

    $environment = $this->prophesize('DrupalCodeBuilder\Environment\EnvironmentInterface');
    $environment->getRoot()->willReturn($cwd . '/vendor');

    $container = $this->prophesize(DummyContainer::class);

    // We need at least one dummy value here to simulate the passing of module
    // namespaces to the script.
    $container->getParameter('container.namespaces')->willReturn([
      'DrupalCodeBuilder\SomeNamespace' => "path/is/fictitious",
    ]);

    $environment->getContainer()->willReturn($container->reveal());

    $code_analyser = new \DrupalCodeBuilder\Task\Collect\CodeAnalyser($environment->reveal());

    // Some basic class checks that should pass.
    $this->assertTrue($code_analyser->classIsUsable(\StdClass::class), "StdClass is safe.");
    $this->assertTrue($code_analyser->classIsUsable(__CLASS__), "The current class is safe.");
    $this->assertTrue($code_analyser->classIsUsable('MadeUpClass'), "A non-existent class is safe.");
    $this->assertTrue($code_analyser->classIsUsable('DrupalCodeBuilder\Test\Fixtures\Php7Class'), "A class with PHP7 syntax is safe.");

    // For development: use this line to verify that BadClass indeed crashes
    // PHP!
    //class_exists('DrupalCodeBuilder\Test\Fixtures\BadClass');
    $this->assertFalse($code_analyser->classIsUsable('DrupalCodeBuilder\Test\Fixtures\BadClass'), "A broken class is not safe.");

    $this->assertTrue($code_analyser->classIsUsable(\StdClass::class), "The script can be used again after a bad class.");
  }

}

// Dummy class to define the single method we need to mock on a container.
class DummyContainer {

  function getParameter($name) {}

}
