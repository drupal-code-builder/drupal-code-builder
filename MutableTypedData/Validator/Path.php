<?php

namespace DrupalCodeBuilder\MutableTypedData\Validator;

use MutableTypedData\Data\DataItem;
use MutableTypedData\Validator\ValidatorInterface;

/**
 * Validates a path.
 */
class Path implements ValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(DataItem $data): bool {
    return (strpos($data->value, '/') === 0);
  }

  /**
   * {@inheritdoc}
   */
  public function message(DataItem $data): string {
    return "The @label must begin with a '/'.";
  }

}
