<?php

namespace DrupalCodeBuilder\PhpParser;

use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

/**
 * PhpParse node visitor which extracts interesting nodes into flat arrays.
 *
 * @todo Use this in PHPTester instead of the anonymous class.
 */
class GroupingNodeVisitor extends NodeVisitorAbstract {

  /**
   * {@inheritdoc}
   */
  public function enterNode(Node $node) {
    switch (get_class($node)) {
      case \PhpParser\Node\Stmt\Namespace_::class:
        $this->nodes['namespace'][] = $node;
        break;
      case \PhpParser\Node\Stmt\Use_::class:
        $this->nodes['imports'][] = $node;
        break;
      case \PhpParser\Node\Stmt\Class_::class:
        $this->nodes['classes'][$node->name->toString()] = $node;
        break;
      case \PhpParser\Node\Stmt\Interface_::class:
        $this->nodes['interfaces'][$node->name->toString()] = $node;
        break;
      case \PhpParser\Node\Stmt\Property::class:
        $this->nodes['properties'][$node->props[0]->name->toString()] = $node;
        break;
      case \PhpParser\Node\Stmt\TraitUse::class:
        $this->nodes['traits'][$node->traits[0]->parts[0]] = $node;
        break;
      case \PhpParser\Node\Stmt\Function_::class:
        $this->nodes['functions'][$node->name->toString()] = $node;
        break;
      case \PhpParser\Node\Stmt\ClassMethod::class:
        $this->nodes['methods'][$node->name->toString()] = $node;
        break;
    }
  }

  /**
   * Get the extracted nodes.
   *
   * @return array
   *   An array of nodes grouped by type.
   */
  public function getNodes(): array {
    return $this->nodes;
  }

};
