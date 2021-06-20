<?php

namespace DrupalCodeBuilder\Definition;

use DrupalCodeBuilder\Utility\InsertArray;
use MutableTypedData\Definition\DataDefinition as BasePropertyDefinition;
use MutableTypedData\Definition\OptionDefinition;
use MutableTypedData\Exception\InvalidDefinitionException;

/**
 * Extends the basic property definition with DCB extras.
 *
 * TODO: replace return type with 'self' when PHP 7.4 is the minimum; it's
 * broken in PHP 7.3.
 */
class PropertyDefinition extends BasePropertyDefinition implements \ArrayAccess {

  // TODO: can this be done with defaults instead??
  protected $acquiringExpression = FALSE;

  protected $presets = [];

  protected $processing;

  protected $optionsProvider;

  protected $variantMappingProvider;

  protected $autoAcquired = FALSE;

  /**
   * Adds a property after the named property.
   *
   * @param string $after
   *   The name of the property to insert after.
   * @param \MutableTypedData\Definition\DataDefinition $properties
   *   The properties to insert after the specified property name, in the order
   *   to insert them.
   *
   * @return \MutableTypedData\Definition\DataDefinition
   *   Returns the same instance for chaining. (This is typed as the parent
   *   class so it can be moved to MTD in future without causing incompatibility
   *   issues.)
   */
  public function addPropertyAfter(string $after, BasePropertyDefinition ...$properties): BasePropertyDefinition {
    // TODO! this won't catch child classes of SimpleData!!!
    if ($this->type == 'string' || $this->type == 'boolean') {
      // TODO: needs tests
      throw new InvalidDefinitionException("Simple data can't have sub-properties.");
    }

    if ($this->type == 'mutable') {
      throw new InvalidDefinitionException("Mutable data must have only the type property.");
    }

    // Reverse the array of properties, as we keep adding them after the $after
    // property.
    $properties = array_reverse($properties);

    foreach ($properties as $property) {
      if (empty($property->getName())) {
        throw new InvalidDefinitionException("Properties added with addPropertyAfter() must have a machine name set.");
      }

      InsertArray::insertAfter($this->properties, $after, [
        $property->getName() => $property,
      ]);
    }

    return $this;
  }

  public function getDeltaDefinition(): self {
    $delta_definition = parent::getDeltaDefinition();

    // Remove presets.
    $delta_definition->setPresets([]);

    return $delta_definition;
  }

  // TODO: move all options provider stuff upstream.
  public function setOptionsProvider(OptionsProviderInterface $provider): BasePropertyDefinition {
    $this->optionsProvider = $provider;

    return $this;
  }

  public function addOption(OptionDefinition $option): BasePropertyDefinition {
    if ($this->optionsProvider) {
      throw new InvalidDefinitionException("Can't add options if using an options provider.");
    }

    return parent::addOption($option);
  }

  public function hasOptions(): bool {
    return !empty($this->options) || !empty($this->optionsProvider);
  }

  public function getOptions(): array {
    if (!$this->options && $this->optionsProvider) {
      $this->options = $this->optionsProvider->getOptions();
    }

    return parent::getOptions();
  }

  public function setVariantMappingProvider(VariantMappingProviderInterface $provider): BasePropertyDefinition {
    $this->variantMappingProvider = $provider;
    return $this;
  }

  public function hasVariantMapping(): bool {
    return !is_null($this->variantMapping) || $this->variantMappingProvider;
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
   * @return self
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
   * @return self
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

  public function setPresets(array $presets) :self {
    $this->presets = $presets;

    if ($presets) {
      $options = [];
      foreach ($presets as $key => $preset) {
        $option = OptionDefinition::create($key, $preset['label'], $preset['description'] ?? NULL);
        $options[] = $option;
      }
      $this->setOptions(...$options);
    }

    return $this;
  }

  public function getPresets() :array {
    return $this->presets;
  }

  public function setProcessing(callable $callback): BasePropertyDefinition {
    $this->processing = $callback;

    return $this;
  }

  public function getProcessing(): ?callable {
    return $this->processing;
  }

  public function offsetExists($offset) {
    dump($this);
    throw new \Exception("Accessing definition $this->name as array with offsetExists $offset.");
  }

  public function offsetGet($offset) {
    dump($this);
    throw new \Exception("Accessing definition $this->name as array with offsetGet $offset.");
  }

  public function offsetSet($offset, $value) {
    dump($this);
    throw new \Exception("Accessing definition $this->name as array with offsetSet $offset.");
  }

  public function offsetUnset($offset){
    dump($this);
    throw new \Exception("Accessing definition $this->name as array with offsetUnset $offset.");
  }

}
