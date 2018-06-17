<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Abstract Generator base class for files.
 */
class File extends BaseGenerator {

  /**
   * The request name of this generator.
   *
   * A File generator should use as its name the generic form of its eventual
   * file name, that is, the filename with the token '%module' in place of the
   * module name. For example: %module.module, $module.views.inc, etc.
   */
  public $name;

  /**
   * The actual name of the file to write.
   */
  protected $filename;

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      // The name of the file, without the path.
      'filename' => [
        'internal' => TRUE,
      ],
    ];
  }

  /**
   * Constructor.
   *
   * @param $component_name
   *  The name should be the eventual filename, which may include tokens such as
   *  %module, which are handled by assembleFiles().
   * @param $component_data
   *   An array of data for the component.
   */
  function __construct($component_name, $component_data, $root_generator) {
    if (empty($component_data['filename'])) {
      // For backwards-compatibility, allow the component name to be the
      // filename if that was not specified.
      $component_data['filename'] = $component_name;
    }

    // Set the class property for code that still expects it.
    $this->filename = $component_data['filename'];

    parent::__construct($component_name, $component_data, $root_generator);
  }

  /**
   * Return an empty array of subcomponent types.
   *
   * Files are (so far!) always terminal components.
   */
  public function requiredComponents() {
    return array();
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
   * @return
   *  An array of file info, or NULL to provide no file. The file info array
   *  should have the following properties:
   *  - path: The path to the file, relative to the future component folder,
   *    without the trailing slash. An empty string means the base folder of the
   *    component.
   *  - filename: The file name. This may contain tokens, to be replaced using
   *    the root component class's getReplacements().
   *  - body: An array of pieces to assemble in order to form the body of the
   *    file. These can be single lines, or larger chunks: they will be joined
   *    up by assembleFiles(). The array may be keyed numerically, or the keys
   *    can be meaningful to the generator class: they are immaterial to the
   *    caller.
   */
  public function getFileInfo() {
    // Subclasses should override this.

    /*
    // Example:
    return array(
      'path' => '', // Means base folder.
      'filename' => '%module.info',
      'body' => $this->code_body(),
    );
    */

    return NULL;
  }

}
