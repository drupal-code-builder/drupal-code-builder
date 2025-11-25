<?php

namespace DrupalCodeBuilder\File;

use DrupalCodeBuilder\Generator\RootComponent;

/**
 * Represents a generated code file.
 *
 * This may include the code of file on disk if this exists and the file is of
 * a type that we are able to merge.
 *
 * This goes through a refinement process, with some properties being set later
 * and some which hold earlier versions of data being unset.
 */
class CodeFile implements CodeFileInterface {

  /**
   * The array of body pieces.
   *
   * This is unset when the array is merged to produce the self::$body code.
   */
  protected array $body_pieces;

  /**
   * The code for the file.
   *
   * This is set when the self::$body_pieces are merged.
   */
  protected string $code;

  /**
   * The filepath relative to the generated component, without initial slash.
   *
   * An empty string means the base folder of the component.
   *
   * For example:
   *  - 'mymodule.module'
   *  - 'src/Plugin/Foo/AlphaPlugin.php'
   *  - 'tests/modules/testmodule.info.yml'
   */
  protected string $filepath;

  /**
   * Whether this file exists on disk.
   *
   * This is set by the FileAssembler after construction.
   */
  protected bool $exists;

  /**
   * Whether this file has been merged with the existing file.
   */
  protected bool $merged;

  /**
   * The build list tags.
   *
   * Sort of obsolete and not used any more by UIs?
   */
  public array $build_list_tags;

  /**
   * Constructs a new CodeFile.
   *
   * @param string $body_pieces
   *   An array of pieces to assemble in order to form the body of the file.
   *   These can be single lines, or larger chunks: they will be joined up by
   *   self::assembleCode(). The array may be keyed numerically, or the keys can
   *   be meaningful to the generator class: they are immaterial to the this
   *   class.
   * @param array $build_list_tags
   *   The array of build list tags.
   * @param bool $merged
   *   Boolean indicating whether the generated code has merged in code from an
   *   existing file.
   */
  public function __construct(
    $body_pieces,
    $build_list_tags = [],
    $merged = FALSE,
  ) {
    $this->body_pieces = $body_pieces;
    $this->build_list_tags = $build_list_tags;
    $this->merged = $merged;
  }

  /**
   * Sets whether this file already exists on disk.
   *
   * @param bool $exists
   *   Whether the file exists.
   */
  public function setExists(bool $exists): void {
    $this->exists = $exists;
  }

  /**
   * Sets the filepath.
   *
   * @param string $filepath
   *   The filepath relative to the generated root component.
   */
  public function setFilepath($filepath): void {
    $this->filepath = $filepath;
  }

  /**
   * Assembles the code from the array of lines.
   *
   * This unsets self::$body_pieces.
   */
  public function assembleCode(): void {
    $this->code = implode("\n", $this->body_pieces);
    unset($this->body_pieces);
  }

  /**
   * Replaces the tokens in the code and filepath.
   *
   * Expects self::$code and self::$filepath to be set: assembleCode() must
   * have been called already.
   *
   * @param \DrupalCodeBuilder\Generator\RootComponent $closest_requesting_root
   *   The closest requesting root component.
   */
  public function replaceTokens(RootComponent $closest_requesting_root) {
    $variables = $closest_requesting_root->getReplacements();

    $this->code = strtr($this->code, $variables);
    $this->filepath = strtr($this->filepath, $variables);
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

  /**
   * Gets the relative filepath.
   *
   * @internal
   *
   * @todo Make this part of the API in 4.3.0.
   *
   * @return string
   *   The filepath.
   */
  public function getFilePath(): string {
    return $this->filepath;
  }

}
