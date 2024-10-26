<?php

namespace DrupalCodeBuilder\Utility\CodeAnalysis;

/**
 * Provides tools for analysing PHP variables and parameters.
 */
class Variables {

  /**
   * Produces the string representation of type from the ReflectionType.
   *
   * @param \ReflectionType $type_reflection
   *   The reflection type. If this is a compound type, this method calls itself
   *   with each of the subtypes.
   *
   * @return string
   *   The string representation of type, usable in code.
   */
  public static function getTypeStringFromReflection(\ReflectionType $type_reflection): string {
    if ($type_reflection instanceof \ReflectionNamedType) {
      $type = $type_reflection->getName();

      // Prefix a class with a '\'. We can assume that class types start with
      // an uppercase letter, and that there are no static / self etc types.
      if (ctype_upper(substr($type, 0, 1))) {
        $type = '\\' . $type;
      }

      return $type;
    }
    elseif ($type_reflection instanceof \ReflectionUnionType) {
      $union_types = array_map(fn (\ReflectionType $map_type) => static::getTypeStringFromReflection($map_type), $type_reflection->getTypes());
      return implode('|', $union_types);
    }

    assert(FALSE, 'Unsupported ReflectionType class.');
  }


}
