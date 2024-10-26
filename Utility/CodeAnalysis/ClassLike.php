<?php

namespace DrupalCodeBuilder\Utility\CodeAnalysis;

/**
 * Provides tools for analysing PHP classes.
 */
class ClassLike {

  /**
   * The PHP namespace separator.
   */
  const NS = '\\';

  /**
   * Determines whether two classes are in the same namespace.
   *
   * @param string $class_a
   *   The class name.
   * @param string $class_b
   *   The other class name.
   *
   * @return bool
   *   TRUE if the classes are in the same namespace, FALSE if not.
   */
  public static function classesInSameNamespace(string $class_a, string $class_b): bool {
    return static::getNamespace($class_a) === static::getNamespace($class_b);
  }

  /**
   * Gets the namespace of a class.
   *
   * @param string $class
   *   The class name.
   *
   * @return string
   *   The class namespace.
   */
  public static function getNamespace(string $class): string {
    $pieces = explode(static::NS, $class);
    array_pop($pieces);
    return implode(static::NS, $pieces);
  }

}