<?php

/**
 * @file
 * Contains general generator classes, abstract parents, etc.
 */

namespace ModuleBuider\Generator;

/**
 * Abstract base Generator for components.
 *
 * The generator process works over three phases:
 *  - a tree of generators is gathered
 *  - a list of file info is built up, with each generator allowed to contribute
 *  - the file info is processed and returned for the caller to output
 *
 * The generator system works by starting with a particular generator for a
 * given component (e.g., 'module'), and then adding generators this one
 * requests, recursing this process into each new generator and building a tree
 * down from the original one. This is achieved by each generator class
 * implementing subComponents() to return its child components. The process ends
 * when the added generators are themselves one that return no sub-components.
 *
 * So for example, the caller requests a 'module' component. This causes
 * the entry point to the system, Generate::generateComponent() to
 * instantiate a module generator, which is then interrogated for its
 * subcomponents. It returns, say, that it needs:
 *  - a README file
 *  - a .info file
 *  - hooks, which is an abstract component which represents the module's code
 *    which we are to generate.
 * The first two components are terminal: they do not have subcomponents of
 * their own. The hooks component however adds more generators: a code file
 * generator for each file it needs for the hooks it has been requested to
 * generate. The code files are then interrogated, and return no subcomponents:
 * the gathering process is thus complete.
 *
 * The end result is a tree which consists of each generator class having a
 * property which is an array of its subgenerators, keyed by component name.
 *
 * Once we have this, we then recurse once more into it, this time building up
 * an array of file info that we pass by reference (this it can be altered
 * as well as added to, though generator order is TBFO). Generator classes that
 * wish to add files should override collectFiles() to add them.
 *
 * Finally, we assemble the file info into filenames and code, ready for the
 * initiator of the whole process (e.g., Drush, Drupal UI) to output them in an
 * appropriate way. This is done in the starting generator's assembleFiles().
 *
 * Rough conceptual hierarchy:
  - component generators/file generators (NOT class hierarchy: processing chain!)
   - module
     - codeModule ---> hooks & callbacks
       - codeModuleFile
     - info
     - readme
     - tests
   - theme
     - codeTheme ---> theme functions
     - codeThemeFile
     - info
     - readme
   - profile
     - codeProfile --> hooks
     - info
     - readme
   - plugin ??
     - goes beneath module somehow??????

   the initial request causes:
     - figuring out which generator to start up
     - component generator figures out:
       - subcomponents (hooks, info file, plugins, tests)
       - each subcomponent recurses and eventually gets to a file generator.
 *
 * @see Generate::generateComponent()
 */
abstract class Base {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
   */
  public $name;

  /**
   * Reference to the Generate task handler.
   *
   * This should be used to access the environment, and to call getGenerator().
   */
  public $task;

  /**
   * Reference to the base component of this component.
   *
   * This should be used to access the component data.
   */
  public $base_component;

  /**
   * An array of this component's subcomponents.
   *
   * This is keyed by the name of the component name. Values are the
   * instantiated component generators.
   */
  public $components = array();

  /**
   * The data for the component.
   *
   * This is only present on the base component (e.g., 'Module'), so that the
   * data initially given by the user may be globally modified or added to by
   * components.
   */
  public $component_data = array();

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component. This is often the same as the type
   *   (e.g., 'module', 'hooks') but in the case of types used multiple times
   *   this will be a unique identifier.
   */
  function __construct($component_name) {
    $this->name = $component_name;
  }

  /**
   * Get the subcomponents for this generator.
   *
   * This calls itself recursively on the subcomponents, thus building a nested
   * tree of generators.
   *
   * Generator classes should implement subComponents() to return the list
   * of component types they require, possibly depending on incoming data.
   *
   * Obviously, it's important that eventually this process terminate with
   * generators that return an empty array for subComponents().
   *
   * @return
   *  None. This should set an array of subcomponent generators on the property
   *  $this->components.
   */
  public function getSubComponents() {
    $this->components = array();

    // Get the required subcomponents.
    $subcomponent_info = $this->subComponents();

    // Instantiate each one, and recurse into it.
    foreach ($subcomponent_info as $component_name => $component_type) {
      $generator = $this->task->getGenerator($component_type, $component_name);
      $this->components[$component_name] = $generator;

      // Recurse into the subcomponent.
      foreach ($this->components as $generator) {
        $generator->getSubComponents();
      }
    }
  }

  /**
   * Return an array of subcomponent types.
   *
   * For example, a module component requires hooks, an info file, and a readme
   * file. Hooks in turn require a varying number of files, determined by the
   * incoming module data.
   *
   * @return
   *  An array of subcomponents which the current generator requires.
   *  Each item's key is a name for the component. This must be unique, so if
   *  there are likely to be multiple instances of a component type, this will
   *  need to be generated based on input data. Each value is the type for the
   *  component, suitable for passing to Generate::getGenerator() to get the
   *  generator class.
   */
  protected function subComponents() {
    return array();
  }

  /**
   * Collect data for files, recursing into each subcomponent.
   *
   * It's safe for subclasses to use this too, as it acts on a generator's own
   * array of subcomponents.
   *
   * Generators that have some code to output should override this to output it!
   *
   * It's up to the caller of this on the root generator to figure out how
   * to output the files at the end of the process: eg, drush prints them to
   * the terminal or writes them; the Drupal UI shows them in form textareas.
   *
   * @param
   *  An array of file info, passed by reference. Components should add files
   *  to this, but may also alter what has already been generated.
   *  The keys are machine names, probably (!) arbitrary. Values are:
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
  function collectFiles(&$files) {
    foreach ($this->components as $generator) {
      $generator->collectFiles($files);
    }
  }

  /**
   * Assemble file info into filename and code.
   *
   * @param $files
   *  An array of file info, as compiled by collectFiles().
   *
   * @return
   *  An array of files ready for output. Keys are the filepath and filename
   *  relative to the module folder (eg, 'foo.module', 'tests/module.test');
   *  values are strings of the contents for each file.
   */
  function assembleFiles($files) {
    $return = array();

    foreach ($files as $file_id => $file_info) {
      if (!empty($file_info['path'])) {
        $filepath = $file_info['path'] . '/' . $file_info['filename'];
      }
      else {
        $filepath = $file_info['filename'];
      }

      $code = implode($file_info['join_string'], $file_info['body']);

      $return[$filepath] = $code;
    }

    return $return;
  }

}
