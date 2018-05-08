<?php

namespace DrupalCodeBuilder\Generator\Render;

/**
 * Renderer for FormAPI arrays.
 *
 * TODO: Look into rewriting this as a subclass of RecursiveArrayIterator that
 * just returns the rendered line from current() if I can figure out how to
 * return multiple lines from current(). See example at
 * https://github.com/cballou/PHP-SPL-Iterator-Interface-Examples/blob/master/recursive-caching-iterator.php
 * If that's not doable, change render() to be a generator function that
 * yields() each line instead.
 *
 * TODO: move more of the work from FormBuilder / FormElement here.
 */
class FormAPIArrayRenderer {

  /**
   * The original data array.
   *
   * @var array
   */
  protected $data;

  /**
   * Creates a new FormAPIArrayRenderer.
   *
   * @param array $data
   *   An array of attributes and values for a FormAPI element. May contain
   *   nested attributes such as '#machine_name'. Attributes should have their
   *   initial '#'. Neither keys not values should be quoted, as they will
   *   receive quotes (single for keys, double for values), with the exception
   *   of the following cases:
   *    - values which begin with '£' are taken to be an expression
   *    - values which begin with '\' are taken to be a qualified class name or
   *      a class constant.
   *    - values which begin with '[' are taken to be an inline array.
   */
  public function __construct($data) {
    $this->data = $data;
  }

  /**
   * Gets the array iterator object to use.
   *
   * @return \RecursiveIterator
   *   The iterator.
   */
  protected function getArrayIterator() {
    return new \RecursiveArrayIterator($this->data);
  }

  /**
   * Gets the outer iterator object to use.
   *
   * @return \OuterIterator
   *   The iterator.
   */
  protected function getRecursiveIterator() {
    $data_iterator = $this->getArrayIterator();

    return new \RecursiveIteratorIterator(
      new \RecursiveCachingIterator($data_iterator, \CachingIterator::TOSTRING_USE_KEY),
      \RecursiveIteratorIterator::SELF_FIRST
    );
  }

  /**
   * Creates the rendered lines.
   *
   * @return array
   *   The array of rendered lines.
   */
  public function render() {
    $recursive_iter_iter = $this->getRecursiveIterator();

    $render = [];
    foreach ($recursive_iter_iter as $value) {
      $key = $recursive_iter_iter->key();
      $depth = $recursive_iter_iter->getDepth();

      $indent = str_repeat('  ', $depth);

      if (is_array($value)) {
        $render[] = "$indent'$key' => [";
      }
      else {
        // Quote the value if it's not an expression.
        // WTF, '£' is unicode???
        $first_char = mb_substr($value, 0, 1);
        if (!in_array($first_char, ['£', '\\', '['])) {
          $value = '"' . $value . '"';
        }

        $render[] = "$indent'$key' => $value,";

        if (!$recursive_iter_iter->hasNext() && $depth) {
          $indent = $depth ? str_repeat('  ', $recursive_iter_iter->getDepth() - 1) : '';

          $render[] = "$indent],";
        }
      }
    }

    return $render;
  }

}
