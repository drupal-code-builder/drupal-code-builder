<?php

namespace DrupalCodeBuilder\MutableTypedData\Validator;

use MutableTypedData\Data\DataItem;
use MutableTypedData\Validator\ValidatorInterface;

/**
 * Validates a plugin type name.
 */
class PluginTypeName implements ValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(DataItem $data): bool {
    // Only lowercase letters, numbers, and underscores, and dots are
    // allowed.
    return !preg_match('@[^a-z0-9_.]@', $data->value);
  }

  /**
   * {@inheritdoc}
   */
  public function message(DataItem $data): string {
    return "The @label may only contain lowercase letters, numbers, underscores, and a full stop.";
  }

}
