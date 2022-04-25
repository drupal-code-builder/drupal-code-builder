<?php

namespace DrupalCodeBuilder\File;

/**
 * Represents a generated code file.
 *
 * This may include the code of file on disk if this exists and the file is of
 * a type that we are able to merge.
 */
interface CodeFileInterface {

  /**
   * Gets the code for the file.
   *
   * @return string
   *   The code.
   */
  public function getCode(): string;

  /**
   * Determines whether the file this code is for already exists.
   *
   * @return bool
   *   TRUE if the file exists, FALSE if not.
   */
  public function fileExists(): bool;

  /**
   * Determines whether the generated code has been merged with existing code.
   *
   * @return bool
   *   TRUE if the existing code has been merged; FALSE if not. Merged code may
   *   overwrite sections of existing code, for example, if both versions have
   *   the same function or method.
   */
  public function fileIsMerged(): bool;

}
