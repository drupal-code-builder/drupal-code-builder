<?php

namespace DrupalCodeBuilder\Test\Fixtures\File;

use DrupalCodeBuilder\File\DrupalExtension;

/**
 * Mock for an existing Drupal extension in tests.
 */
class MockableExtension extends DrupalExtension {

  protected $mockedFiles = [];

  /**
   * Set mocked code.
   *
   * @param string $relative_file_path
   *   The relative file path. This MUST use tokens such as '%module' instead of
   *   the extension name, as this class doesn't call getRealPath(), and can't
   *   because the name of the fixture module folder and the mocked module
   *   file data may not match.
   *   TODO: fix this.
   *
   * @param string $content
   *   The file contents.
   */
  public function setFile(string $relative_file_path, string $content) {
    $this->mockedFiles[$relative_file_path] = $content;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFile(string $relative_file_path): bool {
    if (isset($this->mockedFiles[$relative_file_path])) {
      return TRUE;
    }
    else {
      return parent::hasFile($relative_file_path);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileContents($relative_file_path) {
    if (isset($this->mockedFiles[$relative_file_path])) {
      return $this->mockedFiles[$relative_file_path];
    }
    else {
      return parent::getFileContents($relative_file_path);
    }
  }

}
