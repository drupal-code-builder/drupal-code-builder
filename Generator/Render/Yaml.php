<?php

namespace DrupalCodeBuilder\Generator\Render;

use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;

/**
 * Renders PHP arrays into YAML.
 *
 * We don't use Symfony Yaml's dumper, because that:
 * - Excessively quotes strings that don't need quoting
 * - Doesn't allow fine control of where to switch to inline layout: see
 *   https://github.com/symfony/symfony/issues/19014#event-688175812.
 * - Doesn't allow blank lines between sibling items.
 */
class Yaml {

  use PHPFormattingTrait;

  /**
   * Represents a nesting level that cannot be reached.
   *
   * Used as a value for $inline_from_level and $blank_lines_until_level.
   *
   * @var bool
   */
  public const NEVER = -1;

  /**
   * Creates a new Yaml renderer.
   *
   * @param array $data
   *   An array of data to render. This can contain:
   *   - strings
   *   - numerics
   *   - booleans
   *   - NULLs
   *   - arrays, which can be nested, and can be empty
   *   - nested objects of this class, which allows to vary the inline and blank
   *     lines parameters at different levels within one YAML document.
   * @param int $inline_from_level
   *   (optional) The first level at which formatting switches to inline. The
   *   top level is 0. Defaults to self::NEVER.
   * @param int $blank_lines_until_level
   *   (optional) The last level for which blank lines are inserted between
   *   siblings. Defaults to self::NEVER.
   */
  public static function create(
    array $data,
    int $inline_from_level = self::NEVER,
    int $blank_lines_until_level = self::NEVER,
  ) {
    return new static($data, $inline_from_level, $blank_lines_until_level);
  }

  /**
   * Constructor.
   *
   * @internal
   */
  public function __construct(
    protected array $data,
    protected int $inline_from_level,
    protected int $blank_lines_until_level,
  )
  {}

  /**
   * Renders the data.
   *
   * @return array
   *   An array of code lines. Note that this does not have a terminal empty
   *   line, as this causes too many problems with nesting.
   */
  public function render(): array {
    if ($this->inline_from_level == 0) {
      return [
        $this->renderDataInline($this->data),
      ];
    }
    else {
      $lines = $this->renderDataMultiline($this->data, 0);

      return $lines;
    }
  }

  /**
   * Renders data as multiple lines.
   *
   * @param array $data
   *   The data to render.
   * @param int $level
   *   The level of the data in the overall structure. The top level is 0.
   *
   * @return array
   *   An array of YAML code lines.
   */
  protected function renderDataMultiline(array $data, int $level): array {
    $lines = [];

    $is_list = array_is_list($data);

    foreach ($data as $key => $value) {
      $indent = str_repeat('  ', $level);

      $line = $indent;

      if ($is_list) {
        $line .= '-';
      }
      else {
        $line .= $key;
        $line .= ':';
      }

      if (is_scalar($value)) {
        // Render a scalar.
        $line .= ' ';
        $line .= $this->renderScalar($value);

        $lines[] = $line;
      }
      elseif (is_null($value)) {
        $line .= ' ';
        $line .= 'null';
        $lines[] = $line;
      }
      elseif ($value instanceof static) {
        // Nested YAML renderer.
        $nested_lines = $value->render();

        // Detect inline.
        if ($value->inline_from_level == 0) {
          $line .= ' ';
          $line .= $nested_lines[0];

          $lines[] = $line;
        }
        else {
          $nested_lines = $this->indentCodeLines($nested_lines, $level + 1);

          $lines[] = $line;
          $lines = array_merge($lines, $nested_lines);
        }
      }
      else {
        // Render an array.
        if (empty($value)) {
          $line .= ' ';
          // Empty can be either {} or [] in YAML, but we match what Symfony's
          // Yaml dumper does. Two spaces inside is the most common format in
          // Drupal core.
          $line .= '{  }';
          $lines[] = $line;
        }
        elseif ($this->inline_from_level > 0 && $level + 1 >= $this->inline_from_level) {
          // Render inline.
          $line .= ' ';
          $line .= $this->renderDataInline($value);
          $lines[] = $line;
        }
        else {
          // Render as multiple lines.
          $lines[] = $line;

          $lines = array_merge($lines, $this->renderDataMultiline($value, $level + 1));
        }
      }

      // Add a blank line, unless we're on the last item.
      if ($level <= $this->blank_lines_until_level && $key != array_key_last($data)) {
        $lines[] = '';
      }
    } // foreach $data

    return $lines;
  }

  /**
   * Renders data inline.
   *
   * @param array $data
   *   The data to render.
   *
   * @return string
   *   A single line of YAML.
   */
  protected function renderDataInline(array $data): string {
    $items = [];

    $is_list = array_is_list($data);

    foreach ($data as $key => $value) {
      $item = '';

      if (!$is_list) {
        $item .= $key;
        $item .= ': ';
      }

      if (is_scalar($value)) {
        // Render a scalar.
        $item .= $this->renderScalar($value);
      }
      else {
        // Render an array.
        $item .= $this->renderDataInline($value);
      }

      $items[] = $item;
    }

    $string = '';

    if ($is_list) {
      $string .= '[';
      $string .= implode(', ', $items);
      $string .= ']';
    }
    else {
      // Drupal core's style (undocumented!) for YAML is for a space inside
      // associative array brackets.
      $string .= '{ ';
      $string .= implode(', ', $items);
      $string .= ' }';
    }

    return $string;
  }

  /**
   * Renders a scalar value.
   *
   * @param mixed $value
   *   The value to render.
   *
   * @return string
   *   The YAML string representation of the value.
   */
  protected function renderScalar($value): string {
    if (is_bool($value)) {
      return match($value) {
        TRUE => 'true',
        FALSE => 'false',
      };
    }
    else {
      // Determine whether to quote a string value.
      $quote = FALSE;
      // Starts with a special character.
      $quote |= in_array(substr($value, 0, 1), ['@', '\\']);
      // Contains a ':'.
      $quote |= str_contains($value, ':');
      // Is a string that would be interpreted as a boolean if bare.
      $quote |= preg_match('/^(yes|no|true|false)$/i', $value);

      if ($quote) {
        return "'" . (string) $value . "'";
      }
      else {
        return (string) $value;
      }
    }
  }

}
