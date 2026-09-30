<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Trait for formatting names.
 */
trait NameFormattingTrait {

  /**
   * Helper to make a fully-qualified class name.
   *
   * @param array $class_name_pieces
   *  An array of the class name pieces. It is permissible for some pieces to
   *  contain more than one subnamespaces.
   *
   * @return string
   *  The qualified class name string, without the initial slash, e.g.
   *  'Drupal\Foo\SomeClass'.
   */
  public static function makeQualifiedClassName($class_name_pieces) {
    $qualified_class_name = implode('\\', $class_name_pieces);
    return $qualified_class_name;
  }

  /**
   * Prefixes a string if it does not already start with the prefix.
   *
   * @param string $prefix
   *   The prefix string.
   * @param string $glue
   *   The glue to join the prefix to the string.
   * @param string $main
   *   The main string.
   *
   * @return string
   *   The main string with the prefix at the start, either from adding it or
   *   because it was already there.
   */
  public static function softPrepend(string $prefix, string $glue, string $main): string {
    if (str_starts_with($main, $prefix)) {
      return $main;
    }
    else {
      return $prefix . $glue . $main;
    }
  }

}
