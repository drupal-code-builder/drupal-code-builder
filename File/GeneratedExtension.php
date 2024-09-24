<?php

namespace DrupalCodeBuilder\File;


/**
 * Represents a generated extension.
 *
 * This is a bit of a hack added on for FixtureGenerationDrushCommands.
 * However, it might ultimately replace the array of CodeFile objects returned
 * by Generate.
 */
class GeneratedExtension extends DrupalExtension {

  /**
   * Constructs a new extension.
   *
   * @param string $extension_type
   *   The type.
   * @param string $name
   *   The extension machine name.
   * @param array $extension_files
   *   The generated extension files.
   */
  public function __construct(string $extension_type, string $name, array $extension_files) {

    $this->type = $extension_type;
    $this->name = $name;
    $this->files = $extension_files;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFile(string $relative_file_path): bool {
    $relative_file_path = str_replace('%module', $this->name, $relative_file_path);

    return isset($this->files[$relative_file_path]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileContents($relative_file_path) {
    $relative_file_path = str_replace('%module', $this->name, $relative_file_path);

    return $this->files[$relative_file_path];
  }

}
