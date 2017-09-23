<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\NameFormattingTrait.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Trait for formatting names.
 */
trait NameFormattingTrait {

  /**
   * Helper to convert a snake case string to camel case.
   *
   * @param $snake_case_string
   *  A string in the format 'convert_this'.
   *
   * @return
   *  The converted string, e.g. 'ConvertThis'.
   */
  public static function toCamel($snake_case_string) {
    // TODO: support split on '.' if needed?
    $pieces = explode('_', $snake_case_string);

    $camel = implode('', array_map('ucfirst', $pieces));

    return $camel;
  }

  /**
   * Helper to make a fully-qualified class name.
   *
   * @param array $class_name_pieces
   *  An array of the class name pieces.
   *
   * @return
   *  The qualified class name string, without the initial slash, e.g.
   *  'Drupal\Foo\SomeClass'.
   */
  public static function makeQualifiedClassName($class_name_pieces) {
    $qualified_class_name = implode('\\', $class_name_pieces);
    return $qualified_class_name;
  }

}
