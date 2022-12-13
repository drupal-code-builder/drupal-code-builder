<?php

namespace DrupalCodeBuilder\MutableTypedData\Validator;

use MutableTypedData\Data\DataItem;
use MutableTypedData\Validator\ValidatorInterface;

/**
 * Validates a form reference.
 */
class FormReference implements ValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(DataItem $data): bool {
    if (str_starts_with($data->value, '!')) {
      return is_numeric(substr($data->value, 1));
    }

    // We don't validate a class name yet. TODO!
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function message(DataItem $data): string {
    return "The value '@value' for @label must be a PHP class name in PascalCase format or '!N' where N is the index of a generated form.";
  }

}
