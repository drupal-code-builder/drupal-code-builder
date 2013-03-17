<?php

/**
 * @file
 * Contains general generator classes, abstract parents, etc.
 */

/**
 * Abstract base Generator for components.
 */
abstract class ModuleBuilderGeneratorComponent {

  function __construct($module_data) {
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
   */
  function getSubComponents() {
    $this->components = array();

    // Get the required subcomponents.
    $subcomponent_info = $this->subComponents();

    // Instantiate each one, and recurse into it.
    foreach ($subcomponent_info as $component_type) {
      $class = module_builder_get_class($component_type);
      $generator = new $class($this->module_data);
      $this->components[$component_type] = $generator;

      // Recurse into the subcomponent.
      foreach ($this->components as $generator) {
        $generator->getSubComponents();
      }
    }

    drush_print_r($this->components);
  }

  /**
   * Return an array of subcomponent types.
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
 * Abstract Generator base class for files.
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
   * The main code building function.
   */
  function build() {
    $output = '';
    $output .= $this->file_header();
    $output .= $this->code_header();
    $output .= $this->code_body();
    $output .= $this->code_footer();

    return $output;
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

