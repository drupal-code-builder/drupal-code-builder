<?php

/**
 * @file
 * Contains general generator classes, abstract parents, etc.
 */

namespace ModuleBuider\Generator;

/**
 * Abstract base Generator for components.
 *
 * (This named to distinguish it from the Base Task clas.)
 *
 * The generator process works over three phases:
 *  - a tree of generators is gathered
 *  - a list of file info is built up, with each generator allowed to contribute
 *  - the file info is processed and returned for the caller to output
 *
 * The generator system works by starting with a particular generator for a
 * given component (e.g., 'module'), and then adding generators this one
 * requests, recursing this process into each new generator and building a tree
 * down from the original one. This is achieved by each generator class
 * implementing requiredComponents() to return its child components. The process ends
 * when the added generators are themselves one that return no sub-components.
 *
 * So for example, the caller requests a 'module' component. This causes
 * the entry point to the system, Generate::generateComponent() to
 * instantiate a module generator, which is then interrogated for its
 * subcomponents. It returns, say, that it needs:
 *  - a README file
 *  - a .info file
 *  - hooks, which is an abstract component which represents the module's code
 *    which we are to generate.
 * The first two components are terminal: they do not have subcomponents of
 * their own. The hooks component however adds more generators: a code file
 * generator for each file it needs for the hooks it has been requested to
 * generate. The code files are then interrogated, and return no subcomponents:
 * the gathering process is thus complete.
 *
 * The end result is a flat list of components, keyed by component names. Each
 * component has the data it needs to operate.
 *
 * Once we have this, we iterate over it to assemble a tree structure, which
 * tells us which components contains which other components. For example, the
 * module code file contains functions and hook implementations.
 *
 * Once we have this, we then recurse once more into it, this time building up
 * an array of file info that we pass by reference (this it can be altered
 * as well as added to, though generator order is TBFO). Generator classes that
 * wish to add files should override collectFiles() to add them.
 *
 * Finally, we assemble the file info into filenames and code, ready for the
 * initiator of the whole process (e.g., Drush, Drupal UI) to output them in an
 * appropriate way. This is done in the starting generator's assembleFiles().
 *
 * There are three distinc hierarchies at work here:
 *  - A plain PHP class hierarchy, which is just there to allow us to make use
 *    of method inheritance. So for instance, ModuleCodeFile inherits from File.
 *    This is just for code re-use.
 *  - A hierarchy formed by components that request other components in turn.
 *    This fans out from the initially requested root component, e.g. 'module'.
 *    This is based on how things fit together conceptually: a module may need
 *    hooks, and a hook implementation needs a file it can go in. The same
 *    component may appear at different parts of the request hierarchy.
 *  - The tree of components that is assembled prior to building code. This is
 *    purely to do with containment. Thus, a code file contains its functions.
 *    This comes into play when a component is building its code, and may
 *    interrogate its child components in this hierarchy to have them add to
 *    what it provides.
 *
 * Rough conceptual hierarchy:
  - component generators/file generators (NOT class hierarchy: processing chain!)
   - module
     - codeModule ---> hooks & callbacks
       - codeModuleFile
     - info
     - readme
     - tests
   - theme
     - codeTheme ---> theme functions
     - codeThemeFile
     - info
     - readme
   - profile
     - codeProfile --> hooks
     - info
     - readme
   - plugin ??
     - goes beneath module somehow??????

   the initial request causes:
     - figuring out which generator to start up
     - component generator figures out:
       - subcomponents (hooks, info file, plugins, tests)
       - each subcomponent recurses and eventually gets to a file generator.
 *
 * @see Generate::generateComponent()
 */
abstract class BaseGenerator {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
   */
  public $name;

  /**
   * Reference to the Generate task handler.
   *
   * This should be used to access the environment, and to call getGenerator().
   */
  public $task;

  /**
   * Reference to the base component of this component.
   *
   * This should be used to access the component data.
   */
  public $base_component;

  /**
   * The base component's flat list of components.
   *
   * This is keyed by the name of the component name. Values are the
   * instantiated component generators.
   *
   * (This is only present on the base component.)
   *
   * TODO: It might be cleaner to add an abstract BaseComponent class, but then
   * we'd lose the flexibility of base components being usable as by other
   * components, or anything being requestable as a base.
   */
  public $components = array();

  /**
   * The data for the component.
   *
   * On the base component (e.g., 'Module'), this is the entirety of the data
   * requested by the user.
   *
   * On other components (e.g., 'Routing'), this contains data from the request
   * for the component. Properties will depend on the class.
   *
   * TODO: split this var into two somehow??!
   */
  public $component_data = array();

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component. This is often the same as the type
   *   (e.g., 'module', 'hooks') but in the case of types used multiple times
   *   this will be a unique identifier.
   * @param $component_data
   *   (optional) An array of data for the component.
   *   While each component will have its own array of data, components may also
   *   need to access the data of the root component. For this, use
   *   $task->getRootGenerator() (for now!).
   *   TODO: check whether components really need to do this, as removing this
   *   would simplify things!
   */
  function __construct($component_name, $component_data = array()) {
    $this->name = $component_name;
    $this->component_data = $component_data;
  }

  /**
   * Get the root component's data.
   *
   * This can be used in circumstances where it's not known whether the current
   * component is the base or not.
   *
   * @return
   *  The base component.
   */
  function getRootComponentData() {
    // Get the root component from the Task, which is the autority on this.
    $root_component = $this->task->getRootGenerator();

    return $root_component->component_data;
  }

  /**
   * Returns the flat list of components, as assembled by assembleComponentList().
   */
  function getComponentList() {
    $base = $this->task->getRootGenerator();
    return $base->components;
  }

  /**
   * Get the list of required components this generator.
   *
   * This calls itself recursively on the returned components, so that any added
   * component may in turn add more.
   *
   * Generator classes should implement requiredComponents() to return the list
   * of component types they require, possibly depending on incoming data.
   *
   * Obviously, it's important that eventually this process terminate with
   * generators that return an empty array for requiredComponents().
   *
   * @return
   *  None. This should set an array of subcomponent generators on the property
   *  $this->components.
   */
  public function assembleComponentList() {
    // Get the base component to add the generators to it.
    $base_component = $this->task->getRootGenerator();

    // Get the required subcomponents.
    $subcomponent_info = $this->requiredComponents();

    // Instantiate each one (if not already done), and recurse into it.
    foreach ($subcomponent_info as $component_name => $data) {
      // The $data may either be a string giving a class name, or an array.
      if (is_string($data)) {
        $component_type = $data;
        $component_data = array();
      }
      else {
        $component_type = $data['component_type'];
        $component_data = $data;
      }

      $generator = $this->task->getGenerator($component_type, $component_name, $component_data);

      // If the component is already present, merge any additionally requested
      // data with the existing component and then continue to the next one.
      if (isset($base_component->components[$component_name])) {
        if (!empty($component_data)) {
          $base_component->components[$component_name]->mergeComponentData($component_data);
        }

        continue;
      }

      // Add the new component to the master array of components on the base.
      $base_component->components[$component_name] = $generator;

      // Recurse into the subcomponent.
      $generator->assembleComponentList();
    }
  }

  /**
   * Get this component's required components.
   *
   * For example, a module component requires hooks, an info file, and a readme
   * file. Hooks in turn require a varying number of files, determined by the
   * incoming module data.
   *
   * @return
   *  An array of subcomponents which the current generator requires.
   *  Each item's key is a name for the component. The name of a component that
   *  has already been requested by another generator may be present: the data
   *  array if present will be merged with that of the existing component.
   *  Each value is either:
   *    - the type for the component, suitable for passing to
   *      Generate::getGenerator() to get the generator class.
   *    - an array of data for the component. This must include a properties
   *      'component_type', which gives the type for the component as above.
   *      Further array properties are determined by the component class's
   *      __construct().
   *      (Note that if the component has previously been requested, this array
   *      is ignored on later components! This is because -- so far! -- no
   *      components that get requested multiple times require this!)
   */
  protected function requiredComponents() {
    return array();
  }

  /**
   * Merge data from additional requests of a component.
   */
  protected function mergeComponentData($additional_component_data) {
    $this->component_data = array_merge_recursive($this->component_data, $additional_component_data);
  }

  /**
   * Assemble a tree of components, grouped by what they contain.
   *
   * For example, a code file contains its functions; a form component
   * contains the handler functions.
   *
   * This iterates over the flat list of components assembled by
   * assembleComponentList(), and re-assembles it as a tree.
   *
   * The tree is an array of parentage data, where keys are the names of
   * components that are parents, and values are flat arrays of component names.
   * To traverse the tree:
   *  - access the base component name
   *  - iterate over its children
   *  - recursively do the same thing to each child component.
   *
   * Not all components in the component list need to place themselves into the
   * tree, but this means that they will not participate in file assembly.
   */
  public function assembleComponentTree() {
    $tree = array();
    foreach ($this->components as $name => $component) {
      $parent_name = $component->containingComponent();
      if (!empty($parent_name)) {
        $tree[$parent_name][] = $name;
      }
    }

    $this->tree = $tree;
  }

  /**
   * Return this component's parent in the component tree.
   *
   * @return
   *  The name of this component's parent in the tree, or NULL if this component
   *  is either the base, or does not participate in the tree.
   *
   * @see assembleComponentTree()
   */
  function containingComponent() {
    return NULL;
  }

  /**
   * Work through the component tree, gathering contained components.
   *
   * This allows, for example, a module code file component to collect the
   * functions it contains.
   *
   * This function is called recursively. Components that wish to do something
   * here should override this.
   */
  public function assembleContainedComponents() {
    $base_component = $this->task->getRootGenerator();

    // If we're not in the tree, we have nothing to say here and bail.
    if (!isset($base_component->tree[$this->name])) {
      return;
    }

    $component_list = $this->getComponentList();

    // Iterate over our children elements.
    $children = $base_component->tree[$this->name];

    // Call assembleContainedComponentsHelper().
    $this->assembleContainedComponentsHelper($children);

    foreach ($children as $child_name) {
      // Get the child component.
      $child_component = $component_list[$child_name];

      // Recurse into it.
      $child_component->assembleContainedComponents();
    }
  }

  /**
   * Helper for assembleContainedComponents().
   *
   * Allows components to do the work of assembling their contained components
   * without having to override assembleContainedComponents().
   *
   * TODO: AARGH needs better name!
   */
  function assembleContainedComponentsHelper($children) {
    // Base does nothing.
  }

  /**
   * Collect data for files, recursing into each subcomponent.
   *
   * It's safe for subclasses to use this too, as it acts on a generator's own
   * array of subcomponents.
   *
   * Generators that have some code to output should override this to output it!
   *
   * It's up to the caller of this on the root generator to figure out how
   * to output the files at the end of the process: eg, drush prints them to
   * the terminal or writes them; the Drupal UI shows them in form textareas.
   *
   * @param
   *  An array of file info, passed by reference. Components should add files
   *  to this, but may also alter what has already been generated.
   *  The keys are machine names, probably (!) arbitrary. Values are:
   *  - path: The path to the file, relative to the future module folder.
   *  - filename: The file name.
   *  - body: An array of pieces to assemble in order to form the body of the
   *    file. These can be single lines, or larger chunks: they will be joined
   *    up by assembleFiles(). The array may be keyed numerically, or the keys
   *    can be meaningful to the generator class: they are immaterial to the
   *    caller.
   *  - join_string: The string to join the body pieces with. If the body is an
   *    array of single lines, you probably want to use "\n". If you have chunks
   *    it makes more sense for each chunk to contain its own linebreaks
   *    including the terminal one.
   *  - contains_classes: A boolean indicating that this file contains one or
   *    more classes, and thus should be declared in the component's .info file.
   */
  function collectFiles(&$files) {
    foreach ($this->components as $generator) {
      $generator->collectFiles($files);
    }
  }

  /**
   * Assemble file info into filename and code.
   *
   * @param $files
   *  An array of file info, as compiled by collectFiles().
   *
   * @return
   *  An array of files ready for output. Keys are the filepath and filename
   *  relative to the module folder (eg, 'foo.module', 'tests/module.test');
   *  values are strings of the contents for each file.
   */
  function assembleFiles($files) {
    $return = array();

    foreach ($files as $file_id => $file_info) {
      if (!empty($file_info['path'])) {
        $filepath = $file_info['path'] . '/' . $file_info['filename'];
      }
      else {
        $filepath = $file_info['filename'];
      }

      $code = implode($file_info['join_string'], $file_info['body']);

      $return[$filepath] = $code;
    }

    return $return;
  }

}
