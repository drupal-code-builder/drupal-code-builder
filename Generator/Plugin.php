<?php

/**
 * @file
 * Definition of ModuleBuider\Generator\Plugin.
 */

namespace ModuleBuider\Generator;

/**
 * Generator for a plugin.
 */
class Plugin extends PHPFile {

  /**
   * The unique name of this generator.
   *
   * A generator's name is used as the key in the $components array.
   *
   * A Plugin generator should use as its name ... ???
   */
  public $name;

  /**
   * Constructor method; sets the component data.
   *
   * @param $component_name
   *   The identifier for the component.
   * @param $component_data
   *   (optional) An array of data for the component. Any missing properties
   *   (or all if this is entirely omitted) are given default values. Valid
   *   properties are:
   *    - 'class': The name of the annotation class that defines the plugin
   *      type, e.g. 'Drupal\Core\Entity\Annotation\EntityType'.
   *      TODO: since the classnames are unique regardless of namespace, figure
   *      out if there is a way of just specifying the classname.
   */
  function __construct($component_name, $component_data = array()) {
    // Set some default properties.
    $component_data += array();

    parent::__construct($component_name, $component_data);
  }

  /**
   * Build the code files.
   */
  function collectFiles(&$files) {
    // TODO: how do we figure out the namespace below the module's?
    // can it be deduced from the plugin type???
    $this->path = 'lib/Drupal/%module/';

    // TODO: token replace should happen once only and centrally!
    $this->filename = str_replace('%module', $this->base_component->component_data['module_root_name'], $this->name);

    $files[$this->name] = array(
      // TODO: revisit
      'path' => $path,
      'filename' => $this->filename,
      'body' => $this->file_contents(),
      // We join code files up on a single newline. This means that each
      // component is responsible for ending its own lines.
      'join_string' => "\n",
    );
  }

  /**
   * Return the main body of the file code.
   *
   * TODO: messier and messier arrays within arrays! file_contents() needs
   * rewriting!
   */
  function code_body() {
    return array(
      $this->code_namespace(),
      $this->class_annotation(),
    );
  }

  /**
   * Produces the namespace and 'use' lines.
   */
  function code_namespace() {
    $code = array();

    $code[] = 'namespace ' . $this->pathToNamespace($this->path) . ';';
    $code[] = '';
    // TODO!!! is there any way to figure these out??
    $code[] = 'use yadayada;';
    $code[] = '';

    return implode("\n", $code);
  }

  /**
   * Produces the plugin class annotation.
   */
  function class_annotation() {
    $class_variables = get_class_vars($this->component_data['class']);
    ddpr($class_variables);

    $docblock_code = array();
    // TODO: just the classname, not the namespace.
    $docblock_code[] = '@' . $this->component_data['class'];

    $this->buildAnnotationFromVariable($class_variables, $docblock_code);

    return $this->docBlock($docblock_code);
  }

  /**
   * Helper to recursively build a docblock annotation from a variables array.
   */
  function buildAnnotationFromVariable($variables, &$docblock_code, $nesting = 1) {
    foreach ($variables as $name => $value) {
      // TODO: aarrrrrrrgh!!!!!!! no terminal commas!!! stoopid doctrine!
      $docblock_code[] = str_repeat(' ', $nesting * 2) . $name . ' = "value",';

      if (is_array($value)) {
        $this->buildAnnotationFromVariable($value, $docblock_code, $nesting + 1);
      }
    }

    return $docblock_code;
  }

  /**
   * TODO: is there a core function for this?
   */
  function pathToNamespace($path) {
    return str_replace('/', '\\', $path);
  }

  /**
   * Helper to format text as docblock.
   *
   * @param @lines
   *  An array of lines. Lines to be normally indented should have no leading
   *  whitespace.
   *
   * @return
   *  A string of docblock with start and end PHP comment markers.
   */
  function docBlock($lines) {
    $lines = array_merge(
      array("/**"),
      array_map(array($this, 'docblockLine'), $lines),
      array(" */")
    );

    return implode("\n", $lines);
  }

  /**
   * Callback for array_map().
   *
   * Formats a single inner line of docblock.
   */
  function docblockLine($line) {
    return " * $line";
  }

}

