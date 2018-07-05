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
   * Tests adding the same component twice.
   */
  public function testRepeatAddingComponent() {
    $collection = new ComponentCollection;

    $root_component = $this->prophesize(RootComponent::class);
    $root_requested = $this->prophesize(BaseGenerator::class);

    $collection->addComponent('root', $root_component->reveal(), NULL);
    $collection->addComponent('requested', $root_requested->reveal(), $root_component->reveal());

    $this->expectException(\Exception::class);
    $this->expectExceptionMessageRegExp('@Unique ID .+ already in use, by component with request path .+.@');

    $collection->addComponent('requested', $root_requested->reveal(), $root_component->reveal());
  }

  /**
   * Tests the containment tree can't be interrogated until it's been built.
   */
  public function testTreeNotAssembledYet() {
    $collection = new ComponentCollection;

    $root_component = $this->prophesize(RootComponent::class);
    $root_requested = $this->prophesize(BaseGenerator::class);

    $collection->addComponent('root', $root_component->reveal(), NULL);
    $collection->addComponent('requested', $root_requested->reveal(), $root_component->reveal());

    $this->expectException(\LogicException::class);

    $collection->getContainmentTreeChildren($root_component->reveal());
  }

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

  /**
   * Tests data from the collection concerning the request tree.
   */
  public function testRequestData() {
    $collection = new ComponentCollection;

    // Create a request chain:
    // root -> requested -> inner_root -> inner_requested
    $root_component = $this->prophesize(RootComponent::class)->reveal();
    $root_requested = $this->prophesize(BaseGenerator::class)->reveal();
    $inner_root = $this->prophesize(RootComponent::class)->reveal();
    $inner_requested = $this->prophesize(BaseGenerator::class)->reveal();

    $collection->addComponent('root', $root_component, NULL);
    $collection->addComponent('requested', $root_requested, $root_component);
    $collection->addComponent('inner_root', $inner_root, $root_requested);
    $collection->addComponent('inner_requested', $inner_requested, $inner_root);

    // Check the request paths.
    $request_paths = $collection->getComponentRequestPaths();

    $this->assertContains('root', $request_paths);
    $this->assertContains('root/requested', $request_paths);
    $this->assertContains('root/requested/inner_root', $request_paths);
    $this->assertContains('root/requested/inner_root/inner_requested', $request_paths);

    // Check getRootComponent().
    $this->assertEquals($root_component, $collection->getRootComponent());

    // Check getting the closest requesting root component.
    $this->assertEquals($inner_root, $collection->getClosestRequestingRootComponent($inner_requested));
    $this->assertEquals($root_component, $collection->getClosestRequestingRootComponent($inner_root));
    $this->assertEquals($root_component, $collection->getClosestRequestingRootComponent($root_requested));

    // ... except for the root itself.
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('ComponentCollection::getClosestRequestingRootComponent() may not be called with the root component.');

    $this->assertEquals($root_component, $collection->getClosestRequestingRootComponent($root_component));
  }

  /**
   * Tests the assembly of the containment tree.
   */
  public function testContainmentTree() {
    $collection = new ComponentCollection;

    $root_prophecy = $this->prophesize(RootComponent::class);
    $requested_a_prophecy = $this->prophesize(BaseGenerator::class);
    $requested_a_1_prophecy = $this->prophesize(BaseGenerator::class);
    $requested_b_prophecy = $this->prophesize(RootComponent::class);
    $requested_b_1_prophecy = $this->prophesize(BaseGenerator::class);
    $requested_b_2_prophecy = $this->prophesize(BaseGenerator::class);
    $requested_c_prophecy = $this->prophesize(BaseGenerator::class);

    $root_prophecy->getMergeTag()->willReturn();
    $requested_a_prophecy->getMergeTag()->willReturn();
    $requested_a_1_prophecy->getMergeTag()->willReturn();
    $requested_b_prophecy->getMergeTag()->willReturn();
    $requested_b_1_prophecy->getMergeTag()->willReturn();
    $requested_b_2_prophecy->getMergeTag()->willReturn();
    $requested_c_prophecy->getMergeTag()->willReturn();

    $root = $root_prophecy->reveal();
    $requested_a = $requested_a_prophecy->reveal();
    $requested_a_1 = $requested_a_1_prophecy->reveal();
    $requested_b = $requested_b_prophecy->reveal();
    $requested_b_1 = $requested_b_1_prophecy->reveal();
    $requested_b_2 = $requested_b_2_prophecy->reveal();
    $requested_c = $requested_c_prophecy->reveal();

    // Create a request chain:
    // root -> requested_a -> requested_a_1
    //      -> requested_b [root] -> requested_b_1
    //                            -> requested_b_2
    //      -> requested_c
    $collection->addComponent('root', $root, NULL);
    $collection->addComponent('a', $requested_a, $root);
    $collection->addComponent('a_1', $requested_a_1, $requested_a);
    $collection->addComponent('b', $requested_b, $root);
    $collection->addComponent('b_1', $requested_b_1, $requested_b);
    $collection->addComponent('b_2', $requested_b_2, $requested_b);
    $collection->addComponent('c', $requested_c, $root);

    // Create a containment hierarchy that's completely different:
    // root [
    //   requested_a_1 [
    //     requested_a
    //     requested_b [
    //       requested_b_1 [
    //         requested_b_2
    //       ]
    //     ]
    //     requested_c
    //   ]
    // ]
    $root_prophecy->containingComponent()->willReturn();
    $requested_a_prophecy->containingComponent()->willReturn('%self:a_1');
    $requested_a_1_prophecy->containingComponent()->willReturn('%root');
    $requested_b_prophecy->containingComponent()->willReturn('%nearest_root:a:a_1');
    $requested_b_1_prophecy->containingComponent()->willReturn('%requester');
    $requested_b_2_prophecy->containingComponent()->willReturn('%nearest_root:b_1');
    $requested_c_prophecy->containingComponent()->willReturn('%requester:a:a_1');

    $collection->assembleContainmentTree();

    // Check the tree.
    $tree_with_request_paths = $collection->getContainmentTreeWithRequestPaths();

    $this->assertEquals([
      "root" => [
        "root/a/a_1",
      ],
      "root/a/a_1" => [
        "root/a",
        "root/b",
        "root/c",
      ],
      "root/b" => [
        "root/b/b_1",
      ],
      "root/b/b_1" => [
        "root/b/b_2",
      ],
    ], $tree_with_request_paths);

    // Check the getContainmentTreeChildren() method.
    $this->assertContains($requested_a_1, $collection->getContainmentTreeChildren($root));
    $this->assertContains($requested_a, $collection->getContainmentTreeChildren($requested_a_1));
    $this->assertContains($requested_b, $collection->getContainmentTreeChildren($requested_a_1));
  }

}
