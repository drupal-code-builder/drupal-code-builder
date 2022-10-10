<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a service mocked in a unit test class.
 */
class TestMockedService extends InjectedService {

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    $contents = parent::getContents();

    // TODO: clean up!
    foreach (['service', 'service_property'] as $existing_key) {
      $new_key = $existing_key . '_mocked';

      $contents[$new_key] = $contents[$existing_key];
    }

    return $contents;
  }

}
