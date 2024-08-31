<?php

namespace DrupalCodeBuilder\Attribute;

use Attribute;

/**
 * Marks a core version that a class works for.
 *
 * This attribute is for classes that support multiple versions, and so can't
 * use a numeric suffix on their class name to indicate the version they
 * support.
 *
 * Classes with this attribute must also have the RelatedBaseClass attribute.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class DrupalCoreVersion {

  /**
   * Constructor.
   *
   * @param int $core_version
   *   One of the core versions that the targeted class is for.
   */
  public function __construct(
    public readonly int $core_version,
  ) {}

}
