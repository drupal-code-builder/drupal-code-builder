<?php

namespace DrupalCodeBuilder\Test\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * PHPUnit constraint for a code file string being valid PHP.
 */
class CodeIsValidPhp extends Constraint {

  protected $code;

  protected $output;

  /**
   * Evaluates the constraint for parameter $other. Returns true if the
   * constraint is met, false otherwise.
   */
  protected function matches($code): bool {
    // Escape all the backslashes. This is to prevent any escaped character
    // sequences from being formed by namespaces and long classes, e.g.
    // 'namespace Foo\testmodule;' will treat the '\t' as a tab character.
    // TODO: find a better way to do this that doesn't involve changing the
    // code.
    $escaped_code = str_replace('\\', '\\\\', $code);

    // Pass the code to PHP for linting.
    $output = NULL;
    $exit = NULL;
    $result = exec(sprintf('echo %s | php -l', escapeshellarg($escaped_code)), $output, $exit);

    if (!empty($exit)) {
      // Store data on this object for failureDescription() to access.
      $this->code = $code;
      $this->output = $output;

      return FALSE;
    }

    return TRUE;
  }

  public function toString(): string {
      return 'is valid PHP';
  }

  /**
   * Returns the description of the failure.
   *
   * The beginning of failure messages is "Failed asserting that" in most
   * cases. This method should return the second part of that sentence.
   *
   * @param mixed $other evaluated value or object
   */
  protected function failureDescription($other): string {
    // Get the code lines as an array so we can add the line numbers.
    $code_lines = explode("\n", $this->code);
    // Re-key it so the line numbers start at 1.
    $code_lines = array_combine(range(1, count($code_lines)), $code_lines);

    $indent_size = strlen((string) count($code_lines));

    array_walk($code_lines, function(&$line, $number) use ($indent_size) {
      $line = str_pad($number, $indent_size, ' ', \STR_PAD_LEFT) . ' ' . $line;
    });

    return 'the file is valid PHP, with errors: ' .
      implode("\n", $this->output) .
      "\nin the following code:\n" .
      implode("\n", $code_lines);
  }

}
