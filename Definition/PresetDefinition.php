<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\OptionDefinition;
use MutableTypedData\Exception\InvalidDefinitionException;

/**
 * Defines a single preset.
 *
 * This does not support preset suggestions yet, but then, nothing else does.
 */
class PresetDefinition {

  /**
   * The name for this preset, also the value for the corresponding option.
   *
   * @var string
   */
  protected $name;

  /**
   * The option for this preset.
   *
   * @var \MutableTypedData\Definition\OptionDefinition
   */
  protected $option;

  /**
   * The values to force on other properties when the preset is selected.
   *
   * This is in the same format as the preset array definition:
   *
   * [
   *   'property_name' => [
   *     'value' => VALUE
   *   ]
   * ]
   *
   * @var array
   */
  protected $force = [];

  public function __construct($value, $label, $description = NULL) {
    $this->option = new OptionDefinition($value, $label, $description);
    $this->name = $this->option->getValue();
  }

  /**
   * Factory method.
   *
   * @param mixed $value
   *   The data that is stored when this preset is chosen.
   * @param string $label
   *   The label that is shown by UIs to the user for the preset.
   * @param string $description
   *   (optional) Additional text to show to the user in UIs.
   *
   * @return static
   */
  public static function create($value, string $label, string $description = NULL): self {
    return new static($value, $label, $description);
  }

  public function setForceValues(array $values): self {
    $this->force = $values;
    return $this;
  }

  public function getName(): string {
    return $this->name;
  }

  public function getOption(): OptionDefinition {
    return $this->option;
  }

  public function getForceValues(): array {
    return $this->force;
  }

}
