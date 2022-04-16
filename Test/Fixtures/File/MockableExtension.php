<?php

namespace DrupalCodeBuilder\Test\Fixtures\File;

use DrupalCodeBuilder\File\DrupalExtension;

class MockableExtension extends DrupalExtension {

  protected $mockedFiles = [];

  public function setFile(string $relative_file_path, string $content) {
    $this->mockedFiles[$relative_file_path] = $content;
  }

  public function hasFile(string $relative_file_path): bool {
    if (isset($this->mockedFiles[$relative_file_path])) {
      return TRUE;
    }
    else {
      return parent::hasFile($relative_file_path);
    }
  }

  protected function getFileContents($relative_file_path) {
    if (isset($this->mockedFiles[$relative_file_path])) {
      return $this->mockedFiles[$relative_file_path];
    }
    else {
      return parent::getFileContents($relative_file_path);
    }
  }

}
