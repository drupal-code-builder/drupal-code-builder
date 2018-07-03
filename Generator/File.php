<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Abstract Generator base class for files.
 */
class File extends BaseGenerator {

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
