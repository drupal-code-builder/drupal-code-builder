<?php

namespace DrupalCodeBuilder\File;

/**
 * Represents a generated code file.
 *
 * This may include the code of file on disk if this exists and the file is of
 * a type that we are able to merge.
 */
class CodeFile implements \Stringable, CodeFileInterface {

  /**
   * The code for the file.
   *
   * @var string
   */
  protected string $code;

  /**
   * The filepath relative to the module.
   *
   * @var string
   */
  protected string $filepath;

  /**
   * Whether this file exists on disk.
   *
   * @var bool
   */
  protected bool $exists;

  /**
   * Whether this file has been merged with the existing file.
   *
   * @var bool
   */
  protected bool $merged;

  /**
   * Constructor.
   *
   * @param string $filepath
   * @param string $code
   * @param bool $exists
   * @param bool $merged
   */
  public function __construct(string $filepath, string $code, bool $exists, bool $merged) {
    $this->code = $code;
    $this->filepath = $filepath;
    $this->exists = $exists;
    $this->merged = $merged;
  }

  /**
   * Allows using this as a string, for BC.
   *
   * @return string
   *   The code.
   */
  public function  __toString() {
    return $this->code;
  }

  /**
   * {@inheritdoc}
   */
  public function getCode(): string {
    return $this->code;
  }

  /**
   * {@inheritdoc}
   */
  public function fileExists(): bool {
    return $this->exists;
  }

  /**
   * {@inheritdoc}
   */
  public function fileIsMerged(): bool {
    return $this->merged;
  }

}
