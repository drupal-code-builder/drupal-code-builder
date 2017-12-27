<?php

namespace DrupalCodeBuilder\Generator;

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
  public function requiredComponents() {
    return array();
  }

  /**
   * Return this component's parent in the component tree.
   *
   * Files are usually contained by the root component.
   */
  function containingComponent() {
    return $this->root_component->getUniqueID();
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
   *  - join_string: The string to join the body pieces with. If the body is an
   *    array of single lines, you probably want to use "\n". If you have chunks
   *    it makes more sense for each chunk to contain its own linebreaks
   *    including the terminal one.
   */
  public function getFileInfo() {
    // Subclasses should override this.

    /*
    // Example:
    return array(
      'path' => '', // Means base folder.
      'filename' => $this->root_component->component_data['root_name'] . '.info',
      'body' => $this->code_body(),
      // We join the info lines with linebreaks, as they (currently!) do not
      // come with their own lineends.
      // TODO: fix this!
      'join_string' => "\n",
    );
    */

    return NULL;
  }

}
