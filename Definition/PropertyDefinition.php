<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\PropertyDefinition as OriginalPropertyDefinition;

class PropertyDefinition extends OriginalPropertyDefinition implements \ArrayAccess {

  protected $internal = FALSE;

  // TODO: can this be done with defaults instead??
  protected $acquiringExpression = FALSE;

  protected $presets = [];

  protected $processing;

  protected $forceCreate = FALSE;

  public function getDeltaDefinition(): self {
    $delta_definition = parent::getDeltaDefinition();

    // Remove presets.
    $delta_definition->setPresets([]);

    // remove default.
    // TODO: move upstream??
    $delta_definition->default = NULL;

    return $delta_definition;
  }

  public function setInternal(bool $internal) :self {
    $this->internal = $internal;

    return $this;
  }

  public function isInternal() :bool {
    return $this->internal;
  }

  /**
   * Sets the expression to acquire a value from the requesting component.
   *
   * Note there is no way to REMOVE acquired status, but this should be fine.
   *
   * @param string $expression
   *   An Expression Language expression. This should use object notation and
   *   does not need to bother with getItemValue(), since there is no need for
   *   JavaScript interpretation. Available variables:
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

  public function setForceCreate(bool $force_create): self {
    $this->forceCreate = $force_create;

    return $this;
  }

  public function getForceCreate(): bool {
    return $this->forceCreate;
  }

  public function offsetExists($offset) {
    dump($this);
    throw new \Exception("Accessing definition $this->name as array with offsetExists $offset.");
  }

  public function offsetGet($offset) {
    throw new \Exception("Accessing definition $this->name as array with offsetGet $offset.");
  }

  public function offsetSet($offset, $value) {
    throw new \Exception("Accessing definition $this->name as array with offsetSet $offset.");
  }

  public function offsetUnset($offset){
    throw new \Exception("Accessing definition $this->name as array with offsetUnset $offset.");
  }


}
