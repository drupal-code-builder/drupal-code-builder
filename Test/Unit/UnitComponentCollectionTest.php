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
   * Tests components can't be added with an existing unique ID.
   */
  public function testDuplicateUniqueID() {
    $collection = new ComponentCollection;

    $root_component = $this->prophesize(RootComponent::class);
    $root_component->getUniqueID()->willReturn('root:root');
    $root_component->containingComponent()->willReturn(NULL);
    $collection->addComponent('root', $root_component->reveal(), NULL);

    $component_two = $this->prophesize(BaseGenerator::class);
    $component_two->getUniqueID()->willReturn('not_so_unique');
    $collection->addComponent('component_2', $component_two->reveal(), NULL);

    $component_three = $this->prophesize(BaseGenerator::class);
    $component_three->getUniqueID()->willReturn('not_so_unique');

    $this->expectException(\Exception::class);

    $collection->addComponent('component_3', $component_three->reveal(), NULL);
  }

  /**
   * Tests components can't be added to the collection after the tree is built.
   */
  public function testNoFurtherComponentsAfterTreeBuild() {
    $collection = new ComponentCollection;

    $root_component = $this->prophesize(RootComponent::class);
    $root_component->getUniqueID()->willReturn('root:root');
    $root_component->containingComponent()->willReturn(NULL);

    $collection->addComponent('root', $root_component->reveal(), NULL);

    $collection->assembleContainmentTree();

    $root_requested = $this->prophesize(BaseGenerator::class);

    $this->expectException(\LogicException::class);

    $collection->addComponent('requested', $root_requested->reveal(), $root_component->reveal());
  }

}
