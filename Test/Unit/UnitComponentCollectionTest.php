<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Generator\Collection\ComponentCollection;
use DrupalCodeBuilder\Generator\BaseGenerator;
use DrupalCodeBuilder\Generator\RootComponent;

/**
 * Unit tests for the ComponentCollection class.
 */
class UnitComponentCollectionTest extends TestCase {

  /**
   * Tests components can't be added to the collection after the tree is built.
   */
  public function testNoFurtherComponentsAfterTreeBuild() {
    $collection = new ComponentCollection;

    $root_component = $this->prophesize(RootComponent::class);
    $root_component->getMergeTag()->willReturn(NULL);
    $root_component->containingComponent()->willReturn(NULL);

    $collection->addComponent('root', $root_component->reveal(), NULL);

    $collection->assembleContainmentTree();

    $root_requested = $this->prophesize(BaseGenerator::class);

    $this->expectException(\LogicException::class);

    $collection->addComponent('requested', $root_requested->reveal(), $root_component->reveal());
  }

}
