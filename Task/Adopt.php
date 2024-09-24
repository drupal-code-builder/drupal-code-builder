<?php

namespace DrupalCodeBuilder\Task;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\File\DrupalExtension;
use DrupalCodeBuilder\Generator\AdoptableInterface;
use DrupalCodeBuilder\Task\Generate\ComponentClassHandler;
use MutableTypedData\Data\DataItem;

/**
 * Task handler for adopting existing components.
 *
 * Adoption is the process of creating data in the root component which
 * represents a component in an existing extension. For example, a service in an
 * existing extension can be added to the root component as a service item.
 *
 * Generators which support adoption must implement
 * \DrupalCodeBuilder\Generator\AdoptableInterface.
 */
class Adopt extends Base {

  /**
   * The class handler.
   *
   * @$var \DrupalCodeBuilder\Task\Generate\ComponentClassHandler $class_handler
   */
  protected $classHandler;

  /**
   * Constructs a new ComponentCollector.
   *
   * @param EnvironmentInterface $environment
   *   The environment object.
   * @param ComponentClassHandler $class_handler
   *   The class handler helper.
   */
  public function __construct(
    EnvironmentInterface $environment,
    ComponentClassHandler $class_handler
  ) {
    $this->environment = $environment;
    $this->classHandler = $class_handler;
  }

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * Gets the component data for an existing Drupal extension.
   *
   * @param \DrupalCodeBuilder\File\DrupalExtension $existing_extension
   *   The existing extension.
   *
   * @return \MutableTypedData\Data\DataItem
   *   The root component data based on the extension. As DCB doesn't store
   *   user data, it is up to the calling UI to store this.
   */
  public function adoptExtension(DrupalExtension $existing_extension): DataItem {
    $generator_class = $this->classHandler->getGeneratorClass($existing_extension->type);
    $data = $generator_class::adoptRootComponent($existing_extension);

    return $data;
  }

  /**
   * Analyses an existing extension for items which can be adopted.
   *
   * @param \MutableTypedData\Data\DataItem $component_data
   *   The existing root component data.
   * @param \DrupalCodeBuilder\File\DrupalExtension $existing_extension
   *   The existing extension to analyse.
   *
   * @return array
   *   An array of adoptable items. Keys are addresses in the given root
   *   component (but these addresses do not necessarily correspond to a present
   *   data item, as it's possible that adoptable components are found for data
   *   which is currently empty: for example, a module definition might have no
   *   forms, and this method finds a form to adopt). Values are arrays of items
   *   which can be adopted into that property. Keys are item identiers which
   *   are suitable for passing to static::adoptComponent(); values are
   *   human-readable labels. The identifiers do not necessarily have any
   *   relation to any other data.
   */
  public function listAdoptableComponents(DataItem $component_data, DrupalExtension $existing_extension): array {
    $adoptable = [];

    $definition = $component_data->getDefinition();

    // For now only do top-level.
    // Iterate over the definition rather than the data, as iterating over the
    // data auto-vivifies elements which we don't want to happen, and as we
    // may find components to adopt into empty values.
    foreach ($definition->getProperties() as $name => $definition) {
      if (!$definition instanceof \DrupalCodeBuilder\Definition\MergingGeneratorDefinition) {
        continue;
      }

      $generator_class = $this->classHandler->getGeneratorClass($definition->getComponentType());
      if (!is_a($generator_class, AdoptableInterface::class, TRUE)) {
        continue;
      }

      $adoptable_items = $generator_class::findAdoptableComponents($existing_extension);
      if ($adoptable_items) {
        $adoptable[$component_data->getName() . ':' . $name] = $adoptable_items;
      }
    }
    return $adoptable;
  }

  /**
   * Adopt a component.
   *
   * @param \MutableTypedData\Data\DataItem $component_data
   *   The existing root component data.
   * @param \DrupalCodeBuilder\File\DrupalExtension $existing_extension
   *   The existing extension to analyse.
   * @param string $address
   *   The address in the component data of the property to adopt the component
   *   into. This is one of the keys in the return value of
   *   static::listAdoptableComponents().
   * @param string $component_name
   *   The name of the component to adopt. This is one of the keys in
   *   the nested arrays in the return value of static::listAdoptableComponents().
   */
  public function adoptComponent(DataItem $component_data, DrupalExtension $existing_extension, string $address, string $component_name) {
    $component_definition = $component_data->getItem($address)->getDefinition();
    $generator_class = $this->classHandler->getGeneratorClass($component_definition->getComponentType());

    if (!is_a($generator_class, AdoptableInterface::class, TRUE)) {
      throw new \InvalidArgumentException("The generator class for the address '{$address}' is not adoptable.");
    }

    // TODO: Resolve the WTF with the child knowing about how it's used: go in
    // via the PARENT, telling it to adopt COMPONENT into CHILD PROPERTY.

    $generator_class::adoptComponent($component_data, $existing_extension, $address, $component_name);
  }

}
