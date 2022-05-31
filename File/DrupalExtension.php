<?php

namespace DrupalCodeBuilder\File;

use Symfony\Component\Yaml\Yaml;
use PhpParser\ParserFactory;
use PhpParser\Error;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Use_;

/**
 * Represents a Drupal extension's files in the codebase.
 */
class DrupalExtension {

  /**
   * The extension type, e.g. 'module'.
   *
   * @var string
   */
  protected $type;

  /**
   * The extension name.
   *
   * @var string
   */
  protected $name;

  /**
   * The given extension path.
   *
   * @var string
   */
  protected $path;

  /**
   * Constructs a new extension.
   *
   * @param string $extension_type
   *   The type.
   * @param string $extension_path
   *   The path.
   */
  public function __construct(string $extension_type, string $extension_path) {
    if (!file_exists($extension_path)) {
      throw new \Exception("Path $extension_path does not exist.");
    }

    $this->path = $extension_path;
    $this->type = $extension_type;
    $this->name = basename($this->path);
  }

  /**
   * Determines whether a file exists in the extension.
   *
   * @param string $relative_file_path
   *   The filepath relative to the extension folder. Use '%module' to represent
   *   the extension's machine name in the filepath.
   *
   * @return bool
   *   TRUE if the file exists, FALSE if not.
   */
  public function hasFile(string $relative_file_path): bool {
    return file_exists($this->getRealPath($relative_file_path));
  }

  /**
   * Gets a range of lines from a file.
   *
   * @param string $relative_file_path
   *   The filepath relative to the extension folder. Use '%module' to represent
   *   the extension's machine name in the filepath.
   * @param int $start
   *   The index of the start line, where 1 is the first line of the file.
   * @param int $end
   *   The index of the end line.
   *
   * @return array
   */
  public function getFileLines(string $relative_file_path, int $start, int $end): array {
    $lines = explode("\n", $this->getFileContents($relative_file_path));

    $slice = array_slice($lines, $start - 1, $end - $start + 1);
    return $slice;
  }

  /**
   * Gets the YAML data from a file,
   *
   * @param string $relative_file_path
   *   The filepath relative to the extension folder. Use '%module' to represent
   *   the extension's machine name in the filepath.
   *
   * @return array
   *   The YAML data.
   */
  public function getFileYaml(string $relative_file_path): array {
    // TODO: throw if not a .yml file.

    $yml = $this->getFileContents($relative_file_path);

    $value = Yaml::parse($yml);

    // Cast to array in case the .yml file is empty.
    return (array) $value;
  }

  /**
   * Gets the AST of a PHP file.
   *
   * @param string $relative_file_path
   *   The filepath relative to the extension folder. Use '%module' to represent
   *   the extension's machine name in the filepath.
   *
   * @return array
   *   The AST array.
   */
  public function getFileAST(string $relative_file_path): array {
    $php = $this->getFileContents($relative_file_path);

    $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    try {
      $ast = $parser->parse($php);
    }
    catch (Error $error) {
      // TODO: warn somehow?
      return NULL;
    }

    return $ast;
  }

  /**
   * Gets the import statements from an AST.
   *
   * @param array $ast
   *   The AST as returned by getFileAST().
   *
   * @return array
   *   An array of use nodes.
   */
  public function getASTImports(array $ast): array {
    $ast = array_filter($ast, function($node) {
      return $node instanceof Use_;
    });

    return $ast;
  }

  /**
   * Gets the functions from an AST.
   *
   * @param array $ast
   *   The AST as returned by getFileAST().
   *
   * @return array
   *   An array of function nodes.
   */
  public function getASTFunctions(array $ast): array {
    $ast = array_filter($ast, function($node) {
      return $node instanceof Function_;
    });

    return $ast;
  }

  /**
   * Gets the absolute path from a relative path.
   *
   * This does not check the file exists.
   *
   * @param string $relative_file_path
   *   The filepath relative to the extension folder. Use '%module' to represent
   *   the extension's machine name in the filepath.
   *
   * @return string
   *   The absolute filepath, with the '%module' wildcard replaced.
   */
  protected function getRealPath(string $relative_file_path): string {
    $relative_file_path = str_replace('%module', $this->name, $relative_file_path);
    $absolute_file_path = $this->path . '/' . $relative_file_path;
    return $absolute_file_path;
  }

  /**
   * Gets the contents of a file.
   *
   * @param string $relative_file_path
   *   The filepath relative to the extension folder. Use '%module' to represent
   *   the extension's machine name in the filepath.
   *
   * @return string
   *   The file contents.
   */
  protected function getFileContents($relative_file_path) {
    return file_get_contents($this->getRealPath($relative_file_path));
  }

}
