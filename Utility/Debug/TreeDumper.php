<?php

namespace DrupalCodeBuilder\Utility\Debug;

/**
 * Produces a textual representation of a tree for debug output.
 *
 * For example:
 *
 * @code
 * root
 * ├─ child
 * │  └─ grandchild
 * └─ other child
 * @endcode
 *
 * This expects a group of objects where each object has an ID and can be output
 * in some meaningful way as a string.
 *
 * The data structure is abitrary, as callables are passed in to navigate it.
 */
class TreeDumper {

  protected $dumper;
  protected $get_label;
  protected $get_children;

  /**
   * Dumps a tree of objects.
   *
   * This method may be called multiple times on the same TreeDumper object
   * with a different structure and different callbacks.
   *
   * @param mixed $root_id
   *   The ID of the root object of the tree.
   * @param callable $get_children
   *   A callable which given an object ID, returns an array of the IDs of its
   *   children. The keys of the array are immaterial. Signature is
   *   callable(mixed $id): array.
   * @param callable $get_label
   *   A callable which given an object ID, returns a string representation for
   *   debug output. Signature is callable(mixed $id): string.
   * @param callable $dumper
   *   A callable which outputs a line of text, for example
   *   \Symfony\Component\VarDumper\VarDumper::class::dump().
   */
  public function dumpTree(mixed $root_id, callable $get_children, callable $get_label, callable $dumper): void {
    $this->dumper = $dumper;
    $this->get_label = $get_label;
    $this->get_children = $get_children;

    // Dump the root item first, as it's shown without the tree structure.
    $dumper($get_label($root_id));

    $children = $get_children($root_id);
    assert(is_array($children));
    foreach ($children as $key => $child_id) {
      // Use strict comparison for keys in case of weird keys.
      $this->dumpTreeLeaf($child_id, 1, '', ($key === array_key_last($children)));
    }
  }

  /**
   * Dump a leaf and its children.
   *
   * @param mixed $leaf_id
   *   The ID of the leaf object.
   * @param int $nesting
   *   The nesting level, starting from 1 as the root object has already been
   *   dumped.
   * @param string $trail
   *   The tree diagram trail. This is the lines to the left that represent the
   *   link between the current leaf's parents and its subsequen children beyond
   *   this leaf.
   * @param bool $last
   *   Whether the current item is the last among its siblings.
   */
  protected function dumpTreeLeaf(mixed $leaf_id, int $nesting, string $trail, bool $last): void {
    // Make the tree diagram piece that connects to this leaf from its parent.
    $item_hierarchy_piece =
      $last
      ? '└─ '
      : '├─ ';

    ($this->dumper)($trail . $item_hierarchy_piece . ($this->get_label)($leaf_id));

    $children = ($this->get_children)($leaf_id);
    assert(is_array($children));
    foreach ($children as $key => $child_id) {
      // Append to the tree diagram trail for the current child item.
      $child_trail = $trail . (
        $last
        ? '   '
        : '│  '
      );

      $this->dumpTreeLeaf($child_id, $nesting + 1, $child_trail, ($key === array_key_last($children)));
    }
  }

}
