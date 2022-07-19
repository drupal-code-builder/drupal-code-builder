<?php

namespace DrupalCodeBuilder\Drush\Commands;

use Drupal\Core\File\FileSystemInterface;
use Drush\Commands\DrushCommands;
use DrupalCodeBuilder\File\GeneratedExtension;
use DrupalCodeBuilder\Generator\FormattingTrait\PHPFormattingTrait;
use PhpParser\Node\FunctionLike;

/**
 * Provides Drush commands for generating fixture code.
 */
class FixtureGenerationDrushCommands extends DrushCommands {

  use PHPFormattingTrait;

  /**
   * Write fixture modules used in tests.
   *
   * So far this only writes the test_analyze_9 module.
   *
   * @command dcb:fixtures
   *
   * @bootstrap full
   *
   * @aliases dcbf
   */
  public function fixtures() {
    \DrupalCodeBuilder\Factory::setEnvironmentLocalClass('DrushFixtures')
      ->setCoreVersionNumber(9);

    $task = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
    $component_data = $task->getRootComponentData();

    $module_name = 'test_analyze_9';

    // Add code lines that invoke hooks.
    // The token MODULE_HANDLER gets replaced with different ways of getting
    // the module handler service.
    // The token PREFIX is replaced to indicate in the hook name the way in
    // which the module handler service is obtained.
    $invoking_lines = [
      "MODULE_HANDLER->invokeAll('{$module_name}_PREFIX_all_cats', \$purr, \$miaow);",
      "MODULE_HANDLER->invoke('kitty_module', '{$module_name}_PREFIX_one_cat', \$purr, \$miaow);",
      "MODULE_HANDLER->alter('{$module_name}_PREFIX_change_cat', \$purr, \$miaow);",
      "MODULE_HANDLER->alter(['{$module_name}_PREFIX_change_cat_1', '{$module_name}_PREFIX_change_cat_2'], \$purr, \$miaow);",
    ];

    $component_data->set([
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'hooks' => [
        'hook_entity_bundle_info',
      ],
      'plugins' => [
        0 => [
          'plugin_type' => 'action',
          'plugin_name' => 'alpha',
          'injected_services' => [
            'module_handler',
          ],
        ],
      ],
      'readme' => TRUE,
    ]);

    $files = $task->generateComponent($component_data);

    // Convert the CodeFile objects to strings.
    $files = array_map(function($code_file) { return $code_file->getCode(); }, $files);

    // Add info to the README.
    $files['README.txt'] = "This module was generated automatically by the 'dcb:fixtures' command.";

    $extension = new GeneratedExtension('modules', 'test_analyze', $files);

    // Modify the code file strings in place so we retain the association with
    // the filename array key.
    $plugin_file = &$files['src/Plugin/Action/Alpha.php'];
    $ast = $extension->getFileAST('src/Plugin/Action/Alpha.php');
    $functions = $extension->getASTMethods($ast);

    // Invoking hooks with an injected service.
    $extra_lines = $invoking_lines;
    $extra_lines = str_replace('MODULE_HANDLER', '$this->moduleHandler', $extra_lines);
    $extra_lines = str_replace('PREFIX', 'di', $extra_lines);
    $extra_lines = $this->indentCodeLines($extra_lines, 2);

    $plugin_file = $this->insertLines($plugin_file, $functions['executeMultiple'], $extra_lines);

    $module_file = &$files['test_analyze_9.module'];
    $ast = $extension->getFileAST('test_analyze_9.module');
    $functions = $extension->getASTFunctions($ast);

    // Invoking hooks with the service() method on the Drupal static class.
    $extra_lines = $invoking_lines;
    $extra_lines = str_replace('MODULE_HANDLER', "\Drupal::service('module_handler')", $extra_lines);
    $extra_lines = str_replace('PREFIX', 'service', $extra_lines);
    $extra_lines = $this->indentCodeLines($extra_lines, 1);

    $module_file = $this->insertLines($module_file, $functions[0], $extra_lines);

    // Invoking hooks with the dedicated method on the Drupal static class.
    $extra_lines = $invoking_lines;
    $extra_lines = str_replace('MODULE_HANDLER', "\Drupal::moduleHandler()", $extra_lines);
    $extra_lines = str_replace('PREFIX', 'method', $extra_lines);
    $extra_lines = $this->indentCodeLines($extra_lines, 1);

    $module_file = $this->insertLines($module_file, $functions[0], $extra_lines);

    // Write the files.
    $package_root = realpath(\Composer\InstalledVersions::getInstallPath('drupal-code-builder/drupal-code-builder'));

    $module_folder = $package_root . '/Test/Fixtures/modules/test_analyze_9';
    if (!file_exists($module_folder)) {
      mkdir($module_folder);
    }

    foreach ($files as $filepath => $file_contents) {
      $absolute_filepath = $module_folder . '/' . $filepath;

      \Drupal::service('file_system')->prepareDirectory(dirname($absolute_filepath), FileSystemInterface::CREATE_DIRECTORY);

      $result = file_put_contents($absolute_filepath, $file_contents);
      $this->io()->text(dt('Written file: ' . $absolute_filepath));
    }
  }

  /**
   * Insert lines at the start of a function or method in a code file.
   *
   * @param string $code_file
   *   The complete code for the file.
   * @param \PhpParser\Node\FunctionLike $function_node
   *   The AST node for the function.
   * @param array $insert_code_lines
   *   An array of code lines to insert.
   *
   * @return string
   *   The new code for the file.
   */
  protected function insertLines(string $code_file, FunctionLike $function_node, array $insert_code_lines) {
    $start_line = $function_node->getAttributes()['startLine'];

    $code_file_lines = explode("\n", $code_file);
    array_splice($code_file_lines, $start_line, 0, $insert_code_lines);

    $code_file = implode("\n", $code_file_lines);

    return $code_file;
  }

}