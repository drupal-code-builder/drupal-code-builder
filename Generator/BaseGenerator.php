<?php

namespace DrupalCodeBuilder\Generator;

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
abstract class BaseGenerator {

  /**
   * The name of this generator.
   *
   * @see getUniqueID().
   */
  public $name;

  /**
   * The generator type.
   *
   * This is the unqualified class name without the version suffix.
   */
  public $type;

  /**
   * Reference to the Generate task handler.
   *
   * This should be used to access the environment, and to call getGenerator().
   */
  public $task;

  /**
   * Reference to the root component of this component.
   *
   * This should be used to access the component data.
   */
  public $root_component;

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
   * Boolean to indicate whether this component already exists.
   *
   * @see detectExistence()
   */
  public $exists = FALSE;

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
   *   need to access the data of the root component.
   *   TODO: check whether components really need to do this, as removing this
   *   would simplify things!
   * @param $root_generator
   *   The root Generator object.
   */
  function __construct($component_name, $component_data, $root_generator) {
    $this->name = $component_name;
    $this->component_data = $component_data;

    // TODO: Remove this to simplify how generator classes get their data.
    $this->root_component = $root_generator;

    // Set the type. This is the short class name without the numeric version
    // suffix.
    $class = get_class($this);
    $class_pieces = explode('\\', $class);
    $short_class = array_pop($class_pieces);
    $this->type = preg_replace('@\d+$@', '', $short_class);
  }

  /**
   * Define the component data this component needs to function.
   *
   * This returns an array of data that defines the component data that
   * this component should be given to perform its work. This includes:
   *  - data that must be specified by the user
   *  - data that may be specified by the user, but can be computed or take from
   *    defaults
   *  - data that should not be specified by the user, as it is computed from
   *    other input.
   *
   * This array must be processed in the order in which the properties are
   * given, so that the callables for defaults and options work properly.
   *
   * Note this can't be a class property due to use of closures.
   *
   * @return
   *  An array that defines the data this component needs to operate. The order
   *  is important, as callbacks may depend on component data that has been
   *  assembled so far, i.e., on data properties that are earlier in the array.
   *  Each key corresponds to a key for a property in the $component_data that
   *  should be passed to this class's __construct(). Each value is an array,
   *  with the following keys:
   *  - 'label': A human-readable label for the property.
   *  - 'description': (optional) A longer description of the property.
   *  - 'format': (optional) Specifies the expected format for the property. If
   *    omitted, defaults to 'string'.
   *    Possible values are:
   *    - 'string': The property's data should be a plain string.
   *    - 'boolean': The property's data should be a boolean.
   *    - 'array': The property's data should be an array of string values. The
   *      keys are ignored.
   *    - 'compound': The property's data should be an array where each element
   *      is a further array of properties. The keys are ignored. The values in
   *      each array are specified by calling this method on the class
   *      determined from the 'component' property.
   *  - 'default': (optional) The default value for the property. This is either
   *    a static value, or a callable, in which case it must be called with the
   *    array of component data assembled so far. Depending on the value of
   *    'required', this represents either the value that may be presented as a
   *    default to the user in a UI for convenience, or the value that will be
   *    set if nothing is provided when instatiating the component. Note that
   *    this is required if a later property makes use of this property in a
   *    callback, as non-progressive UIs can only rely on hardcoded default
   *    values.
   *  - 'required': (optional) Boolean indicating whether this property must be
   *    provided. Defaults to FALSE.
   *  - 'cardinality': (optional) For properties with format 'array' or
   *    'compound', specifies the maximum number of values. If omitted,
   *    unlimited values are allowed.
   *  - 'process_default': (optional) Boolean indicating if TRUE that this
   *    property will have the default value set on it if it is empty in the
   *    process stage. This is different from 'required', in that the user may
   *    leave this empty. The purpose of this is for properties where in the
   *    absence of user input, we can derive a sensible value, but we choose to
   *    allow the user to override this. For example, the description on
   *    permissions, which can be derived from the machine name. Defaults to
   *    FALSE.
   *  - 'options': (optional) One of:
   *    - A callable which returns a list of options for the property. This
   *      receives the component data assembled so far.
   *    - A string in the form 'TASKNAME:METHOD' to call to get the array of
   *      options.
   *  - 'options_structured': (optional) A callable which returns data about the
   *    possible options for the property. Use this as an alternative to the
   *    'options' property if you want more information. This returns an array
   *    keyed by group name (where the group is possibly the module that
   *    defines the option), whose values are arrays keyed by the options. Each
   *    value is a further array with these properties:
   *      - 'description': A longer description of the item.
   *  - 'options_allow_other': (optional) If TRUE, specifies that values outside
   *    the list of options are allowable.
   *  - 'processing': (optional) A callback to process input values into the
   *    final format for the component data array. Any changes values should be
   *    placed into the data array. This is called by
   *    ComponentCollector::processComponentData().
   *  - 'process_empty': (optional) Boolean to indicate whether the processing
   *    callback should be applied if the property value is empty. Defaults to
   *    FALSE.
   *  - 'component': (optional) The name of a generator class, relative to the
   *    namespace. If present, this results in child components of this class
   *    being added to the component tree.
   *  - 'computed': (optional) If TRUE, indicates that this property is computed
   *    by the component, and should not be obtained from the user.
   *  - 'internal': (optional) If TRUE, indicates that this property should not
   *    be returned to UIs, as it is for internal use only when requested by
   *    other generators.
   *
   * @see Generate::getComponentDataInfo()
   */
  public static function componentDataDefinition() {
    return [
      'root_component_name' => [
        'acquired' => TRUE,
      ],
      'containing_component' => [
        'internal' => TRUE,
      ],
      'component_base_path' => [
        'acquired' => TRUE,
      ],
    ];
  }

  /**
   * Return a unique ID for this component.
   *
   * In most cases, it suffices to prefix the name with the component type;
   * names will generally be unique within a type.
   *
   * @return
   *  The unique ID
   */
  public function getUniqueID() {
    return
      $this->component_data['root_component_name'] . '/' .
      $this->type . ':' .
      $this->name;
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
      throw new \Exception(strtr("Property @name not found in data for @type component ID '@id'.", [
        '@name' => $name,
        '@type' => get_class($this),
        '@id' => $this->getUniqueID(),
      ]));
    }

    return $this->component_data[$name];
  }

  /**
   * Define how other components acquire properties from this one.
   *
   * @see ComponentCollector::getComponentsFromData()
   *
   * @return array
   *   An array where the key is the property name in this class's component
   *   data, and the value is the property name provided to other components.
   */
  public function providedPropertiesMapping() {
    return [
      'root_component_name' => 'root_component_name',
    ];
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
   * @see Generate::assembleComponentList()
   */
  public function requiredComponents() {
    return array();
  }

  /**
   * Merge data from additional requests of a component.
   */
  public function mergeComponentData($additional_component_data) {
    // Get the property info for just this component: we don't care about
    // going into compound properties.
    $component_property_info = static::componentDataDefinition();

    // Only merge array properties.
    foreach ($component_property_info as $property_name => $property_info) {
      // Skip this property if there's nothing here.
      if (!isset($this->component_data[$property_name]) && !isset($additional_component_data[$property_name])) {
        continue;
      }

      // We're getting the component data direct, so it won't have default
      // attributes filled in: 'format' might not be set.
      if (!isset($property_info['format']) || $property_info['format'] != 'array') {
        // Don't merge this property, but check that we're not throwing away
        // data from the additional data.
        assert($this->component_data[$property_name] == $additional_component_data[$property_name]);

        continue;
      }

      $this->component_data[$property_name] = array_merge_recursive($this->component_data[$property_name], $additional_component_data[$property_name]);
    }
  }

  /**
   * Detect whether this component exists in the given module files.
   *
   * Components should set $this->exists on themselves if they find they already
   * exist in the module.
   *
   * @param $existing_module_files
   *  An array of information about existing module files. Keys are filenames
   *  relative to the module, values are absolute filenames.
   */
  public function detectExistence($existing_module_files) {
    // Do nothing by default.
  }

  /**
   * Returns this component's parent in the component tree.
   *
   * @return
   *  One of the following:
   *   - The ID of this component's parent in the tree.
   *   - One of the following tokens to represent a parent component:
   *     - '%requester': Represents the component that requested this component
   *        via requiredComponents().
   *     - '%sibling:NAME': Represents one of the sibling components in a list
   *       returned from requiredComponents(), where NAME is the key in the
   *       returned value from requiredComponents().
   *   - NULL if this component is either the base, or does not participate in
   *     the tree.
   *
   * @see assembleComponentTree()
   */
  function containingComponent() {
    return $this->component_data['containing_component'] ?? NULL;
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
   *  An array of data for this component's content, in the same form as the
   *  return of buildComponentContents().
   */
  function buildComponentContentsIterative($components, $tree) {
    $children_contents = array();

    // Allow each of our children to do the same as this to collect its own
    // children.
    if (!empty($tree[$this->getUniqueID()])) {
      foreach ($tree[$this->getUniqueID()] as $child_name) {
        $child_component = $components[$child_name];
        $child_contents = $child_component->buildComponentContentsIterative($components, $tree);
        foreach ($child_contents as $key => $contents) {
          $children_contents[$child_name . ':' . $key] = $contents;
        }
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
  function buildComponentContents($children_contents) {
    // Base does nothing.
    return array();
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
   *  role, keyed by the same key as the the given array item.
   */
  protected function filterComponentContentsForRole($contents, $role) {
    $return = [];
    foreach ($contents as $key => $item) {
      if ($item['role'] == $role) {
        $return[$key] = $item['content'];
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
