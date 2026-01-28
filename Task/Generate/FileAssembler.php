<?php

namespace DrupalCodeBuilder\Task\Generate;

use DrupalCodeBuilder\File\DrupalExtension;
use DrupalCodeBuilder\Generator\Collection\ComponentCollection;

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
   * @return \DrupalCodeBuilder\File\CodeFileInterface[]
   *  An array of \DrupalCodeBuilder\File\CodeFileInterface onjects, whose keys
   *  are filepaths relative to the module folder (eg, 'foo.module',
   *  'tests/module.test').
   */
  public function generateFiles($component_data, ComponentCollection $component_collection, ?DrupalExtension $existing_extension = NULL) {
    $component_list = $component_collection->getComponents();

    // Let each file component in the tree gather data from its own children.
    $this->collectFileContents($component_collection);

    // Collect and assemble files.
    // All components that wish to provide a file should have
    // registered themselves as first-level children of the root component.
    $files_assembled = $this->collectFiles($component_collection, $existing_extension);

    // Filter files according to the requested build list.
    // TODO: TEMP!
    // if (isset($component_data['requested_build'])) {
    //   $component_collection->getRootComponent()->applyBuildListFilter($files, $component_data['requested_build'], $component_data);
    // }

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
   * Collects file data from components.
   *
   * @param \DrupalCodeBuilder\Generator\Collection\ComponentCollection $component_collection
   *   The component collection.
   * @param \DrupalCodeBuilder\File\DrupalExtension $existing_extension
   *   (optional) The existing extension object, if one was found.
   *
   * @return \DrupalCodeBuilder\File\CodeFileInterface[]
   *   An array of \DrupalCodeBuilder\File\CodeFileInterface onjects, whose keys
   *   are filepaths relative to the module folder (eg, 'foo.module',
   *   'tests/module.test').
   */
  protected function collectFiles(ComponentCollection $component_collection, ?DrupalExtension $existing_extension = NULL): array {
    $code_files = [];

    // Components which provide a file should have registered themselves as
    // being contained by the root component.
    $file_components = $component_collection->getContainmentTreeChildren($component_collection->getRootComponent());
    /** $var \DrupalCodeBuilder\Generator\File $child_component */
    foreach ($file_components as $id => $child_component) {
      $file_info_item = $child_component->getFileInfo();

      // Get the filepath from the component.
      $filepath = $child_component->getFilename();
      assert(!empty($filepath));
      $file_info_item->setFilepath($filepath);

      // Set the flags relating to existing files on the file info.
      // This must be done before tokens are replaced, as tests use the filename
      // with the token.
      // @todo: Use value from component once all File generator classes
      // check for existence.
      $exists = $existing_extension ? $existing_extension->hasFile($filepath) : FALSE;
      $file_info_item->setExists($exists);

      // Assemble the code into a single string.
      $file_info_item->assembleCode();

      // Replace tokens in file contents and file path.
      // We get the tokens from the root component that was the nearest
      // requester.
      // TODO: consider changing this to be nearest root component; though
      // would require a change to File::containingComponent() among other
      // things.
      $closest_requesting_root = $component_collection->getClosestRequestingRootComponent($child_component);
      $file_info_item->replaceTokens($closest_requesting_root);

      // Get the filepath back with tokens replaced.
      $filepath = $file_info_item->getFilePath();

      // Verify that no two components are trying to generate the same file.
      assert(!isset($code_files[$filepath]), "$filepath already set in list of returned files");

      $code_files[$filepath] = $file_info_item;
    }

    return $code_files;
  }

}
