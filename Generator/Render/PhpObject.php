<?php

namespace DrupalCodeBuilder\Generator\Render;

use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;

/**
 * Renderer for PHP object creation.
 *
 * TODO Only handles objects created with new() and rendered inline for now.
 */
class PhpObject {

  /**
   * Creates a new object value for an object instantiated with 'new()'.
   *
   * @param string $class
   *   The full class name.
   * @param array|string|null $parameters
   *   (optional) Either a single parameter, or an array of parameters.
   */
  public static function new(string $class, array|string|null $parameters = NULL) {
    if (is_string($parameters)) {
      $parameters = [$parameters];
    }
    if (substr($class, 0, 1) != '\\') {
      $class = '\\' . $class;
    }

    return new static($class, $parameters);
  }

  /**
   * Constructor. Do not call directly.
   */
  public function __construct(
    protected string $class,
    protected ?array $parameters,
  ) {}

  /**
   * Renders the object.
   *
   * TODO Params not used yet.
   *
   * @return string|array
   */
  public function render(bool $inline, int $level): string|array {
    $line = 'new ' . $this->class . '(';

    assert(count($this->parameters) < 2, 'More than 1 parameter not yet supported, YAGNI!');

    foreach ($this->parameters as $parameter) {
      $line .= PhpValue::create($parameter)->renderInline();
    }

    $line .= ')';

    return $line;
  }

}
