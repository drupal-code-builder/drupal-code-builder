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
   * Build the code files.
   *
   * Subclasses should override this to add their file data to the list.
   */
  function collectFiles(&$files) {
    /*
    // Example:
    $files[$this->name] = array(
      'path' => '', // Means base folder.
      'filename' => $this->base_component->component_data['root_name'] . '.info',
      // We pass $files in to check for files containing classes.
      'body' => $this->code_body($files),
      // We join the info lines with linebreaks, as they (currently!) do not
      // come with their own lineends.
      // TODO: fix this!
      'join_string' => "\n",
    );
    */
  }

  /**
   * Helper to get replacement strings for tokens in code body.
   *
   * @return
   *  An array of tokens to replacements, suitable for use by strtr().
   */
  function getReplacements() {
    // Get old style variable names.
    $module_data = $this->base_component->component_data;

    return array(
      '%module'       => $module_data['root_name'],
      '%description'  => str_replace("'", "\'", $module_data['short_description']),
      '%name'         => !empty($module_data['readable_name']) ? str_replace("'", "\'", $module_data['readable_name']) : $module_data['root_name'],
      '%help'         => !empty($module_data['module_help_text']) ? str_replace('"', '\"', $module_data['module_help_text']) : t('TODO: Create admin help text.'),
      '%readable'     => str_replace("'", "\'", $module_data['readable_name']),
    );
  }

}
