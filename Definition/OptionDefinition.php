<?php

namespace DrupalCodeBuilder\Definition;

use MutableTypedData\Definition\OptionDefinition as BaseOptionDefinition;

/**
 * Extension to the MTD option definition which adds an API URL property.
 */
class OptionDefinition extends BaseOptionDefinition {

  public function __construct(
    $value,
    $label,
    $description = NULL,
    int $weight = 0,
    public readonly ?string $apiUrl = NULL,
  )  {
    parent::__construct($value, $label, $description, $weight);
  }

  /**
   * Factory method.
   *
   * @param mixed $value
   *   The data that is stored when this option is chosen.
   * @param string $label
   *   The label that is shown by UIs to the user.
   * @param string $description
   *   (optional) Additional text to show to the user in UIs.
   * @param int $weight
   *   (optional) The weight for sorting the option in the list. Larger numbers
   *   are heavier and sink to the bottom. Options with identical weights are
   *   shown in the sort order defined by the data definition.
   * @param string $api_url
   *   (optional) A URL to an API page about the option, if one exists.
   *
   * @return static
   */
  public static function create($value, string $label, string $description = NULL, int $weight = 0, ?string $api_url = NULL): self {
    return new static($value, $label, $description, $weight, $api_url);
  }

}
