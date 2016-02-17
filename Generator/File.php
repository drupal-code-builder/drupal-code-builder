<?php

/**
 * @file
 * Contains generator class for outputting files.
 */

namespace ModuleBuilder\Generator;

/**
 * Abstract Generator base class for files.
 */
class File extends BaseGenerator {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
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
   * Return an empty array of subcomponent types.
   *
   * Files are (so far!) always terminal components.
   */
  protected function requiredComponents() {
    return array();
  }

  /**
   * Return this component's parent in the component tree.
   *
   * Files are usually contained by the root component.
   */
  function containingComponent() {
    return $this->base_component->name;
  }

  /**
   * Return the data for the file this component provides.
   *
   * Subclasses should override this.
   *
   * @return
   *  An array keyed by an arbitrary ID for the file, whose value is an array
   *  of file info. Values in this array are:
   *  - path: The path to the file, relative to the future module folder.
   *  - filename: The file name.
   *  - body: An array of pieces to assemble in order to form the body of the
   *    file. These can be single lines, or larger chunks: they will be joined
   *    up by assembleFiles(). The array may be keyed numerically, or the keys
   *    can be meaningful to the generator class: they are immaterial to the
   *    caller.
   *  - join_string: The string to join the body pieces with. If the body is an
   *    array of single lines, you probably want to use "\n". If you have chunks
   *    it makes more sense for each chunk to contain its own linebreaks
   *    including the terminal one.
   *  - contains_classes: A boolean indicating that this file contains one or
   *    more classes, and thus should be declared in the component's .info file.
   */
  public function getFileInfo() {
    // Subclasses should override this.

    /*
    // Example:
    $files[$this->name] = array(
      'path' => '', // Means base folder.
      'filename' => $this->base_component->component_data['root_name'] . '.info',
      'body' => $this->code_body(),
      // We join the info lines with linebreaks, as they (currently!) do not
      // come with their own lineends.
      // TODO: fix this!
      'join_string' => "\n",
    );
    */

    return array();
  }

}
