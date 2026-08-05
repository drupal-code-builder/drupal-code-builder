<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Factory;
use PHPUnit\Framework\TestCase;
use MutableTypedData\Definition\DataDefinition;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use MutableTypedData\Exception\InvalidDefinitionException;
use MutableTypedData\Test\VarDumperSetupTrait;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Unit tests for the TODO class.
 */
class UnitFrontEndExpressionsTest extends TestCase {

  use ProphecyTrait;

  protected function setUp(): void {
    // $this->setUpVarDumper();
    $environment = $this->prophesize(\DrupalCodeBuilder\Environment\EnvironmentInterface::class);
    \DrupalCodeBuilder\Factory::setEnvironment($environment->reveal());

    // $this->setupDrupalCodeBuilder(11);
    // $this->container = \DrupalCodeBuilder\Factory::getContainer();
  }

  public function testJavaScriptExpressionsCode(): void {
    $task = Factory::getTask('FrontEndExpressions');
  }

}

