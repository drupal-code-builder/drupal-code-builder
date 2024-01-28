<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyListInterface;
use DrupalCodeBuilder\Generator\Collection\ComponentCollection;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\MergingGeneratorDefinition;
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
 * data definition. This uses MergingGeneratorDefinition with that class, which in turn
 * allows the generator class to add properties in addToGeneratorDefinition().
 *
 * Some complex properties in the definition get their own properties from
 * another generator: for example, the Module generator defines an admin
 * settings form complex property, and the child properties for that are
 * defined in the AdminSettingsForm generator. This is done with
 * MergingGeneratorDefinition::createFromGeneratorType().
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
 * MergingGeneratorDefinition for details.
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
 * This tree is iterated over again in collectComponentContents() to allow
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
   * Collection of contained components.
   *
   * @var \DrupalCodeBuilder\Generator\Collection\ContainedComponentCollection
   */
  protected $containedComponents = [];

  /**
   * The class handler.
   *
   * @var \DrupalCodeBuilder\Task\Generate\ComponentClassHandler
   */
  protected $classHandler;

  /**
   * The existing extension, if applicable.
   *
   * @var \DrupalCodeBuilder\File\DrupalExtension
   */
  protected $extension;

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
   * Get the data type for the data definition of this generator.
   *
   * @return string
   *   The data type set in static::$dataType.
   */
  public static function getDefinitionDataType(): string {
    // The generator class data type can't be simple, as then the component
    // generator can't have properties. See DeferredGeneratorDefinition.
    assert(static::$dataType != 'boolean');
    assert(static::$dataType != 'string');
    return static::$dataType;
  }

  /**
   * Adds to the defintion for this generator.
   *
   * This shouldn't set things on its root data such as required, cardinality,
   * or label, as these may depend on where it's used.
   *
   * @param \DrupalCodeBuilder\Definition\PropertyDefinition $definition
   *   The basic definition.
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    // Add the basic properties.
    $definition->addProperties([
      'root_component_name' => PropertyDefinition::create('string')
        ->setAcquiringExpression("getRootComponentName(requester)"),
      'containing_component' => PropertyDefinition::create('string')
        ->setInternal(TRUE),
      // The path of the nearest root component.
      'component_base_path' => PropertyDefinition::create('string')
        ->setAutoAcquiredFromRequester(),
    ]);
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
   * To indicate a component is unique within its requester, use the
   * '%requester' token within the tag.
   *
   * @see ComponentCollector::getComponentsFromData()
   * @see ComponentCollection::getMatchingComponent()
   *
   * @return string
   *   The merge tag. This may contain the '%requester' token. It is the
   *   caller's responsibility to replace this.
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
   * Implementations of this method should use self::getFilename() to get the
   * component's filename rather than the data property, as that takes into
   * account components being inside a child root component such as a test
   * module.
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
   * Because collectComponentContents() is only called on File components, and
   * recursively their contained components, returning something from this
   * method only has an effect if it puts this component in a chain of
   * containment descending from a File component.
   *
   * Components which return a root as their containing component are assumed
   * by the FileAssembler to be File components.
   *
   * @return string|NULL
   *  A string that defines another component as a chain of local names from a
   *  component. Tokens define the starting point. The following patterns are
   *  allowed:
   *   - '%root': Represents the root component.
   *   - '%requester': Represents the component that requested this component
   *      either via properties or requiredComponents().
   *   - '%requester:PATH': Represents a path of local names from the
   *     component that requested this component. The PATH may consist of a
   *     single local name, or a series of local names separated by a ':'.
   *     For example, '%requester:foo' gets the component 'foo' that was also
   *     spawned by this component's spawner. '%requester:foo:bar' gets the
   *     component spawned by the 'foo' component.
   *   - '%requester:(%requester)+:PATH': Ascends the request tree multiple
   *     steps.
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
   * @param \DrupalCodeBuilder\Generator\Collection\ComponentCollection $component_collection
   *   The component collection.
   */
  function collectComponentContents(ComponentCollection $component_collection) {
    $children_contents = [];

    // New system, experimental.
    // TODO: sort by type here using getContentType(), or leave that to
    // specific classes? Many don't need to care about type.
    $this->containedComponents = $component_collection->getContainedComponentCollection($this);

    // Allow each of our children to do the same as this to collect its own
    // children.
    foreach ($component_collection->getContainmentTreeChildren($this) as $id => $child_component) {
      $child_component->collectComponentContents($component_collection);
    }
  }

  /**
   * Gets the type of the content.
   *
   * This is used by the containing component to determine how to use the
   * content.
   *
   * @return string
   *   A string identifying the content type.
   */
  public function getContentType(): string {
    return 'element';
  }

  /**
   * Gets the contents of the component.
   *
   * @return array
   *   An array of content. The format of this depends on the value of
   *   self::getContentType(). Typically this will be code lines, or a keyed
   *   array for YAML data.
   */
  public function getContents(): array {
    return [];
  }

}
