<?php

namespace DrupalCodeBuilder\Attribute;

use Attribute;

/**
 * Declares the related base class of a versioned class.
 *
 * (In the context of versioned classes, 'base' does not necessarily involve
 * PHP class inheritance. See ContainerBuilder for terminology around versioned
 * classses.)
 *
 * @see \DrupalCodeBuilder\Attribute\DrupalCoreVersion
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class RelatedBaseClass {

  /**
   * Constructor.
   *
   * @param string $base_class
   *   The base class the targeted versioned class is related to.
   */
  public function __construct(
    public readonly string $base_class,
  ) {}

}
