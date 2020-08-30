<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\PropertyDefinition as OriginalPropertyDefinition;
use MutableTypedData\Definition\OptionDefinition;

/**
 * Extends the basic property definition with DCB extras.
 */
class PropertyDefinition extends OriginalPropertyDefinition implements \ArrayAccess {

  // TODO: can this be done with defaults instead??
  protected $acquiringExpression = FALSE;

  protected $presets = [];

  protected $processing;

  public function getDeltaDefinition(): self {
    $delta_definition = parent::getDeltaDefinition();

    // Remove presets.
    $delta_definition->setPresets([]);

    return $delta_definition;
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
    $this->acquiringExpression = $expression;

    $this->internal = TRUE;

    return $this;
  }

  public function getAcquiringExpression() :?string {
    return $this->acquiringExpression;
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

  public function setProcessing(callable $callback): self {
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
