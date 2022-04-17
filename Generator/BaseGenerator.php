<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Generator\Collection\ComponentCollection;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\GeneratorDefinition;
use DrupalCodeBuilder\Definition\LazyGeneratorDefinition;
use DrupalCodeBuilder\Exception\MergeDataLossException;
use DrupalCodeBuilder\File\DrupalExtension;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use DrupalCodeBuilder\Task\Generate\ComponentClassHandler;
use MutableTypedData\Definition\DefaultDefinition;
use MutableTypedData\Definition\OptionDefinition;
use MutableTypedData\Data\DataItem;

/**
 * Abstract base Generator for components.
 *
 * (This named to distinguish it from the Base Task clas.)
 *
 * A generator represents a component of code to generate. This can be small,
 * like a class method or a permission, or something that contains other
 * components such as a whole module or a permissions YAML file. It can also be
 * something that cuts across the physical files that will be written, such as
 * an admin settings form, which will contain among other things a single
 * permission.
 *
 * Generator classes do two things:
 *
 * - Define a data structure for that class. This is all done in static methods,
 *   without instantiating the class. The Generate task accesses this definition
 *   on a RootComponent class, and then a UI uses that definition to collect the
 *   data from the user.
 * - Generate the code from the user data. Each generator may return content and
 *   also cause further generators to be instantiated as requirements. The
 *   Generate task and its helpers assemble the content from the generators into
 *   the contents of the files which are returned to the UI.
 *
 * @section data_definition Data definition
 *
 * RootComponent classes implement DefinitionProviderInterface to return their
 * data definition. This hands over to getGeneratorDataDefinition(), which
 * assembles the full definition for the root component.
 *
 * Some complex properties in the definition get their own properties from
 * another generator: for example, the Module generator defines an admin
 * settings form complex property, and the child properties for that are
 * defined in the AdminSettingsForm generator. This is done with
 * static::getLazyDataDefinitionForGeneratorType().
 *
 * All generator classes use class inheritance to build the definition: for
 * example, this hiearchy reduces code repetition:
 *  - BaseGenerator
 *  - File
 *  - PhpClassFile
 *  - PhpClassFileWithInjection
 *  - Form
 *
 * Property definitions are retrieved lazily for various reasons: see
 * LazyGeneratorDefinition for details.
 *
 * @section Code generation
 *
 * The generate process works over three phases:
 *  - a tree of generators is gathered
 *  - a list of file info is built up, with each generator allowed to contribute
 *  - the file info is processed and returned for the caller to output
 *
 * @section sec_gather_generators Gathering generators
 * The generator system starts with a particular generator for a
 * given component (e.g., 'module'), and then adding generators this one
 * requests, recursing this process into each new generator and building a tree
 * down from the original one. This is done by Generate::assembleComponentList()
 * which gathers all the required components in a cascade. Each class implements
 * requiredComponents() to return a list of child components. The process ends
 * when the added generators are themselves one that return no sub-components.
 *
 * So for example, the caller requests a 'module' component. This causes the
 * entry point to the system, DrupalCodeBuilder\Task\Generate::generateComponent(),
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
 * @see Generate::generateComponent()
 */
abstract class BaseGenerator implements GeneratorInterface {

  /**
   * The data type for this generator's data definition.
   *
   * @var string
   */
  protected static $dataType = 'complex';

  /**
   * The generator type.
   *
   * This is the unqualified class name without the version suffix.
   */
  public $type;

  /**
   * The data item for the component.
   *
   * On the base component (e.g., 'Module'), this is the entirety of the data
   * requested by the user.
   *
   * On other components (e.g., 'Routing'), this contains data from the request
   * for the component. Properties will depend on the class.
   */
  public $component_data;

  /**
   * The class handler.
   *
   * @var \DrupalCodeBuilder\Task\Generate\ComponentClassHandler
   */
  protected $classHandler;

  /**
   * Boolean to indicate whether this component already exists.
   *
   * @see detectExistence()
   */
  protected $exists = FALSE;

  /**
   * Data about the existing component.
   *
   * Type varies for different generator classes.
   *
   * @var mixed
   */
  protected $existing = [];

  /**
   * Constructor method; sets the component data.
   *
   * @param \MutableTypedData\Data\DataItem $component_data
   *   An array of data for the component.
   */
  function __construct(DataItem $component_data) {
    $this->component_data = $component_data;

    // Set the type. This is the short class name without the numeric version
    // suffix.
    $class = get_class($this);
    $class_pieces = explode('\\', $class);
    $short_class = array_pop($class_pieces);
    $this->type = preg_replace('@\d+$@', '', $short_class);
  }

  /**
   * Sets the class handler.
   *
   * @param \DrupalCodeBuilder\Task\Generate\ComponentClassHandler $class_handler
   *   The class handler.
   */
  public function setClassHandler(ComponentClassHandler $class_handler) {
    $this->classHandler = $class_handler;
  }

  /**
   * Gets the address of this component's data.
   */
  public function getAddress() {
    return $this->component_data->getAddress();
  }

  /**
   * Gets the type for a class.
   *
   * @param string $class
   *   The fully-qualified class name.
   *
   * @return string
   *   The component type.
   */
  protected static function deriveType(string $class) :string {
    $class_pieces = explode('\\', $class);
    $short_class = array_pop($class_pieces);
    return preg_replace('@\d+$@', '', $short_class);
  }

  /**
   * Gets the data definition for a given component type.
   *
   * This is for use within getPropertyDefinition() and related methods that
   * build up the overall definition for a root component using different
   * generator classes.
   *
   * For standalone data used in static::requiredComponents(), use
   * ComponentClassHandler::getStandaloneComponentPropertyDefinition().
   *
   * TODO: move this to a task handler?
   *
   * @param string $component_type
   * @param string $data_type
   *
   * @return \DrupalCodeBuilder\Definition\PropertyDefinition
   */
  protected static function getLazyDataDefinitionForGeneratorType(string $component_type, string $data_type = NULL): PropertyDefinition {
    $class_handler = \DrupalCodeBuilder\Factory::getContainer()->get('Generate\ComponentClassHandler');
    $generator_class = $class_handler->getGeneratorClass($component_type);

    return $generator_class::getGeneratorDataDefinition($data_type);
  }

  /**
   * Gets the data definition for this generator without the properties.
   *
   * This must be called on the generator class itself. Use
   * static::getLazyDataDefinitionForGeneratorType() from a different generator
   * class.
   *
   * TODO: this should replace getPropertyDefinition()!
   *
   * @param string $data_type
   *   (optional) The data type, to override the data type defined by the
   *   Generator class. This is necessary in cases where the property needs to
   *   be a simple type such as boolean or string, while the generator for
   *   that property is complex because it has internal properties.
   *
   * @return \DrupalCodeBuilder\Definition\LazyGeneratorDefinition
   *   The property definition.
   */
  public static function getGeneratorDataDefinition(string $data_type = NULL): LazyGeneratorDefinition {
    // Check this isn't getting called on BaseGenerator.
    assert(static::class != __CLASS__);

    $component_type = static::deriveType(static::class);

    $data_type = $data_type ?? static::$dataType;

    $definition = LazyGeneratorDefinition::createFromGeneratorType($component_type, $data_type);
    return $definition;
  }

  /**
   * Sets the properties on the generator data definition.
   *
   * The creation of the complete data definition is split between this and
   * self::getGeneratorDataDefinition() in order to prevent various issues:
   * see docs for LazyGeneratorDefinition for details.
   *
   * @param \DrupalCodeBuilder\Definition\PropertyDefinition $definition
   *   The data definition.
   */
  public static function setProperties(PropertyDefinition $definition): void {
    // Yet Another Sodding Shim.
    $temporary_definition = static::getPropertyDefinition();

    $definition->setProperties($temporary_definition->getProperties());
  }

  /**
   * Gets the data definition for this component.
   *
   * This shouldn't set things on its root data such as required, cardinality,
   * or label, as these may depend on where it's used.
   *
   * Use static::getLazyDataDefinitionForGeneratorType() to use the definition
   * from one generator inside another's.
   *
   * @return \DrupalCodeBuilder\Definition\PropertyDefinition
   *   The data definition.
   */
  public static function getPropertyDefinition() :PropertyDefinition {
    $type = static::deriveType(static::class);

    $definition = GeneratorDefinition::createFromGeneratorType($type, 'complex');

    // Add the basic properties.
    // TODO: put these in setProperties() instead, but can't yet, probably
    // because of standalone data for requested components.
    $definition->addProperties([
      'root_component_name' => PropertyDefinition::create('string')
        ->setAcquiringExpression("getRootComponentName(requester)"),
      'containing_component' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      'component_base_path' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);

    return $definition;
  }

  public function isRootComponent(): bool {
    return FALSE;
  }

  /**
   * Gets the type of the component.
   *
   * @return string
   *   The type of the component. This is the same value that is used in
   *   request data, that is, the short class name without any Drupal core
   *   version suffix.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Provides the merge tag for the component.
   *
   * This is an arbitrary string which marks components of this type for
   * merging. When a new component is under the same root, has the same type,
   * and the same merge tag as an existing component, it is merged in rather
   * added.
   *
   * @see ComponentCollector::getComponentsFromData()
   * @see ComponentCollection::getMatchingComponent()
   *
   * @return string
   *   The merge tag.
   */
  public function getMergeTag() {
    return NULL;
  }

  /**
   * Get the value of a component property.
   *
   * @param string $name
   *   The property name.
   *
   * @return mixed
   *   The value.
   */
  public function getComponentDataValue($name) {
    if (!isset($this->component_data[$name])) {
      throw new \Exception(strtr("Property @name not found in data for @type.", [
        '@name' => $name,
        '@type' => get_class($this),
      ]));
    }

    return $this->component_data[$name];
  }

  /**
   * Get this component's required components.
   *
   * For example, a module component requires hooks, an info file, and a readme
   * file. Hooks in turn require a varying number of files, determined by the
   * incoming module data.
   *
   * @return array
   *  An array of subcomponents which the current generator requires.
   *  Each item's key is the local name for the component, which must be unique
   *  within both this array and the components that this component spawns from
   *  properties (note that this is not quite the same as the property names,
   *  as properties with multiple cardinality receive a delta).
   *  Each value an array of data for the component. This must include a
   *  property 'component_type', which gives the type for the component as
   *  above. Further array properties are determined by the component class's
   *  property definition.
   *
   * @see Generate::assembleComponentList()
   */
  public function requiredComponents(): array {
    return [];
  }

  /**
   * Merge data from additional requests of a component.
   *
   * @param \MutableTypedData\Data\DataItem $additional_component_data
   *   The new component data to merge in.
   *
   * @return bool
   *   Boolean indicating whether any data needed to be merged: TRUE if so,
   *   FALSE if nothing was merged because all values were the same.
   *
   * @throws \DrupalCodeBuilder\Exception\MergeDataLossException
   *   Throws an exception if the merge would cause data from $other to be
   *   discarded.
   */
  public function mergeComponentData(DataItem $additional_component_data) {
    try {
      $differences_merged = $this->component_data->merge($additional_component_data);
    }
    catch (MergeDataLossException $e) {
      // TODO: add more detail here.
      throw $e;
    }

    return $differences_merged;
  }

  /**
   * Detect whether this component exists in the given module files.
   *
   * Components should set $this->exists on themselves if they find they already
   * exist in the module.
   *
   * @param \DrupalCodeBuilder\File\DrupalExtension $extension
   *  An extension object for the existing extension.
   */
  public function detectExistence(DrupalExtension $extension) {
    // Do nothing by default.
  }

  /**
   * Defines this component's parent in the containment tree.
   *
   * This is called by the ComponentCollection when it assembles a tree of
   * components by containment.
   *
   * @return string|NULL
   *  A string that defines another component, using either a token, or a token
   *  and a chain of local names from the component that the token represents.
   *  The following patterns are allowed:
   *   - '%root': Represents the root component.
   *   - '%requester': Represents the component that requested this component
   *      either via properties or requiredComponents().
   *   - '%requester:PATH': Represents a path of local names from the
   *     component that requested this component. The PATH may consist of a
   *     single local name, or a series of local names separated by a ':'.
   *     For example, '%requester:foo' gets the component 'foo' that was also
   *     spawned by this component's spawner. '%requester:foo:bar' gets the
   *     component spawned by the 'foo' component.
   *   - '%self:PATH': Represents a path of local names from this component.
   *   - '%nearest_root:PATH': Represents a path of local names from the
   *     component's nearest requesting root.
   *   - NULL if this component is either the base, or does not participate in
   *     the tree.
   *
   * @see ComponentCollection::assembleComponentTree()
   */
  function containingComponent() {
    return $this->component_data->containing_component->value ?? NULL;
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
   * @param \DrupalCodeBuilder\Generator\Collection\ComponentCollection $component_collection
   *   The component collection.
   *
   * @return
   *  An array of data for this component's content, in the same form as the
   *  return of buildComponentContents().
   */
  function buildComponentContentsIterative(ComponentCollection $component_collection) {
    $children_contents = [];

    // Allow each of our children to do the same as this to collect its own
    // children.
    foreach ($component_collection->getContainmentTreeChildren($this) as $id => $child_component) {
      $child_contents = $child_component->buildComponentContentsIterative($component_collection);
      foreach ($child_contents as $key => $contents) {
        $children_contents[$id . ':' . $key] = $contents;
      }
    }

    // Produce output for the current component, using data collected from the
    // children. E.g. a PHP file concatenates and wraps the output of its child
    // function components.
    return $this->buildComponentContents($children_contents);
  }

  /**
   * Collects contents for this component.
   *
   * @param $children_contents
   *  An array of the content that the child components returned.
   *
   * @return
   *  The content for this component, destined for the containing component to
   *  make use of. This is an array with the following keys:
   *  - 'role': A string indicating to the containing component how to use this
   *    content.
   *  - 'content': The content itself. The nature of this depends on the
   *    intended parent and the role: for example PHPFile 'function' role
   *    expects an array of code lines whereas YMLFile expects an array of data
   *    to be rendered into Yaml.
   */
  protected function buildComponentContents($children_contents) {
    // Base does nothing.
    return [];
  }

  /**
   * Filters an array of child contents by role.
   *
   * Helper for buildComponentContents().
   *
   * @param $contents
   *  The array of contents as returned by an implementation of
   *  buildComponentContents().
   * @param $role
   *  A role name, as used in the 'role' property of the $contents array.
   *
   * @return
   *  An array of the 'content' data of the given array items that matched the
   *  role. The array key is in MOST cases the same key as the the given array
   *  item.
   */
  protected function filterComponentContentsForRole($contents, $role) {
    $return = [];
    foreach ($contents as $key => $item) {
      $return_key = $key;

      if ($item['role'] == $role) {
        // Special case: function contents need to know the function name
        // for merging PHP files.
        // We have to put this as the key, as there's no other way to include
        // data other than the contents. Function names should be unique!
        if ($role == 'function') {
          assert(isset($item['function_name']));

          $return_key = $item['function_name'];

          assert(!isset($return[$return_key]));
        }

        $return[$return_key] = $item['content'];
      }
    }
    return $return;
  }

  /**
   * Groups the array of component contents by their role.
   *
   * @param $contents
   *  The array of contents as returned by an implementation of
   *  buildComponentContents().
   *
   * @return
   *  An array of the grouped items. The keys are all the values of the 'role'
   *  item in the contents array. The values are arrays keyed by the keys of the
   *  contents array, whose values are the content items. For example:
   *    - role1 =>
   *      - itemkeyA => item content
   *      - itemkeyB => item content
   *    - role2 =>
   *      - itemkeyC => item content
   */
  protected function groupComponentContentsByRole($contents) {
    $return = [];
    foreach ($contents as $key => $item) {
      $return[$item['role']][$key] = $item['content'];
    }
    return $return;
  }

}
