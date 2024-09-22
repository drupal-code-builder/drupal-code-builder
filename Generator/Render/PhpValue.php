<?php

namespace DrupalCodeBuilder\Generator\Render;

use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;

/**
 * Renderer for a PHP value.
 *
 * This can be rendered either as a single string of text, or an array of lines.
 * Furthermore, an array of lines can be embedded in an outer render such as a
 * PHP attribute, with its own indentation level.
 */
class PhpValue {

  use PHPFormattingTrait;

  /**
   * Whether the value is rendered as a single-line or multiple lines.
   *
   * NULL until set by renderMultiline() or renderInline().
   *
   * @var bool|null
   */
  protected ?bool $inline = NULL;

  /**
   * Creator.
   *
   * @param mixed $value
   *   The value to render.
   * @param int $embedded_level
   *   (optional) The level of indentation this is being rendered within, if it
   *   is being embedded in another render. For example, this could be an array
   *   being rendered inside a PHP attribute. Defaults to 0, which implies there
   *   is no embedding. Outer array brackets are not produced when embedded,
   *   because the embedding lines may need the output an array key.
   *
   * @return static
   *   A PhpValue renderer.
   */
  public static function create(mixed $value, int $embedded_level = 0): static {
    return new static($value, $embedded_level);
  }

  public function __construct(
    protected mixed $value,
    protected int $embeddedLevel,
  ) {}

  /**
   * Renders the value with linebreaks.
   *
   * @return array
   *   An array of lines.
   */
  public function renderMultiline(): array {
    $this->inline = FALSE;

    return $this->render($this->value, 0);
  }

  /**
   * Renders the value as a single string.
   *
   * @return string
   *   The rendered value.
   */
  public function renderInline(): string {
    $this->inline = TRUE;

    if ($this->embeddedLevel) {
      throw new \InvalidArgumentException('Nested does not apply to inline rendering.');
    }

    return $this->render($this->value, 0);
  }

  /**
   * General renderer.
   *
   * @param mixed $value
   *
   * @return mixed
   *   Either a single string or an array of strings, depending on whether
   *   rendering is done inline or not.
   */
  protected function render(mixed $value, int $level): mixed {
    if (is_scalar($value) || is_null($value)) {
      // WTF NULL is not scalar.
      $render = $this->renderScalar($value);

      // If rendering a single scalar value as a multiple, return an array.
      if (!$this->inline && $level == 0) {
        $render = [$render];
      }

      return $render;
    }
    elseif (is_array($value)) {
      if ($this->inline) {
        return '[' . $this->renderArrayInline($value, $level) . ']';
      }
      else {
        $indent = str_repeat('  ', $this->embeddedLevel + $level);

        if ($level == 0 && !$this->embeddedLevel) {
          // Top level needs its own outer brackets rendering, unless it is
          // embedded, as in that case the first line will have the key on it
          // too.
          $lines = [
            $indent . '[',
            ...$this->renderArrayMultiline($value, $level),
            $indent . ']',
          ];
        }
        else {
          $lines = $this->renderArrayMultiline($value, $level);
        }

        return $lines;
      }
    }
  }

  /**
   * Renders an array's items on one line. Brackets are not included.
   *
   * @param array $value
   *   The array to render.
   * @param $int
   *   The nesting level.
   *
   * @return string
   *   The array's elements as a string.
   */
  protected function renderArrayInline(array $value, $level): string {
    $pieces = [];
    foreach ($value as $item_key => $item_value) {
      $piece = '';

      // Render a non-numeric key.
      if (!is_numeric($item_key)) {
        $piece .= $this->renderScalar($item_key) . ' => ';
      }

      // Render the value.
      $piece .= $this->render($item_value, $level + 1);

      $pieces[] = $piece;
    }

    return implode(', ', $pieces);
  }

  /**
   * Renders an array's items on multiple lines. Brackets are not included.
   *
   * @param array $value
   *   The array to render.
   * @param $int
   *   The nesting level.
   *
   * @return string[]
   *   An array of strings.
   */
  protected function renderArrayMultiline(array $value, int $level): array {
    $indent = str_repeat('  ', $this->embeddedLevel + $level + 1);

    $lines = [];
    foreach ($value as $item_key => $item_value) {
      $line = $indent;

      // Render a non-numeric key.
      if (!is_numeric($item_key)) {
        $line .= $this->renderScalar($item_key) . ' => ';
      }

      // Render the value.
      $value_render = $this->render($item_value, $level + 1);

      if (is_string($value_render)) {
        $line .= $value_render;
        $line .= ',';

        $lines[] = $line;
      }
      else {
        $line .= '[';
        $lines[] = $line;

        $lines = array_merge($lines, $value_render);

        $lines[] = $indent . "],";
      }
    }

    return $lines;
  }

  /**
   * Renders a scalar value as a PHP string.
   *
   * @param mixed $value
   *   The value to render.
   *
   * @return string
   *   A string of PHP code representing the value.
   */
  protected function renderScalar(mixed $value): string {
    // Handle natives which are represented as strings.
    if (is_numeric($value)) {
      return $value;
    }
    elseif (in_array($value, ['TRUE', 'FALSE', 'NULL'])) {
      return $value;
    }

    if (is_string($value)) {
      // Special case for class constants: we assume a string starting with a
      // '\' is such and thus is not quoted.
      if (!str_starts_with($value, '\\')) {
        $value_string = '\'' . $value . '\'';
      }
      else {
        $value_string = $value;
      }
    }
    elseif (is_numeric($value)) {
      $value_string = (string) $value;
    }
    elseif (is_bool($value)) {
      $value_string = $value ? 'TRUE' : 'FALSE';
    }
    elseif (is_null($value)) {
      return 'NULL';
    }
    else {
      dump($value);
      throw new \Exception("Scalar value not handled!");
    }

    return $value_string;
  }

}
