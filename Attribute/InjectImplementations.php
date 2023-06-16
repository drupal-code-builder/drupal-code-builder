<?php

namespace DrupalCodeBuilder\Attribute;

use Attribute;

/**
 * #[InjectImplementations] attribute.
 *
 * Declares that a service method should be called by the container to inject a
 * collection of services implementing a particular interface.
 *
 * This is the same concept as Drupal core's tagged service collectors, but
 * with an attribute on the collector method rather than a tag on the service
 * definitions.
 *
 * To use this:
 * - Add a method to a service which accepts an array of services (this is
 *   because PHP-DI doesn't allow more than one parameter to service methods!).
 * - Add this attribute to that method. The attribute's parameter must be an
 *   interface.
 * - Define one or more services that implement the interface passed to the
 *   attribute.
 *
 * Services are injected as an array, keyed by the service name.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class InjectImplementations {

  public function __construct(private string $interface) {}

  /**
   * Gets the name of the interface the marked method collects.
   *
   * @return string
   *   The name of the interface.
   */
  public function getInterface(): string {
    return $this->interface;
  }

}
