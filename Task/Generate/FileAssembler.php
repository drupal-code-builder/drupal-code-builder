<?php

namespace DrupalCodeBuilder\Task\Generate;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
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
  public function generateFiles($component_data, ComponentCollection $component_collection) {
    $component_list = $component_collection->getComponents();

    // Let each file component in the tree gather data from its own children.
    $this->collectFileContents($component_collection);

    // Build files.
    // Get info on files. All components that wish to provide a file should have
    // registered themselves as first-level children of the root component.
    $files = $this->collectFiles($component_collection);

    // Filter files according to the requested build list.
    if (isset($component_data['requested_build'])) {
      $component_collection->getRootComponent()->applyBuildListFilter($files, $component_data['requested_build'], $component_data);
    }

    // Then we assemble the files into a simple array of full filename and
    // contents.
    $files_assembled = $this->assembleFiles($component_collection, $files);

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
      $file_component->buildComponentContentsIterative($component_collection);
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
   *  An array of file info, keyed by arbitrary file ID.
   */
  protected function collectFiles(ComponentCollection $component_collection) {
    $file_info = array();

    // Components which provide a file should have registered themselves as
    // children of the root component.
    $file_components = $component_collection->getContainmentTreeChildren($component_collection->getRootComponent());
    foreach ($file_components as $id => $child_component) {
      // Don't get files for existing components.
      // TODO! This is quick and dirty! It's a lot more complicated than this,
      // for instance with components that affect other files.
      // Currently the only component that will set this is Info, to make
      // adding code to existing modules look like it works!
      if ($child_component->exists) {
        continue;
      }

      $file_info_item = $child_component->getFileInfo();
      if (is_array($file_info_item)) {
        // Prepend the component_base_path to the path.
        if (!empty($child_component->component_data['component_base_path'])) {
          if (empty($file_info_item['path'])) {
            $file_info_item['path'] = $child_component->component_data['component_base_path'];
          }
          else {
            $file_info_item['path'] = $child_component->component_data['component_base_path']
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
  protected function assembleFiles(ComponentCollection $component_collection, $files) {
    $return = array();

    foreach ($files as $file_id => $file_info) {
      if (!empty($file_info['path'])) {
        $filepath = $file_info['path'] . '/' . $file_info['filename'];
      }
      else {
        $filepath = $file_info['filename'];
      }

      $code = implode("\n", $file_info['body']);

      // Replace tokens in file contents and file path.
      // We get the tokens from the root component that was the nearest
      // requester.
      // TODO: consider changing this to be nearest root component; though
      // would require a change to File::containingComponent() among other
      // things.
      $file_component = $component_collection->getComponent($file_info['source_component_id']);
      $closest_requesting_root = $component_collection->getClosestRequestingRootComponent($file_component);
      $variables = $closest_requesting_root->getReplacements();
      $code = strtr($code, $variables);
      $filepath = strtr($filepath, $variables);

      $return[$filepath] = $code;
    }

    return $return;
  }

}
