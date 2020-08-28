<?php

namespace DrupalCodeBuilder\MutableTypedData\Validator;

use MutableTypedData\Data\DataItem;
use MutableTypedData\Validator\ValidatorInterface;

/**
 * Validates a plugin ID.
 */
class PluginName implements ValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(DataItem $data): bool {
    return
      // First character must be a lowercase letter.
      preg_match('@^[[:lower:]]@', $data->value)
      &&
      // Only lowercase letters, numbers, and underscores, and colons are
      // allowed.
      // TODO: only ONE colon is allowed!
      !preg_match('@[^a-z0-9_:]@', $data->value);
  }

  /**
   * {@inheritdoc}
   */
  public function message(DataItem $data): string {
    return "The @label may only contain lowercase letters, numbers, underscores, and a colon.";
  }

}
