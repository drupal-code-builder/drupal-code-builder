<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\AnalyzeModule.
 */

namespace DrupalCodeBuilder\Task;

/**
 * Task handler for analyzing an existing module.
 */
class AnalyzeModule extends Base {

  /**
   * The sanity level this task requires to operate.
   */
  protected $sanity_level = 'component_data_processed';

  /**
   * Helper function to get all function names from a file.
   *
   * @param $file
   *  A complete filename from the Drupal root, eg 'modules/user/user.module'.
   */
  public function getFileFunctions($file) {
    $code = file_get_contents($file);
    //drush_print($code);

    $matches = [];
    $pattern = "/^function (\w+)/m";
    preg_match_all($pattern, $code, $matches);

    return $matches[1];
  }

  /**
   * Get the hooks that a module invents, i.e., the ones it should document.
   *
   * @param $module_root_name
   *  The module root name.
   *
   * @return
   *  An array of hooks and their parameters. The hooks are deduced from the
   *  calls to functions such as module_invoke_all(), and the probable
   *  parameters are taken from the variables passed to the call. The keys of
   *  the array are hook short names; the values are the parameters string,
   *  with separating commas but without the outer parentheses. E.g.:
   *    'foo_insert' => '$foo, $bar'
   *  These may not be complete if invocations omit any optional parameters.
   */
  public function getInventedHooks($module_root_name) {
    // Get the module's folder.
    $module_folder = $this->environment->getExtensionPath('module', $module_root_name);

    // Bail if Drupal core doesn't know about the module because it doesn't
    // exist yet, or if the folder doesn't exist yet: there is nothing to do.
    if (empty($module_folder) || !file_exists($module_folder)) {
      return [];
    }

    // An array of short hook names that we'll populate from what we extract
    // from the files.
    $hooks = [];

    // Only consider hooks which are invented by this module: it is legitimate
    // for modules to invoke hooks invented by other modules. We assume the
    // module follows the convention of using its name as a prefix.
    $hook_prefix = $module_root_name . '_';

    $module_files_iterator = ComponentFolderRecursiveFilterIterator::factory($module_folder);
    foreach ($module_files_iterator as $filename => $object) {
      $contents = file_get_contents("$filename");

      // List of patterns to match on.
      // The array keys are arbitrary.
      // They should all have capturing groups for:
      //  - 1. hook name
      //  - 2. optional parameters
      $hook_invocation_patterns = [
        'invokeAll' => [
          // The pattern for this item.
          'pattern' => "/
            invokeAll \(
              ' ( $hook_prefix \w+ ) ' # Hook name, with the hook prefix.
              (?:
                , \s*
                (
                  [^)]* # Capture further parameters: anything up to the closing ')'.
                )
              )? # The further parameters are optional.
          /x",
        ],
        'invoke' => [
          'pattern' =>
            "/
            invoke \(
              [^,]+ # The \$module parameter.
              , \s*
              ' ( $hook_prefix \w+ ) ' # Hook name, with the hook prefix.
              (?:
                , \s*
                (
                  [^)]* # Capture further parameters: anything up to the closing ')'.
                )
              )? # The further parameters are optional.
            /x",
        ],
        'alter_single' => [
          'pattern' =>
            "/
            alter \(
              ' ( $hook_prefix \w+ ) ' # Hook name, with the hook prefix.
              (?:
                , \s*
                (
                  [^)]* # Capture further parameters: anything up to the closing ')'.
                )
              )? # The further parameters are optional.
            /x",
          // A process callback to apply to each hook name the pattern finds.
          // This is because the hook name in drupal_alter() needs a suffix to
          // be added to it.
          'process callback' => function ($hook_name) {
            return [$hook_name . '_alter'];
          },
        ],
        'alter_multiple' => [
          'pattern' =>
            "/
            alter \(
              # Array of hook names.
              \[
                (
                  # Hook names, with the hook prefix.
                  # Capture as the whole thing, split in the process callback.
                  (?: ' $hook_prefix \w+ ' ,? \ ? ) *
                )
              \]
              (?:
                , \s*
                (
                  [^)]* # Capture further parameters: anything up to the closing ')'.
                )
              )? # The further parameters are optional.
            /x",
          // A process callback to apply to each hook name the pattern finds.
          'process callback' => function ($hook_array_string) {
            // Split the code string that's an array of hook names.
            $matches = [];
            preg_match_all('@\w+@', $hook_array_string, $matches);

            // Add the '_alter' suffix to all hook namaes,
            $hook_names = [];
            foreach ($matches[0] as $hook_name) {
              $hook_names[] = $hook_name . '_alter';
            }

            return $hook_names;
          },
        ],
        // module_invoke_all() calls.
        'module_invoke_all' => [
          'pattern' =>
            "/
            module_invoke_all \(
              ' ( $hook_prefix \w+ ) ' # Hook name, with the hook prefix.
              (?:
                , \s*
                (
                  [^)]* # Capture further parameters: anything up to the closing ')'.
                )
              )? # The further parameters are optional.
            /x",
        ],
        // module_invoke() calls.
        'module_invoke' => [
          'pattern' =>
            "/
            module_invoke \(
              [^,]+ # The \$module parameter.
              , \s*
              ' ( $hook_prefix \w+ ) ' # Hook name, with the hook prefix.
              (?:
                , \s*
                (
                  [^)]* # Capture further parameters: anything up to the closing ')'.
                )
              )? # The further parameters are optional.
            /x",
        ],
        // drupal_alter() calls.
        'drupal_alter' => [
          'pattern' =>
            "/
            drupal_alter \(
              ' ( $hook_prefix \w+ ) ' # Hook name, with the hook prefix.
              (?:
                , \s*
                (
                  [^)]* # Capture further parameters: anything up to the closing ')'.
                )
              )? # The further parameters are optional.
            /x",
          // A process callback to apply to each hook name the pattern finds.
          // This is because the hook name in drupal_alter() needs a suffix to
          // be added to it.
          'process callback' => function ($hook_name) {
            return [$hook_name . '_alter'];
          },
        ],
      ];

      // Process the file for each pattern.
      foreach ($hook_invocation_patterns as $pattern_info) {
        $pattern = $pattern_info['pattern'];

        $matches = [];
        preg_match_all($pattern, $contents, $matches);
        // Matches are:
        //  - 1: the first parameter, which is the hook short name.
        //  - 2: the remaining parameters, if any.

        // If we get matches, turn then into keyed arrays and merge them into
        // the cumulative array. This removes duplicates (caused by a hook being
        // invoked in different files).
        if (!empty($matches[1])) {
          //drush_print_r($matches);
          $file_hooks = array_combine($matches[1], $matches[2]);
          //drush_print_r($file_hooks);

          foreach ($file_hooks as $hook_short_name => $parameters) {
            // Perform additional processing on the hook short name, if needed.
            // This can return more than one hook.
            if (isset($pattern_info['process callback'])) {
              $hook_short_names = $pattern_info['process callback']($hook_short_name);
            }
            else {
              $hook_short_names = [$hook_short_name];
            }

            foreach ($hook_short_names as $hook_short_name_inner) {
              // If this hook is already in our list, we take the longest parameters
              // string, on the assumption that this may be more complete if some
              // parameters are options.
              if (isset($hooks[$hook_short_name_inner])) {
                // Replace the existing hook if the new parameters are longer.
                if (strlen($parameters) > strlen($hooks[$hook_short_name_inner])) {
                  $hooks[$hook_short_name_inner] = $parameters;
                }
              }
              else {
                $hooks[$hook_short_name_inner] = $parameters;
              }
            }
          }
        }
      }
    }
    //drush_print_r($hooks);

    return $hooks;
  }

}

/**
 * Recursive filter iterator to skip unwanted files from folder iteration.
 *
 * @see http://stackoverflow.com/questions/18270629/php-recursive-directory-iterator-ignore-certain-files
 * @see http://paulyg.github.io/blog/2014/01/31/using-phps-recursivefilteriterator.html
 */
class ComponentFolderRecursiveFilterIterator extends \RecursiveFilterIterator {

    public static function factory($dir) {
        return new \RecursiveIteratorIterator(
            new static(
                new \RecursiveDirectoryIterator(
                    $dir,
                    \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
                )
            )
        );
    }

  #[\ReturnTypeWillChange]
  public function accept() {
    $current_filename = $this->current()->getFilename();

    if (is_dir($current_filename)) {
      return TRUE;
    }

    $current_filename_extension = pathinfo($current_filename, PATHINFO_EXTENSION);

    // Filter out hidden files: don't want to be scanning .git folders!
    if (str_starts_with($current_filename, '.')) {
      return FALSE;
    }

    // List of file extensions we should skip.
    $unwanted_extensions = [
      'yml',
      'txt',
      'md',
      // Module folders (well mine at least) frequently contain patch files and
      // other associated cruft which we want to skip.
      'patch',
      'orig',
      'rej',
      // Image files.
      'gif',
      'png',
      'jpg',
      'jpeg',
    ];
    if (in_array($current_filename_extension, $unwanted_extensions)) {
      return FALSE;
    }

    // If you're still here then I guess you pass.
    return TRUE;
  }

}
