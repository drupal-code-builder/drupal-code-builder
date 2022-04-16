<?php

namespace DrupalCodeBuilder\File;

/**
 * Represents a generated code file.
 *
 * This may include the code of file on disk if this exists and the file is of
 * a type that we are able to merge.
 */
interface CodeFileInterface {

  public function getCode(): string;

  public function fileExists(): bool;

  public function fileIsMerged(): bool;

}
