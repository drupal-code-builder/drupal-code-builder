<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Generate.
 */

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use MutableTypedData\Data\DataItem;
use DrupalCodeBuilder\Task\Generate\ComponentClassHandler;
use DrupalCodeBuilder\Task\Generate\ComponentCollector;
use DrupalCodeBuilder\Task\Generate\FileAssembler;

/**
 * Task handler for generating a component.
 */
class Generate extends Base {

  /**
   * The sanity level this task requires to operate.
   *
   * We have no sanity level; it's obtained from our base component generator.
   */
  protected $sanity_level = NULL;

  /**
   * The base component type being generated, i.e. either 'module' or 'theme'.
   */
  private $base;

  /**
   * The list of components.
   *
   * This is keyed by the unique ID of the component. Values are the
   * instantiated component generators.
   */
  protected $component_list;

  /**
   * Override the base constructor.
   *
   * @param $environment
   *  The current environment handler.
   * @param $component_type
   *  A component type. Currently supports 'module' and 'theme'.
   *  (We need this early on so we can use it to determine our sanity level.)
   */
  public function __construct(
    $environment,
    $component_type,
    ComponentClassHandler $class_handler,
    ComponentCollector $component_collector,
    FileAssembler $file_assembler
  ) {
    $this->environment = $environment;
    $this->classHandler = $class_handler;
    $this->componentCollector = $component_collector;
    $this->fileAssembler = $file_assembler;

    $this->base = $component_type;
  }

  /**
   * Get the sanity level this task requires.
   *
   * We override this to hand over to the base generator, as different bases
   * may have different requirements.
   *
   * @return
   *  A sanity level string to pass to the environment's verifyEnvironment().
   */
  public function getSanityLevel() {
    $class = $this->classHandler->getGeneratorClass($this->base);
    return $class::getSanityLevel();
  }

  /**
   * Gets the data object for the component.
   *
   * UIs should use this to present the options to the user.
   *
   * @param string $component_type
   *   (optional) The component type.
   *   NOTE: the default value will be removed in 5.0.0.
   */
  public function getRootComponentData($component_type = 'module') {
    $class = $this->classHandler->getGeneratorClass($this->base);

    // We use a custom data item factory so we can add custom Expression
    // Language functions.
    $data = DrupalCodeBuilderDataItemFactory::createFromProvider($class);

    return $data;
  }

  /**
   * Generate the files for a component.
   *
   * @param \MutableTypedData\Data\DataItem $component_data
   *  The data for the component. This is defined by the generator class; see
   *  RootGenerator::getPropertyDefinition().
   * @param $existing_module_files
   *  (optional) An array of existing files for this module. Keys should be
   *  file paths relative to the module, values absolute paths.
   * @param \MutableTypedData\Data\DataItem $configuration
   *  (optional) Configuration data for the component. This should be the same
   *  data object as returned by
   *  \DrupalCodeBuilder\Task\Configuration::getConfigurationData(), with user
   *  values set on it.
   *
   * @return
   *  A files array whose keys are filepaths (relative to the module folder) and
   *  values are the code destined for each file.
   *
   * @throws \DrupalCodeBuilder\Exception\InvalidInputException
   *   Throws an exception if the given data is invalid.
   */
  public function generateComponent(DataItem $component_data, $existing_module_files = [], DataItem $configuration = NULL) {
    // Validate to ensure defaults are filled in.
    $component_data->validate();

    // Put the configuration data into the main data.
    if ($configuration) {
      foreach ($configuration as $configuration_property_name => $configuration_data_item) {
        $component_data->configuration->{$configuration_property_name} = $configuration_data_item->value;
      }
    }

    // WTF validate not filling in module name etc/??????
    // dump($component_data->export());
    // return;

    // Assemble the component list from the request data.
    $component_collection = $this->componentCollector->assembleComponentList($component_data);
    // return;

    // Backward-compatiblity.
    // TODO: replace this.
    $this->component_list = $component_collection->getComponents();

    \DrupalCodeBuilder\Factory::getEnvironment()->log(array_keys($this->component_list), "Complete component list names");

    // Let each component detect whether it already exists in the given module
    // files.
    // TODO: temp bypass!
    // $this->detectExistence($this->component_list, $existing_module_files);

    // Now assemble them into a tree.
    // Calls containingComponent() on everything and puts it into a 2-D array
    // of parent => [children].
    // TODO: replace use of $tree with accessor on the collection.
    $tree = $component_collection->assembleContainmentTree();

    \DrupalCodeBuilder\Factory::getEnvironment()->log($tree, "Component tree");

    $files_assembled = $this->component_list = $this->fileAssembler->generateFiles(
      $component_data,
      $component_collection
    );

    return $files_assembled;
  }

  /**
   * Lets each component determine whether it is already in existing files.
   *
   * Existence is determined at the component level, rather than the file level,
   * because one component may want to add to several files, and several
   * components may want to add to the same file. For example, a service may
   * exist, but other components might want to add services and therefore add
   * code to the services.yml file.
   *
   * @param $component_list
   *  The component list.
   * @param $existing_module_files
   *  The array of existing file names.
   */
  protected function detectExistence($component_list, $existing_module_files) {
    foreach ($component_list as $name => $component) {
      $component->detectExistence($existing_module_files);
    }
  }

  /**
   * Generator factory.
   *
   * @param $component_type
   *   The type of the component. This is use to build the class name: see
   *   getGeneratorClass().
   * @param $component_name
   *   The identifier for the component. This is often the same as the type
   *   (e.g., 'module', 'hooks') but in the case of types used multiple times
   *   this will be a unique identifier.
   * @param $component_data
   *   An array of data for the component. This is passed to the generator's
   *   __construct().
   *
   * @return
   *   A generator object, with the component name and data set on it, as well
   *   as a reference to this task handler.
   *
   * @throws \DrupalCodeBuilder\Exception\InvalidInputException
   *   Throws an exception if the given component type does not correspond to
   *   a component class.
   *
   * @deprecated
   */
  public function getGenerator($component_type, $component_name, $component_data = []) {
    return $this->classHandler->getGenerator($component_type, $component_data);
  }

  /**
   * Helper function to get the desired Generator class.
   *
   * @param $type
   *  The type of the component. This is the name of the class, without the
   *  version suffix. For classes in camel case, the string given here may be
   *  all in lower case.
   *
   * @return
   *  A fully qualified class name for the type and, if it exists, version, e.g.
   *  'DrupalCodeBuilder\Generator\Info6'.
   *
   * @deprecated
   */
  public function getGeneratorClass($type) {
    return $this->classHandler->getGeneratorClass($type);
  }

}
