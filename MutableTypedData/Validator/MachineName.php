<?php

namespace DrupalCodeBuilder\MutableTypedData\Validator;

use MutableTypedData\Data\DataItem;
use MutableTypedData\Validator\ValidatorInterface;

/**
 * Validates a Drupal machine name.
 */
class MachineName implements ValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(DataItem $data): bool {
    return
      // First character must be a lowercase letter.
      preg_match('@^[[:lower:]]@', $data->value)
      &&
      // Only lowercase letters, numbers, and underscores are allowed.
      !preg_match('@[^a-z0-9_]@', $data->value);
  }

  /**
   * {@inheritdoc}
   */
  public function message(DataItem $data): string {
    return "The @label must be a machine name in snake_case format.";
  }

}
