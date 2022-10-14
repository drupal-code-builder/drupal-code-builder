<?php

namespace DrupalCodeBuilder\Generator\Collection;

use DrupalCodeBuilder\Generator\GeneratorInterface;
use DrupalCodeBuilder\Generator\RootComponent;

/**
 * The collection of components for a generate execution.
 *
 * Instantiated components have different relationships between them:
 * - One component requests another; conversely, every component except for the
 *   root has a component that is its requester.
 * - Some components are said to contain other components. This represents where
 *   the eventual generated code will be. For example, a class component
 *   contains the components that are its methods.
 *
 * This holds the instantiated components, in several different structures:
 * - The linear list of components, which this class can iterate over.
 * - The tree of components arranged by request, that is, where the components
 *   are arranged according to which component requested which.
 * - The tree of components arranged by containment, that is, where components
 *   are arranged according to which component contains which. This is only
 *   available once assembleContainmentTree() has been called. After this, no
 *   more components may be added.
 * - The list of local names which each component uses when it spawns further
 *   components.
 * - The list of merge tags for components, grouped by closest requesting root,
 *   then type.
 */
class ComponentCollection implements \IteratorAggregate {

  /**
   * The list of instantiated components.
   *
   * These are iterated over by this class.
   *
   * Keys are the ID given by self::getComponentKey().
   *
   * @var \DrupalCodeBuilder\Generator\GeneratorInterface[]
   */
  private $components = [];

  /**
   * The ID of the root generator.
   *
   * @var string
   */
  private $rootGeneratorId;

  /**
   * The requesters tree.
   *
   * A tree where each key is a component ID, and each value is the ID of the
   * component that first requested it.
   *
   * @var array
   */
  private $requesters = [];

  /**
   * The IDs of components which are roots.
   *
   * An array whose keys are IDs and values are TRUE.
   *
   * @var array
   */
  private $roots = [];

  /**
   * List of each component's closest requesting root.
   *
   * An array whose keys are IDs and values are the closest requesting root ID.
   *
   * @var arrray
   */
  private $requestRoots = [];

  /**
   * The list of local names.
   *
   * A component's local name is a string which is used by its requester. This
   * is NOT necessarily unique across all components, but IS unique within the
   * set of the components that have the same requester.
   *
   * An array whose keys are component unique IDs. Each item is itself an array
   * whose keys are local names of the components it requests, and whose values
   * are the unique ID of the requested component.
   *
   * @var array
   */
  private $localNames = [];

  /**
   * The list of request paths.
   *
   * An array whose keys are component unique IDs. Each item is a string
   * representing the path of local names to that component from the root, with
   * each name separated by '/'.
   *
   * @var array
   */
  private $requestPaths = [];

  /**
   * The containment tree.
   *
   * A tree of parentage data for components, as an array keyed by the parent
   * component name, where each value is an array of the names of the child
   * components. So for example, the list of children of component 'foo' is
   * given by $tree['foo'].
   *
   * Do not access this directly, but use getContainmentTree(), which checks
   * that the tree has been assembled and is available.
   *
   * @var array
   */
  private $tree = NULL;

  /**
   * The merge tags.
   *
   * An lookup array of component unique IDs, whose successive levels of nesting
   * are:
   *  - the ID of the closest root requester
   *  - the component type
   *  - the component merge tag
   */
  private $mergeTags = [];

  /**
   * Indicates the the collection is locked and no more components may be added.
   *
   * @var bool
   */
  private $locked = FALSE;

  /**
   * Returns the iterator for this object.
   */
  public function getIterator(): \Traversable {
    return new \ArrayIterator($this->components);
  }

  /**
   * Returns a unique key for a component.
   *
   * This is unique per component object, but does not depend on request data,
   * so it cannot be used to deduplicate different objects.
   *
   * @param GeneratorInterface $component
   *   The component.
   *
   * @return string
   *   The unique key.
   */
  public function getComponentKey(GeneratorInterface $component) {
    return spl_object_id($component);
  }

  /**
   * Adds a component to the collection.
   *
   * @param $local_name
   *   The local name for the component, that is, the name used within the
   *   requesting components list of components to spawn, whether from
   *   data defined with GeneratorDefinition or from requests.
   * @param $component
   *   The component to add.
   * @param $requesting_component
   *   The component that requested the component being added. May be NULL if
   *   the component being added is the root component.
   */
  public function addComponent(string $local_name, GeneratorInterface $component, $requesting_component) {
    // $component_address = $component->getAddress();
    // dump("adding $local_name - $component_address");
    // Components may not be added once the collection is locked.
    if ($this->locked) {
      throw new \LogicException("Attempt to add component to locked collection.");
    }

    $key = $this->getComponentKey($component);

    if (isset($this->components[$key])) {
      throw new \Exception("Unique ID $key already in use, by component with request path {$this->requestPaths[$key]}.");
    }

    // If this is the first component, it's the root.
    if (!isset($this->rootGeneratorId)) {
      $this->rootGeneratorId = $key;

      $request_path = 'root';

      // Create the first item in the request paths array.
      $this->requestPaths[$key] = $request_path;
    }

    // If this is *a* root, keep track of it.
    if ($component->isRootComponent()) {
      $this->roots[$key] = TRUE;
    }

    if ($requesting_component) {
      $request_path = $this->requestPaths[$this->getComponentKey($requesting_component)] . '/' . $local_name;

      // Request paths should be unique.
      assert(!isset($this->requestPaths[$key]));

      // Add to the array of request paths.
      $this->requestPaths[$key] = $request_path;

      // Add to the array of requesters.
      if (!isset($this->requesters[$key])) {
        // TODO: store multiple requesters?
        $this->requesters[$key] = $this->getComponentKey($requesting_component);
      }

      // Keep track of the nearest requesting root component.
      $closest_requesting_root = $this->determineNearestRequestingRoot($key);
      $this->requestRoots[$key] = $closest_requesting_root;

      // Add to the array of local names.
      // dump("adding $key with local name $local_name");
      $this->localNames[$this->getComponentKey($requesting_component)][$local_name] = $key;
    }

    // Add to the merge tags list.
    if ($merge_tag = $this->getComponentMergeTag($component, $requesting_component)) {
      // $closest_requesting_root will be set because the root component has
      // no merge tag.
      $this->mergeTags[$closest_requesting_root][$component->getType()][$merge_tag] = $key;
    }

    $this->components[$key] = $component;
  }

  /**
   * Add details for a component's that's been replaced by an existing one.
   *
   * This ensures that the local names array still has track of the requested
   * local name, even though the request didn't result in a new component.
   *
   * @param $local_name
   *   The local name for the component that is being discarded, that is, the
   *   name used within the requesting components list of components to spawn,
   *   whether from properties or from requests.
   * @param $existing_component
   *   The existing component which caused the new component to be discarded.
   * @param $requesting_component
   *   The component that requested the new component being discarded.
   */
  public function addAliasedComponent($local_name, GeneratorInterface $existing_component, GeneratorInterface $requesting_component) {
    $this->localNames[$this->getComponentKey($requesting_component)][$local_name] = $this->getComponentKey($existing_component);
  }

  /**
   * Determines the closest requester that is a root component.
   *
   * @param $key
   *   The ID of the component.
   *
   * @return
   *   The ID of the closest component in the request chain that is a root
   *   component.
   */
  private function determineNearestRequestingRoot($key) {
    // We're ascending a tree whose root is a root component, so we have to
    // find a root eventually.
    // Note that the nearest requesting root of a root component is not itself.
    // E.g. with this chain:
    //   root -> requested -> inner_root
    // the nearest root of inner_root is root.
    do {
      $key = $this->requesters[$key];
    }
    while (!isset($this->roots[$key]));

    return $key;
  }

  /**
   * Gets the root component ID.
   *
   * @return
   *   The root component ID.
   */
  public function getRootComponentId() {
    return $this->rootGeneratorId;
  }

  /**
   * Gets all components.
   *
   * @return array
   *   The array of components.
   */
  public function getComponents() {
    return $this->components;
  }

  /**
   * Gets the request paths.
   *
   * @return array
   *   An array whose keys are the internal IDs and values are the request
   *   paths.
   */
  public function getComponentRequestPaths() {
    return $this->requestPaths;
  }

  /**
   * Gets the root component.
   *
   * @return
   *   The root component.
   */
  public function getRootComponent() {
    return $this->components[$this->rootGeneratorId];
  }

  /**
   * Returns the component with the given ID.
   *
   * @param string $id
   *   The component unique ID.
   *
   * @return
   *   The component.
   */
  public function getComponent($id) {
    return $this->components[$id];
  }

  /**
   * Assemble a tree of components, grouped by what they contain.
   *
   * For example, a code file contains its functions; a form component
   * contains the handler functions.
   *
   * This iterates over the flat list of components assembled by
   * ComponentCollector, and re-assembles it as a tree.
   *
   * The tree is an array of parentage data, where keys are the names of
   * components that are parents, and values are flat arrays of component names.
   * The top level of the tree is the root component, whose name is its type,
   * e.g. 'module'.
   * To traverse the tree:
   *  - access the base component name
   *  - iterate over its children
   *  - recursively do the same thing to each child component.
   *
   * Not all components in the component list need to place themselves into the
   * tree, but this means that they will not participate in file assembly.
   *
   * @return
   *  A tree of parentage data for components, as an array keyed by the parent
   *  component name, where each value is an array of the names of the child
   *  components. So for example, the list of children of component 'foo' is
   *  given by $tree['foo'].
   *
   * @throws \Exception
   *  Throws an exception if the return value of containingComponent() for a
   *  component is unrecognized as representing another component.
   */
  public function assembleContainmentTree() {
    // Lock the collection.
    $this->locked = TRUE;

    $this->tree = [];
    foreach ($this->components as $id => $component) {
      $containing_component_name = $this->getContainingComponentName($component);

      // An empty containing component means the component does not participate
      // in the containment tree.
      if (empty($containing_component_name)) {
        continue;
      }

      assert(isset($this->components[$containing_component_name]), "Containing component '$containing_component_name' given by '$id' is not a component ID.");

      $this->tree[$containing_component_name][] = $id;
    }

    return $this->tree;
  }

  /**
   * Gets the containing component ID for the given component.
   *
   * @param \DrupalCodeBuilder\Generator\GeneratorInterface $component
   *   The component to get the containing component for.
   *
   * @return string|null
   *   The component ID as given by self::getComponentKey(), or NULL if there
   *   is no containing component.
   *
   * @see \DrupalCodeBuilder\Generator\BaseGenerator::containingComponent()
   */
  protected function getContainingComponentName(GeneratorInterface $component): ?string {
    $id = $this->getComponentKey($component);
    $containing_component_token = $component->containingComponent();

    // Nothing to do if the token is empty.
    if (empty($containing_component_token)) {
      return NULL;
    }

    // Handle the root special case.
    if ($containing_component_token == '%root') {
      return $this->rootGeneratorId;
    }

    // Handle the plain requester as a special case rather than in the loop for
    // easier debugging.
    if ($containing_component_token == '%requester') {
      // TODO: consider whether this might go wrong when a component is
      // requested multiple times. Unlikely, as it tends to be containers
      // that are re-requested.
      return $this->requesters[$id];
    }

    // Handle a compound token.
    $token_pieces = explode(':', $containing_component_token);
    $current_id = $id;
    foreach ($token_pieces as $index => $token_piece) {
      if ($token_piece == '%requester') {
        // if requester, get requester of CURRENT ID.
        $current_id = $this->requesters[$current_id];
        continue;
      }

      if ($token_piece == '%self') {
        assert(($index == 0), 'Token %self may only be used in first path piece.');

        // Do nothing: $current_id already set before the loop.
        continue;
      }

      if ($token_piece == '%nearest_root') {
        assert(($index == 0), 'Token %nearest_root may only be used in first path piece.');

        $current_id = $this->requestRoots[$current_id];
        continue;
      }

      // Default case: token piece is a local name.
      assert(isset($this->localNames[$current_id][$token_piece]), sprintf(
        "Failed to get containing component for %s, local name '%s' not found for ID %s. Valid local names are: %s",
        $this->requestPaths[$id],
        $token_piece,
        $this->requestPaths[$current_id],
        implode(', ', array_keys($this->localNames[$current_id]))
      ));

      $current_id = $this->localNames[$current_id][$token_piece];
    }

    return $current_id;
  }

  /**
   * Gets the containment tree, with components represented by request paths.
   *
   * @return array
   *  An array of the same structure as getContainmentTree() returns, but
   *  instead of using object keys, every component is represented by its
   *  request path.
   */
  public function getContainmentTreeWithRequestPaths() {
    $tree_by_path = [];

    $tree = $this->getContainmentTree();

    foreach ($tree as $parent => $children) {
      foreach ($children as $child) {
        $tree_by_path[$this->requestPaths[$parent]][] = $this->requestPaths[$child];
      }
    }

    return $tree_by_path;
  }

  /**
   * Gets a component's children in the tree.
   *
   * @param GeneratorInterface $component
   *   The component to get children for.
   *
   * @return GeneratorInterface[]
   *   The child components, keyed by unique ID.
   */
  public function getContainmentTreeChildren(GeneratorInterface $component) {
    $component_id = $this->getComponentKey($component);

    $tree = $this->getContainmentTree();

    $child_ids = $tree[$component_id] ?? [];
    $return = [];
    foreach ($child_ids as $id) {
      $return[$id] = $this->components[$id];
    }

    return $return;
  }

  /**
   * Gets a component's collection of contained components in the tree.
   *
   * @param GeneratorInterface $component
   *   The component to get children for.
   *
   * @return \DrupalCodeBuilder\Generator\Collection\ContainedComponentCollection
   *   The collection object holding the child components.
   */
  public function getContainedComponentCollection(GeneratorInterface $component): ContainedComponentCollection {
    $children = $this->getContainmentTreeChildren($component);

    $contained_components = [];
    foreach ($children as $id => $child) {
      $content_type = $child->getContentType();
      $request_path = $this->requestPaths[$id];

      assert(!empty($content_type), sprintf('Contained child %s at %s must have a non-empty content type.',
        $child->getType(),
        $request_path
      ));

     $contained_components[$content_type][$request_path] = $child;
    }

    $component_request_path = $this->requestPaths[$this->getComponentKey($component)];
    return new ContainedComponentCollection($contained_components, $component_request_path);
  }

  /**
   * Gets the containment tree.
   *
   * This checks that the tree has been assembled already.
   *
   * @return array
   *   The tree assembled by assembleContainmentTree().
   *
   * @throws \LogicException
   *   Throws an exception if the tree has not yet been assembled.
   */
  private function getContainmentTree() {
    if (is_null($this->tree)) {
      throw new \LogicException("Tree has not yet been assembled.");
    }

    return $this->tree;
  }

  /**
   * Get the component that matches the type and merge tag, if any.
   *
   * @param $component
   *   The component to find a match for, not yet in the collection.
   * @param $requesting_component
   *   The component that is requesting the component to match.
   *
   * @return \DrupalCodeBuilder\Generator\GeneratorInterface|null
   *   Either a component that's in the collection and matches the given
   *   component, or NULL if nothing was found.
   */
  public function getMatchingComponent(GeneratorInterface $component, ?GeneratorInterface $requesting_component): ?GeneratorInterface{
    if (is_null($requesting_component)) {
      return NULL;
    }

    $component_merge_tag = $this->getComponentMergeTag($component, $requesting_component);

    if (empty($component_merge_tag)) {
      return NULL;
    }

    // We've not added $component yet (and might not), so we don't know have
    // any data about it. So we need to use the requesting component to get the
    // closest requesting root.
    $requesting_component_id = $this->getComponentKey($requesting_component);
    if (isset($this->roots[$requesting_component_id])) {
      // If the requesting component is a root, it's obviously the closest
      // requesting root. But getClosestRequestingRootComponent() would not
      // give us this, as root's closest requester is not itself.
      $closest_requesting_root_id = $requesting_component_id;
    }
    else {
      $closest_requesting_root_id = $this->getComponentKey($this->getClosestRequestingRootComponent($requesting_component));
    }

    if (isset($this->mergeTags[$closest_requesting_root_id][$component->getType()][$component_merge_tag])) {
      $matching_component_id = $this->mergeTags[$closest_requesting_root_id][$component->getType()][$component_merge_tag];
      return $this->components[$matching_component_id];
    }
    else {
      return NULL;
    }
  }

  /**
   * Returns the closest requester that is a root component.
   *
   * This may be called before the collection is complete.
   *
   * @param GeneratorInterface $component
   *   The component to get children for.
   *
   * @return
   *   The root component.
   *
   * @throws \LogicException
   *   Throws an exception if called with the root component, as in that case
   *   the answer does not make sense.
   */
  public function getClosestRequestingRootComponent(GeneratorInterface $component): RootComponent {
    if ($this->getComponentKey($component) === $this->rootGeneratorId) {
      throw new \LogicException("ComponentCollection::getClosestRequestingRootComponent() may not be called with the root component.");
    }

    $component_id = $this->getComponentKey($component);

    $closest_requesting_root_id = $this->requestRoots[$component_id];
    return $this->components[$closest_requesting_root_id];
  }

  /**
   * Gets the merge tag for a component, handling token replacement.
   *
   * A component's merge tag MUST be obtained via this method.
   *
   * @param \DrupalCodeBuilder\Generator\GeneratorInterface $component
   *   The component.
   * @param \DrupalCodeBuilder\Generator\GeneratorInterface|null $requesting_component
   *   (optional) The requester of the component.
   *
   * @return string
   *   The merge tag, with the '%requester' token replaced with the data address
   *   of the requester.
   */
  protected function getComponentMergeTag(GeneratorInterface $component, ?GeneratorInterface $requesting_component): string {
    $component_merge_tag = $component->getMergeTag();

    if (empty($component_merge_tag)) {
      return '';
    }

    if (str_contains($component_merge_tag, '%requester')) {
      if (!$requesting_component) {
        throw new \LogicException("Merge tag {$component_merge_tag} contains token but there is nothing to replace it.");
      }

      // The merge tag can contain the '%requester' token which needs to be
      // replaced.
      $component_merge_tag = str_replace('%requester', $requesting_component->component_data->getAddress(), $component_merge_tag);
    }

    return $component_merge_tag;
  }

  /**
   * Dumps the data structures of the collection for debugging.
   */
  public function dumpStructure() {
    dump("Requesters:");
    dump($this->requesters);

    dump("Request roots:");
    dump($this->requestRoots);

    dump("Request paths:");
    dump($this->requestPaths);

    dump("Local names:");
    dump($this->localNames);

    if (isset($this->tree)) {
      dump("Containment tree:");
      dump($this->tree);
    }
  }

}
