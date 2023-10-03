<?php

namespace DrupalCodeBuilder\Task\Generate;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\File\CodeFile;
use DrupalCodeBuilder\File\DrupalExtension;
use DrupalCodeBuilder\Generator\Collection\ComponentCollection;
use DrupalCodeBuilder\Generator\RootComponent;

/**
 * Task helper for generating files.
 */
class FileAssembler {

  /**
   * Generate code files.
   *
   * @param $component_data
   *   The component data from the initial request.
   * @param \DrupalCodeBuilder\Generator\Collection\ComponentCollection $component_collection
   *   The component collection.
   *
   * @return
   *  An array of files ready for output. Keys are the filepath and filename
   *  relative to the module folder (eg, 'foo.module', 'tests/module.test');
   *  values are strings of the contents for each file.
   */
  public function generateFiles($component_data, ComponentCollection $component_collection, DrupalExtension $existing_extension = NULL) {
    $component_list = $component_collection->getComponents();

    // Let each file component in the tree gather data from its own children.
    $this->collectFileContents($component_collection);

    // Build files.
    // Get info on files. All components that wish to provide a file should have
    // registered themselves as first-level children of the root component.
    $files = $this->collectFiles($component_collection);

    // Filter files according to the requested build list.
    // TODO: TEMP!
    // if (isset($component_data['requested_build'])) {
    //   $component_collection->getRootComponent()->applyBuildListFilter($files, $component_data['requested_build'], $component_data);
    // }

    // Then we assemble the files into a simple array of full filename and
    // contents.
    $files_assembled = $this->assembleFiles($component_collection, $files, $existing_extension);

    return $files_assembled;
  }

  /**
   * Allow file components to gather data from their child components.
   *
   * @param \DrupalCodeBuilder\Generator\Collection\ComponentCollection $component_collection
   *   The component collection.
   */
  protected function collectFileContents(ComponentCollection $component_collection) {
    // Iterate over all file-providing components, i.e. one level below the root
    // of the tree.
    $file_components = $component_collection->getContainmentTreeChildren($component_collection->getRootComponent());
    foreach ($file_components as $file_component) {
      // Let the file component run over its children iteratively.
      // (Not literally ;)
      $file_component->collectComponentContents($component_collection);
    }
  }

  /**
   * Collect file data from components.
   *
   * This assembles an array, keyed by an arbitrary ID for the file, whose
   * values are arrays with the following properties:
   *  - 'body': An array of lines of content for the file.
   *  - 'path': The path for the file, relative to the module folder.
   *  - 'filename': The filename for the file.
   *
   * @param $component_list
   *  The component list.
   *
   * @return
   *  An array of file info, keyed by arbitrary file ID. Each array contains:
   *    - filename
   *    - path
   *    - body
   *    - source_component_id
   *    - merged: TRUE if the file has been merged with an existing file. Unset
   *      otherwise.
   */
  protected function collectFiles(ComponentCollection $component_collection) {
    $file_info = [];

    // Components which provide a file should have registered themselves as
    // children of the root component.
    $file_components = $component_collection->getContainmentTreeChildren($component_collection->getRootComponent());
    /** $var \DrupalCodeBuilder\Generator\File $child_component */
    foreach ($file_components as $id => $child_component) {
      $file_info_item = $child_component->getFileInfo();
      if (is_array($file_info_item)) {
        // Prepend the component_base_path to the path.
        // @todo Make use of File::getFilename().
        $component_base_path = $child_component->component_data->component_base_path->value;
        if (!empty($component_base_path)) {
          if (empty($file_info_item['path'])) {
            $file_info_item['path'] = $child_component->component_data->component_base_path->value;
          }
          else {
            $file_info_item['path'] = $child_component->component_data->component_base_path->value
            . '/'
            . $file_info_item['path'];
          }
        }

        // Add the source component ID.
        $file_info_item['source_component_id'] = $id;

        $file_info[$id] = $file_info_item;
      }
    }

    return $file_info;
  }

  /**
   * Assemble file info into filename and code.
   *
   * @param \DrupalCodeBuilder\Generator\Collection\ComponentCollection $component_collection
   *   The component collection.
   * @param $files
   *  An array of file info, as compiled by collectFiles().
   *
   * @return
   *  An array of files ready for output. Keys are the filepath and filename
   *  relative to the module folder (eg, 'foo.module', 'tests/module.test');
   *  values are strings of the contents for each file.
   */
  protected function assembleFiles(ComponentCollection $component_collection, $files, DrupalExtension $existing_extension = NULL) {
    $return = [];

    foreach ($files as $file_id => $file_info) {
      $file_component = $component_collection->getComponent($file_info['source_component_id']);

      // Try the filepath from the component first, but in some cases this is
      // only hardcoded in the file info and not defined in the property
      // system, WTF!
      $filepath = $file_component->getFilename();
      if (!empty($file_info['use_file_info_filename']) || empty($filepath)) {
        if (!empty($file_info['path'])) {
          $filepath = $file_info['path'] . '/' . $file_info['filename'];
        }
        else {
          $filepath = $file_info['filename'];
        }
      }

      // Set the flags relating to existing files on the file info.
      // This must be done before tokens are replaced, as tests use the filename
      // with the token.
      // @todo: Use valuue from component once all File generator classes
      // check for existence.
      $exists = $existing_extension ? $existing_extension->hasFile($filepath) : FALSE;
      $merged = $file_info['merged'] ?? FALSE;

      $code = implode("\n", $file_info['body']);

      // Replace tokens in file contents and file path.
      // We get the tokens from the root component that was the nearest
      // requester.
      // TODO: consider changing this to be nearest root component; though
      // would require a change to File::containingComponent() among other
      // things.
      $closest_requesting_root = $component_collection->getClosestRequestingRootComponent($file_component);
      $variables = $closest_requesting_root->getReplacements();
      $code = strtr($code, $variables);
      $filepath = strtr($filepath, $variables);

      // Verify that no two components are trying to generate the same file.
      assert(!isset($return[$filepath]), "$filepath already set in list of returned files");

      $return[$filepath] = new CodeFile($filepath, $code, $exists, $merged);
    }

    return $return;
  }

}
