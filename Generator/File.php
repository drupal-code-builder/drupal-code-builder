<?php

namespace DrupalCodeBuilder\Generator;

use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Abstract Generator base class for files.
 */
class File extends BaseGenerator {

  /**
   * Gets the filename, relative to the main root component.
   *
   * This should be used instead of accessing the filename property directly, as
   * it replaces the '%module' token. This is particularly important as the
   * replacement value depends on the component's closest root.
   *
   * @return string
   *   The relative filename, with the '%module' token replaced.
   */
  public function getFilename(): string {
    $filename = $this->component_data->filename->value;
    $filename = str_replace('%module', $this->component_data->root_component_name->value, $filename);

    $component_base_path = $this->component_data->component_base_path->value;
    if (!empty($component_base_path)) {
      $filename = $component_base_path . '/' . $filename;
    }

    return $filename;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPropertyDefinition(): PropertyDefinition {
    $definition = parent::getPropertyDefinition();

    $definition->addProperty(PropertyDefinition::create('string')
      // The name of the file, without the path.
      ->setName('filename')
      ->setInternal(TRUE)
      ->setRequired(TRUE)
    );

    return $definition;
  }

  /**
   * Return an empty array of subcomponent types.
   *
   * Files are (so far!) always terminal components.
   */
  public function requiredComponents(): array {
    return [];
  }

  /**
   * Return this component's parent in the component tree.
   *
   * Files are usually contained by the root component.
   */
  function containingComponent() {
    return '%root';
  }

  /**
   * Return the data for the file this component provides.
   *
   * Subclasses should override this.
   *
   * TODO: make this return CodeFile objects.
   *
   * @return
   *  An array of file info, or NULL to provide no file. The file info array
   *  should have the following properties:
   *  - path: The path to the file, relative to the future component folder,
   *    without the trailing slash. An empty string means the base folder of the
   *    component.
   *  - filename: The file name. This MUST use the '%module' token for the root
   *    component name, so that FileAssembler correctly detects existing files.
   *    Other tokens may also be used and will be replaced using
   *    the root component class's getReplacements().
   *  - body: An array of pieces to assemble in order to form the body of the
   *    file. These can be single lines, or larger chunks: they will be joined
   *    up by assembleFiles(). The array may be keyed numerically, or the keys
   *    can be meaningful to the generator class: they are immaterial to the
   *    caller.
   *  - use_file_info_filename: Dirty hack to deal with some components that
   *    want to use the filename and path from this array, and some that
   *    should use the result of getFilename() instead. WTF. TODO: remove this.
   */
  public function getFileInfo() {
    // Subclasses should override this.

    /*
    // Example:
    return array(
      'path' => '', // Means base folder.
      'filename' => '%module.info',
      'body' => $this->phpCodeBody(),
    );
    */

    return NULL;
  }

}
