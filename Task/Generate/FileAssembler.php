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
    $tree = $component_collection->getContainmentTree();

    // The root generator is the first component in the list.
    // TODO: change this to use parameters.
    $this->root_generator = reset($component_list);

    // Let each file component in the tree gather data from its own children.
    $this->collectFileContents($component_collection);

    //drush_print_r($generator->components);

    // Build files.
    // Get info on files. All components that wish to provide a file should have
    // registered themselves as first-level children of the root component.
    $files = $this->collectFiles($component_list, $tree);

    // Filter files according to the requested build list.
    if (isset($component_data['requested_build'])) {
      $this->root_generator->applyBuildListFilter($files, $component_data['requested_build'], $component_data);
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
    $component_list = $component_collection->getComponents();
    $tree = $component_collection->getContainmentTree();

    // Iterate over all file-providing components, i.e. one level below the root
    // of the tree.
    $file_component_ids = $component_collection->getContainmentTreeChildrenIds($component_collection->getRootComponentId());
    foreach ($file_component_ids as $file_component_name) {
      // Skip files with no children in the tree.
      if (empty($tree[$file_component_name])) {
        continue;
      }

      // Let the file component run over its children iteratively.
      // (Not literally ;)
      $component_collection->getComponent($file_component_name)
        ->buildComponentContentsIterative($component_list, $tree);
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
   *  - 'join_string': The string with which to join the items in the body
   *    array. (TODO: remove this!)
   *
   * @param $component_list
   *  The component list.
   * @param $tree
   *  An array of parentage data about components, as given by
   *  assembleComponentTree().
   *
   * @return
   *  An array of file info, keyed by arbitrary file ID.
   */
  protected function collectFiles($component_list, $tree) {
    $file_info = array();

    // Components which provide a file should have registered themselves as
    // children of the root component.
    $root_component_name = $this->root_generator->getUniqueID();
    foreach ($tree[$root_component_name] as $child_component_name) {
      $child_component = $component_list[$child_component_name];

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
        $file_info_item['source_component_id'] = $child_component_name;

        $file_info[$child_component_name] = $file_info_item;
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

      $code = implode($file_info['join_string'], $file_info['body']);

      // Replace tokens in file contents and file path.
      // We get the tokens from the root component that was the nearest
      // requester.
      // TODO: consider changing this to be nearest root component; though
      // would require a change to File::containingComponent() among other
      // things.
      $closest_requesting_root = $component_collection->getClosestRequestingRootComponent($file_info['source_component_id']);
      $variables = $closest_requesting_root->getReplacements();
      $code = strtr($code, $variables);
      $filepath = strtr($filepath, $variables);

      $return[$filepath] = $code;
    }

    return $return;
  }

}
