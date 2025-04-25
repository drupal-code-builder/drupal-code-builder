<?php

namespace DrupalCodeBuilder\Definition;

use DrupalCodeBuilder\Definition\OptionDefinition;
use MutableTypedData\Definition\DataDefinition as BasePropertyDefinition;
use MutableTypedData\Definition\OptionDefinition as BaseOptionDefinition;
use MutableTypedData\Definition\PropertyListInterface;
use MutableTypedData\Exception\InvalidDefinitionException;

/**
 * Extends the basic property definition with DCB extras.
 */
class PropertyDefinition extends BasePropertyDefinition implements PropertyListInterface, \ArrayAccess {

  use PropertyInsertTrait;

  // TODO: can this be done with defaults instead??
  protected $acquiringExpression = FALSE;

  protected $presets = [];

  protected $processing;

  protected $optionsProvider;

  protected $variantMappingProvider;

  protected $autoAcquired = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getComponentType(): string {
    return $this->componentType;
  }

  public function getDeltaDefinition(): self {
    $delta_definition = parent::getDeltaDefinition();

    // Remove presets.
    if ($delta_definition->getPresets()) {
      $delta_definition->setPresets([]);
    }

    return $delta_definition;
  }

  public function addOption(BaseOptionDefinition $option): self {
    if ($this->optionsProvider) {
      throw new InvalidDefinitionException("Can't add options if using an options provider.");
    }

    return parent::addOption($option);
  }

  public function hasOptions(): bool {
    return parent::hasOptions() || !empty($this->optionsProvider);
  }

  public function getOptions(): array {
    if (!$this->options && $this->optionsProvider) {
      $this->options = $this->optionsProvider->getOptions();
    }

    return parent::getOptions();
  }

  /**
   * Removes a default from the definition, if one was set.
   */
  public function removeDefault(): self {
    $this->default = NULL;
    return $this;
  }

  public function setVariantMappingProvider(VariantMappingProviderInterface $provider): self {
    $this->variantMappingProvider = $provider;
    return $this;
  }

  public function hasVariantMapping(): bool {
    return parent::hasVariantMapping() || $this->variantMappingProvider;
  }

  public function getVariantMapping(): ?array {
    if (!$this->variantMapping && $this->variantMappingProvider) {
      $this->variantMapping = $this->variantMappingProvider->getVariantMapping();
    }

    return parent::getVariantMapping();
  }

  /**
   * Sets this to acquire its value from the requster's same name property.
   *
   * Note there is no way to REMOVE acquired status, but this should be fine.
   *
   * @return static
   *   Returns the definition, for chaining.
   */
  public function setAutoAcquiredFromRequester(): self {
    if ($this->acquiringExpression) {
      throw new \LogicException();
    }

    $this->setInternal(TRUE);

    // Can't set the expression at this point, as typically this definition is
    // set as one of an array and so does not have its name set yet; relying
    // instead on its key in the containing properties array.
    $this->autoAcquired = TRUE;

    return $this;
  }

  /**
   * Sets the expression to acquire a value from the requesting component.
   *
   * Note there is no way to REMOVE acquired status, but this should be fine.
   *
   * @param string $expression
   *   An Expression Language expression. This can use object notation and does
   *   not need to bother with using custom Expression Language functions from
   *   DataAddressLanguageProvider, since there is no need for JavaScript
   *   interpretation. Available variables:
   *    - requester: The requesting component.
   *
   * @return static
   *   Returns the definition, for chaining.
   */
  public function setAcquiringExpression(string $expression) :self {
    if ($this->autoAcquired) {
      throw new \LogicException();
    }

    $this->acquiringExpression = $expression;

    $this->internal = TRUE;

    return $this;
  }

  public function getAcquiringExpression() :?string {
    if ($this->autoAcquired) {
      if ($this->name == 'root_component_name') {
        throw new \LogicException("This case not covered here yet!");
      }

      $this->setInternal(TRUE);

      if ($this->isMultiple()) {
        // Urgh.
        $expression = "requester.{$this->name}.export()";
      }
      else {
        $expression = "requester.{$this->name}.value";
      }

      return $expression;
    }
    else {
      return $this->acquiringExpression;
    }
  }

  /**
   * Sets presets for this property.
   *
   * @param mixed ...$presets
   *   Either:
   *    - a variable number of \DrupalCodeBuilder\Definition\PresetDefinition
   *      objects
   *    - a single array of preset definition arrays.
   *
   * @return self
   *   Returns $this for chaining.
   */
  public function setPresets(...$presets) :self {
    if ($this->getType() != 'string') {
      throw new InvalidDefinitionException(sprintf(
        "Property %s is not of type 'string' and so cannot have presets",
        $this->name
      ));
    }

    if ($presets) {
      // New object definition of presets.
      if (is_object($presets[0])) {
        $options = [];
        /** @var \DrupalCodeBuilder\Definition\PresetDefinition $preset */
        foreach ($presets as $preset) {
          $options[] = $preset->getOption();
        }

        // Consumers of presets don't support PresetDefinitions yet, so convert
        // here to array.
        $preset_definitions = $presets;
        $presets = [];
        /** @var \DrupalCodeBuilder\Definition\PresetDefinition $preset */
        foreach ($preset_definitions as $preset) {
          // We only need to set the force data, as that's all preset
          // consumers need.
          $presets[$preset->getName()] = [
            'data' => [
              'force' => $preset->getForceValues(),
            ],
          ];
        }
      }
      else {
        $presets = $presets[0];

        $options = [];
        foreach ($presets as $key => $preset) {
          $option = OptionDefinition::create(
            $key,
            $preset['label'],
            $preset['description'] ?? NULL,
            // TODO: These are only supported for old-style array definitions!
            api_url: $preset['api_url'] ?? NULL,
          );
          $options[] = $option;
        }
      }

      $this->setOptions(...$options);
    }

    $this->presets = $presets;

    return $this;
  }

  public function getPresets() :array {
    return $this->presets;
  }

  /**
   * Sets a processing callback.
   *
   * Processing is applied to a component's data when it is instantiated from
   * input data.
   *
   * Note that processing is not applied to default values!
   *
   * @param callable $callback
   *   The callback to apply to data.
   *
   * @return self
   *
   * @see \DrupalCodeBuilder\Task\Generate\ComponentCollector::processComponentData()
   */
  public function setProcessing(callable $callback): self {
    $this->processing = $callback;

    return $this;
  }

  public function getProcessing(): ?callable {
    return $this->processing;
  }

  /**
   * Load all the lazy properties.
   *
   * Iterates into all properties, so that any definitions which are instances
   * of MergingGeneratorDefinition load their properties.
   *
   * TODO: possibly rethink the lazy-loading thing? Can the problem it exists to
   * solve be dealt with instead by changing all report tasks to lazy option
   * providers and a bit of fancy footwork for the Module/TestModule
   * circularity?
   */
  public function loadLazyProperties() {
    foreach ($this->getProperties() as $property) {
      $property->loadLazyProperties();
    }
  }

  public function offsetExists(mixed $offset): bool {
    dump($this);
    throw new \Exception("Accessing definition $this->name as array with offsetExists $offset.");
  }

  public function offsetGet(mixed $offset): mixed {
    dump($this);
    throw new \Exception("Accessing definition $this->name as array with offsetGet $offset.");
  }

  public function offsetSet(mixed $offset, mixed $value): void {
    dump($this);
    throw new \Exception("Accessing definition $this->name as array with offsetSet $offset.");
  }

  public function offsetUnset(mixed $offset): void {
    dump($this);
    throw new \Exception("Accessing definition $this->name as array with offsetUnset $offset.");
  }

}
