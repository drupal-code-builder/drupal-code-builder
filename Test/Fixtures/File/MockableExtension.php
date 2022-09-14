<?php

namespace DrupalCodeBuilder\Test\Fixtures\File;

use DrupalCodeBuilder\File\DrupalExtension;
use Symfony\Component\Yaml\Yaml;

/**
 * Mock for an existing Drupal extension in tests.
 */
class MockableExtension extends DrupalExtension {

  protected $mockedFiles = [];

  /**
   * Set mocked code.
   *
   * @param string $relative_file_path
   *   The relative file path. This should generally NOT use the '%module'
   *   token, as calls to this from Generator classes pass in the real filename.
   *
   * @param string $content
   *   The file contents.
   */
  public function setFile(string $relative_file_path, string $content) {
    $this->mockedFiles[$relative_file_path] = $content;
  }

  /**
   * Mocks an info file.
   *
   * WARNING: Assumes we're only testing modules!
   *
   * @param string $name
   *   The name of the extension the info file is for.
   * @param array $data
   *   (optional) Additional data for the info file's YAML.
   * @param string $path
   *   (optional) The path to the info file, with a trailing slash. Defaults to
   *   putting the info file in the extension root.
   */
  public function mockInfoFile(string $name, array $data = [], string $path = '') {
    $relative_file_path = $path . $name . '.info.yml';
    $data += [
      'name' => $name,
      'type' => 'module',
      'description' => 'Description.',
      'core_version_requirement' => '^9',
    ];

    $this->mockedFiles[$relative_file_path] = Yaml::dump($data);
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
