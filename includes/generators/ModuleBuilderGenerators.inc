<?php

/**
 * @file
 * Contains general generator classes, abstract parents, etc.
 */

/**
 * Abstract base Generator for components.
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
 */
abstract class ModuleBuilderGeneratorComponent {

  /**
   * Constructor method; sets the module data.
   *
   * @param $module_data
   *   An associative array of data for the module, [passed by reference so data
   *   on generated files can be added!?!?!?!]
   *   The keys can *mostly* be taken straight from form values. They are as follows:
   *     - 'module_root_name'
   *     - 'module_readable_name'
   *     - 'module_short_description'
   *     - 'module_help_text'
   *     - 'hooks': An associative array whose keys are full hook names
   *       (eg 'hook_menu'), where requested hooks have a value of TRUE.
   *       Unwanted hooks may also be included as keys provided their value is FALSE.
   *     - 'module_dependencies': a string of dependencies, eg 'forum views'.
   *     - 'module_package': the module package.
   *     - 'module_folder': (optional) The destination folder to write the module
   *        files to.
   *     - 'module_files': ??? OBSOLETE!? added by this function. A flat array of filenames that have been generated.
   *    - 'requested_build': An array whose keys are requested things to build.
   // - all -- everything we can do
   // - code -- code files, not info (module + install _ ..?)
   // - info -- only info fole
   // - module -- only module file
   // - install -- only install file
   // - ??? whatever hooks need
   */
  function __construct($module_data) {
    // TODO: find a way to only have this once instead of EVERY DAMN OBJECT!
    $this->module_data = $module_data;
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
  function getSubComponents() {
    $this->components = array();

    // Get the required subcomponents.
    $subcomponent_info = $this->subComponents();

    // Instantiate each one, and recurse into it.
    foreach ($subcomponent_info as $component_name => $component_type) {
      $class = module_builder_get_class($component_type);
      $generator = new $class($this->module_data);
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
   *  Each item's key is its unique name; each value is its type (from which
   *  the generator class is composed).
   */
  function subComponents() {
    return array();
  }

  /**
   * Collect files, recursing into each subcomponent.
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
   *  An array of files. Components should add files to this.
   *  The keys are machine names, probably (!) arbitrary. Values are:
   *  - path: The path to the file, relative to the future module folder.
   *  - name: The file name.
   *  - body: An array of pieces to assemble in order to form the body of the
   *    file. These can be single lines, or larger chunks: they will be joined
   *    up with newline characters.
   */
  function collectFiles(&$files) {
    foreach ($this->components as $generator) {
      $generator->collectFiles($files);
    }
  }

}

/**
 * Abstract Generator base class for files. TODO: rename? merge into Code?
 */
abstract class ModuleBuilderGeneratorFile extends ModuleBuilderGeneratorComponent {

  function __construct($module_data) {
    $this->module_data = $module_data;
  }

  /**
   * Return an empty array of subcomponent types.
   *
   * Files are always terminal components.
   */
  function subComponents() {
    return array();
  }

  /**
   * Return the PHP file header line.
   */
   function file_header()  {
     return "<?php\n";
   }

  /**
   * Return the file doxygen header and any custom header code.
   */
  function code_header() {
    $filename = $this->filename;
    $default = <<<EOT
/**
 * @file $filename
 * TODO: Enter file description here.
 */

EOT;
    $code = variable_get('module_builder_header', $default);
    return $code;
  }

  /**
   * Return the main body of the file code.
   */
  abstract function code_body();

  /**
   * Return a file footer.
   */
  function code_footer() {}
}

