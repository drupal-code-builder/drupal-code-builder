<?php

/**
 * Generator base class for module hooks.
 *
 * Hooks are a 'fuzzy' component (I don't want to say 'abstract' here, as that
 * already means something else) in that they are not an actual file, or
 * group of files, or section of a file.
 *
 * TODO: make this work for theme hooks too?
 */
class ModuleBuilderGeneratorHooks extends ModuleBuilderGeneratorComponent {

  /**
   * Get the subcomponents for this component.
   *
   * We override getSubComponents() rather than just declare types in
   * subComponents() because (at this point at least) we need to do fancy things
   * to the generator objects as we created them.
   *
   * TODO: handle version stuff here? Or better to have it transparent in the
   * factory function?
   *
   * TODO: clean up (if possible) so we use subComponents() instead?
   */
  function getSubComponents() {
    // Just translate the variable for easier frankencoding for now!
    $module_data = $this->module_data;

    // Force hook_help() if there is help text in the incoming data.
    if (isset($module_data['module_help_text'])) {
      $module_data['hooks']['hook_help'] = TRUE;
    }

    // Get a set of hook declarations and function body templates for the hooks we want.
    // $hook_data is of the form:
    //   'hook_foo' => array( 'declaration' => DATA, 'template' => DATA )
    $hook_file_data = module_builder_get_templates($module_data);

    // There must always be a MODULE.module file, even if there are no hooks to
    // go in it.
    // (Slight niggle: it gets put at the end :/)
    // TODO: rearrange this!
    $hook_file_data += array(
      $module_data['module_root_name'] . '.module' => array(),
    );

    //print_r($module_data);
    //dsm($hook_file_data);

    // Iterate over our data array, because it's in a pretty order.
    // by each needed file of code.
    $components = array();
    foreach ($hook_file_data as $filename => $hook_data) {
      // Er... these are all just 'code' type generators. And yet they each need a key!
      // Looks like we need to change this to be:
      // name => type
      $class = module_builder_get_class('code');
      $generator = new $class($module_data);
      // Faffy stuff:
      $generator->hook_data = $hook_data;
      $generator->filename = $filename;

      $components[$filename] = $generator;
    }

    //drush_print_r($components);
    $this->components = $components;
  }

}
