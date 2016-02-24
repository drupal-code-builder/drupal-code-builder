<?php

/**
 * @file
 * Contains ModuleBuilder\Generator\BaseGenerator.
 */

namespace ModuleBuilder\Generator;

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
 * @section sec_gather_generators Gathering generators
 * The generator system starts with a particular generator for a
 * given component (e.g., 'module'), and then adding generators this one
 * requests, recursing this process into each new generator and building a tree
 * down from the original one. This is done by assembleComponentList(), which is
 * recursively called on each generator class. Each class implements
 * requiredComponents() to return a list of child components. The process ends
 * when the added generators are themselves one that return no sub-components.
 *
 * So for example, the caller requests a 'module' component. This causes the
 * entry point to the system, ModuleBuilder\Task\Generate::generateComponent(),
 * to instantiate a module generator, which is then interrogated for its
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
 * @section sec_assemble_tree Assemble component tree
 * The list of components is iterated over in assembleComponentTree() to
 * assemble a tree structure of the components, where child components are those
 * that are contained by their parents. For example, the module code file
 * contains functions and hook implementations.
 *
 * This tree is iterated over again in buildComponentContentsIterative() to allow
 * file components in the tree to gather data from their child components.
 *
 * @section sec_assemble_file_info Collect file info
 * We then recurse down the tree in collectFiles(), building up
 * an array of file info that we pass by reference (this it can be altered
 * as well as added to, though generator order is TBFO). Generator classes that
 * wish to output a file should implement getFileInfo().
 *
 * @section sec_assemble_files Assemble files
 * Finally, we assemble the file info into filenames and code, ready for the
 * initiator of the whole process (e.g., Drush, Drupal UI) to output them in an
 * appropriate way. This is done in Generate::assembleFiles().
 *
 * There are three distinct hierarchies at work here:
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
   * (This is only present on the root component.)
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
   *   An array of data for the component.
   *   While each component will have its own array of data, components may also
   *   need to access the data of the root component. For this, use
   *   $task->getRootGenerator() (for now!).
   *   TODO: check whether components really need to do this, as removing this
   *   would simplify things!
   * @param $generate_task
   *   The Generate task object.
   * @param $root_generator
   *   The root Generator object.
   */
  function __construct($component_name, $component_data, $generate_task, $root_generator) {
    $this->name = $component_name;
    $this->component_data = $component_data;

    // Each generator needs a link back to the factory to be able to make more
    // generators, and also so it can access the environment.
    // TODO: remove these and go via the factory instead, to simplify class
    // debug output.
    $this->task = $generate_task;
    $this->base_component = $root_generator;
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
   *
   * @see assembleComponentList()
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
   * Defines how this component should be handled when requested directly.
   *
   * @return
   *  A string which defines how this component should be instantiated when
   *  it's requested in incoming component data. One of:
   *  - 'singleton': The component may exist only once, and should be created
   *    with its name set to the component type.
   *  - 'repeat': The component may exist in multiple copies, and one should be
   *    created for each value in the component data.
   *  - 'group': The component should be instantiated once, with all the values
   *    set in its data.
   *
   * @see RootComponent::processComponentData()
   */
  public static function requestedComponentHandling() {
    return 'repeat';
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
   * Allow file components to gather data from their child components.
   *
   * This allows, for example, a module code file component to collect the
   * functions it contains.
   *
   * Components wishing to participate in this should override
   * buildComponentContents().
   *
   * @param $components
   *  The array of components.
   * @param $tree
   *  The tree array.
   *
   * @return
   *  An array of lines for this component's content.
   */
  function buildComponentContentsIterative($components, $tree) {
    $children_contents = array();

    // Allow each of our children to do the same as this to collect its own
    // children.
    if (!empty($tree[$this->name])) {
      foreach ($tree[$this->name] as $child_name) {
        $child_component = $components[$child_name];
        $children_contents[$child_name] = $child_component->buildComponentContentsIterative($components, $tree);
      }
    }

    // Produce output for the current component, using data collected from the
    // children. E.g. a PHP file concatenates and wraps the output of its child
    // function components.
    return $this->buildComponentContents($children_contents);
  }

  /**
   * Collects contents for this file.
   *
   * @param $children_contents
   *  An array of the content that the child components returned.
   *
   * @return
   *  An array of data for this component's content. The nature of this depends
   *  on the intended parent: for example PHPFile expects an array of code lines
   *  whereas YMLFile expects an array of data to be rendered into Yaml.
   */
  function buildComponentContents($children_contents) {
    // Base does nothing.
    return array();
  }

  /**
   * Allow components to alter the files prior to output.
   */
  public function filesAlter(&$files, $component_list) {
    // Base class does nothing.
  }

}
