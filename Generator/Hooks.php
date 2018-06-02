<?php

namespace DrupalCodeBuilder\Generator;

/**
 * Generator base class for module hooks.
 *
 * Hooks are a 'fuzzy' component (I don't want to say 'abstract' here, as that
 * already means something else) in that they are not an actual file, or
 * group of files, or section of a file.
 *
 * A module component requests a hook component, and this component in turn
 * requests one or more HookImplementation components. Those will then in turn
 * request ModuleCodeFile components for the file they should reside in.
 *
 * TODO: make this work for theme hooks too?
 *
 * @see DrupalCodeBuilder\Generator\ModuleCodeFile
 */
class Hooks extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function componentDataDefinition() {
    return parent::componentDataDefinition() + [
      // An array of requested hooks, where the keys are the long hook names and
      // the values are TRUE.
      'hooks' => [
        'label' => 'Hook implementations',
        'required' => FALSE,
        'format' => 'array',
      ],
      // The body code for any hook implementations. An array whose keys are
      // the full hook names, and whose values are the value to pass on to
      // that HookImplementation component.
      'hook_bodies' => [
        'internal' => TRUE,
        'format' => 'array',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMergeTag() {
    return 'hooks';
  }

  /**
   * Declares the subcomponents for this component.
   *
   * These are not necessarily child classes, just components this needs.
   *
   * Further filtering of components based on the build request takes place
   * here.
   *
   * @return
   *  An array of subcomponent names and types.
   */
  public function requiredComponents() {
    // We add components of type HookImplementation: each of these is a single
    // function. From this point on, these subcomponents are the authority on
    // which hooks we generate. Each HookImplementation component will add the
    // file it requires to the component list.
    $components = array();

    // Just translate the variable for easier frankencoding for now!
    $requested_hook_list = $this->component_data['hooks'];

    // Get a set of hook declarations and function body templates for the hooks
    // we want. This is of the form:
    //   'hook_foo' => array( 'declaration' => DATA, 'template' => DATA )
    $hook_file_data = $this->getTemplates($requested_hook_list);

    // Work over this and add our HookImplentation.
    foreach ($hook_file_data as $filename => $file_hook_list) {
      // If we are set to filter, and the abbreviated filename isn't in the
      // build list, skip it.
      // TODO: this is kinda messy, and assumes that the abbreviated name is
      // always obtained by removing '%module.' from the filename!
      $build_list_key = str_replace('%module.', '', $filename);

      // Add a HookImplementation component for each hook.
      foreach ($file_hook_list as $hook_name => $hook) {
        // Figure out if there is a dedicated generator class for this hook.
        // TODO: is it really worth this faff? How many will we have, apart from
        // hook_menu? Is coding this a) slow and b) YAGNI?
        $hook_name_pieces = explode('_', strtolower($hook['name']));
        $hook_name_pieces = array_map(function($word) { return ucwords($word); }, $hook_name_pieces);
        // Make the class name, eg HookMenu.
        $hook_class_name = implode('', $hook_name_pieces);
        // Make the fully qualified class name.
        $hook_class = $this->classHandlerHelper->getGeneratorClass($hook_class_name);
        if (!class_exists($hook_class)) {
          $hook_class_name = 'HookImplementation';
        }

        $components[$hook['name']] = array(
          'component_type' => $hook_class_name,
          'code_file' => $hook['destination'],
          'hook_name' => $hook['name'],
          'declaration' => $hook['definition'],
          'has_wrapping_newlines' => TRUE,
        );

        // The body for the hook implementation can come either from this
        // component's requester, as the 'hook_bodies' property, or from
        // template  code, or finally, the hook documentation's example code.
        // In the last two cases, the code is in $hook['template'].
        // (Note that further down the line, some specific hook implementation
        // generators assemble body from their own child components.)
        if (isset($this->component_data['hook_bodies'][$hook_name])) {
          $components[$hook_name]['body'] = $this->component_data['hook_bodies'][$hook_name];
        }
        else {
          // Strip out INFO: comments for advanced users.
          // This has to be done before we split this into lines.
          // TODO: No need to do this if this is hook api.php file sample code!
          if (!\DrupalCodeBuilder\Factory::getEnvironment()->getSetting('detail_level', 0)) {
            // Used to strip INFO messages out of generated file for advanced users.
            $pattern = '#\s+/\* INFO:(.*?)\*/#ms';
            $hook['template'] = preg_replace($pattern, '', $hook['template']);
          }

          // This needs to be split into an array of lines for things such as
          // PHPFile::extractFullyQualifiedClasses() to work.
          $hook['template'] = explode("\n", $hook['template']);

          // Trim lines from start and end of body, as hook definitions
          // have newlines at start and end.
          $hook['template'] = array_slice($hook['template'], 1, -1);

          // Set it as the method body.
          $components[$hook['name']]['body'] = $hook['template'];

          // The code is a single string, already indented. Tell
          // buildComponentContents() not to indent it again.
          $components[$hook['name']]['body_indented'] = TRUE;
        }
      }
    }

    return $components;
  }

  /**
   * Helper function for our requiredComponents().
   *
   * (Move this back out if it needs to be used by other components in future?)
   *
   * Returns an array of hook data and templates for the requested hooks.
   * This is handled live rather than in the Collect task to allow the user to
   * alter their custom hook templates.
   *
   * @return
   *   An array of hook data grouped by destination file, whose keys are the
   *   filenames of destination files with the token '%module', and whose values
   *   further arrays. The nested arrays' keys are long hook names and the
   *   values are arrays of hook data as follows:
   *    - 'type': One of 'hook' or 'callback'.
   *    - 'name': The long hook name (i.e. 'hook_boot' rather than 'boot').
   *    - 'definition': The function declaration, with the 'hook' part not
   *        yet replaced.
   *    - 'destination': The destination, with tokens still in place.
   *    - 'group': The name of the api.php file this was defined in, without the
   *      'api.php' suffix.
   *    - 'body': The sample body code of the hook from the definition.
   *    - template_files: A list of template file types, in order of preference,
   *        keyed by filename and with the value TRUE if the hook code exists
   *        in that template file.
   *    - template (optional): The template code, if any was found.
   *   Example:
   *  'destination file' => array(
   *    'hook_foo' => array(
   *      'declaration' => DATA,
   *      'template' => DATA,
   *      // ...etc
   *    )
   */
  function getTemplates($requested_hook_list) {
    // Sanity checks already done at this point; no need to catch exception.
    $mb_task_handler_report = \DrupalCodeBuilder\Factory::getTask('ReportHookData');

    //print_r($requested_hook_list);
    // TODO: might not need this; easier to test truth than isset.

    // Get array of the hook function declarations from the downloaded hook data.
    // This is a complete list of all hooks that exist.
    // In the form: 'hook_foo' => array('declaration', 'destination')
    // This array is the order they are in the files from d.org: alphabetical apparently.
    // We don't care for this order!
    $hook_function_declarations = $mb_task_handler_report->getHookDeclarations();

    //drush_print_r($hook_function_declarations);
    // TODO: this should contain the name of the api.php file that provided it!

    // Add hook dependencies.
    foreach (array_keys($requested_hook_list) as $hook_name) {
      if (!empty($hook_function_declarations[$hook_name]['dependencies'])) {
        //drush_print_r($hook_function_declarations[$hook_name]['dependencies']);
        foreach ($hook_function_declarations[$hook_name]['dependencies'] as $hook_dependency) {
          $requested_hook_list[$hook_dependency] = TRUE;
        }
      }
    }

    // Trim this down to just the ones we care about.
    // By this point, both sets of array keys are standardized to lower case.
    $hook_function_declarations = array_intersect_key($hook_function_declarations, $requested_hook_list);
    //print_r("hook_function_declarations: \n");
    //drush_print_r($hook_function_declarations);

    // Filter out the requested hooks that don't have definitions.
    // We do this now as it's possible for a hook to have no definition because
    // the user doesn't have it, but have a template because we provide it,
    // eg views_api.
    // We do this by hand this time rather than array_intersect_key() so we can
    // make a list of hooks we're rejecting for (TODO!) eventual warning output.
    $rejected_hooks = array();
    foreach (array_keys($requested_hook_list) as $hook_name) {
      if (!isset($hook_function_declarations[$hook_name])) {
        unset($requested_hook_list[$hook_name]);
        $rejected_hooks[] = $hook_name;
      }
    }
    // TODO: at this point we should check if we have any actual hooks to
    // process, and warn if not.
    // We should probably also do something with rejected hooks list.

    // Step 1:
    // Build up a list of the basic template files we want to parse.
    //  - in each $hook_function_declarations item, place an ordered list of
    //    all potential template files. We will set these to TRUE in step 2
    //    if they hold a template for the hook.
    //  - meanwhile, build up list of template files we will want to check for
    //    existence and parse.
    // Template filenames are of the following form, in the order they should be
    // checked, ie from most specific to most general:
    //  - GROUP.hooks.template, eg node.hooks.template
    //    (Though groups are still TODO: this is scaffold only for now!)
    //  - FILENAME.template, where the modulename is replaced with 'hooks', hence
    //    hooks.module.template, hooks.install.template, hooks.views.inc.template.
    //  - hooks.template - the base template, final fallback
    // These are found in drupal_code_builder/templates/VERSION, and
    // in addition, a file may be overridden by being present in the user's
    // data directory. Though just what the 'data directory' means exactly is
    // not yet properly defined...
    $template_file_names = array();
    foreach ($hook_function_declarations as $hook_name => $hook) {
      // TODO: $groupname_template = 'GROUP.hooks.template';
      $filename_template  = str_replace('%module', 'hooks', $hook['destination']) . '.template';
      // Place in each $hook_function_declarations item an ordered list of
      // potential files from best fit to fallback.
      // These are keyed by filename and all with value FALSE initially.
      $hook_function_declarations[$hook_name]['template_files'] = array_fill_keys(array(
        // TODO: $groupname_template,
        $filename_template,
        'hooks.template',
      ), FALSE);

      // Meanwhile, build up list of files we will want to check for existence and parse.
      // TODO: $template_file_names[$groupname_template] = TRUE;
      $template_file_names[$filename_template] = TRUE;
      $template_file_names['hooks.template'] = TRUE;

    }

    // print_r("template file names: \n");
    // print_r($template_file_names);

    // print_r("hook_function_declarations are now:: \n");
    // print_r($hook_function_declarations);

    // Step 2:
    // Now we parse the templates we need.
    // We look in two places: drupal_code_builder's own '/templates' folder, and the optional
    // location given for user data (the latter is in fact TODO...)
    // User templates override on a per-file basis, so a custom
    // node.hooks.template will only override that same file in the module data;
    // if the hook is not requested as part of a group then that file will not be considered.
    // (Though groups are broken for now...)
    $version = \DrupalCodeBuilder\Factory::getEnvironment()->getCoreMajorVersion();
    $template_base_path_module = \DrupalCodeBuilder\Factory::getEnvironment()->getPath('templates') . '/' . $version;
    //print "base path: $template_base_path_module";
    // $template_base_paths['module']
    // $template_base_paths['user']

    $template_data = array();
    foreach (array_keys($template_file_names) as $filename) {
      $filepath = "$template_base_path_module/$filename";
      if (file_exists($filepath)) {
        $template_file = file_get_contents($filepath);
        $template_data = $this->parseTemplate($template_file);

        // Trim the template data to the hooks we care about.
        $template_data = array_intersect_key($template_data, $requested_hook_list);

        // Flag the template file in the hook list; ie, set to TRUE the template
        // file in the list which we first created as entirely FALSE.
        foreach (array_keys($template_data) as $hook_name) {
          $hook_function_declarations[$hook_name]['template_files'][$filename] = TRUE;
        }
      }
    }

    //print_r("hook_function_declarations now have template files \n");
    //print_r($hook_function_declarations);


    // $template_data is now an array of the form:
    //  [hook name] => array('template' => DATA)
    // in a pretty order which we want to hold on to.

    //print_r('template data is:');
    //print_r($template_data);

    // Step 3a:
    // Build a new array of hook data, so that we take the order from the
    // template data, but using the same data structure as the
    // $hook_function_declarations array.
    // The big question here: once we have other template files, such as those
    // for groups, or user ones, how do we combine the order from all of them?
    // Or do we just have an overall order from the template files' order, and
    // then within that respect each of theirs, so in effect it's like
    // concatenating all the template files we use?
    $hook_data_return = array();
    foreach (array_keys($template_data) as $hook_name) {
      $destination = $hook_function_declarations[$hook_name]['destination'];
      // Copy over the data we already had.
      $hook_data_return[$destination][$hook_name] = $hook_function_declarations[$hook_name];

      // Copy over the template.
      // TODO: more here.
      $hook_data_return[$destination][$hook_name]['template']    = $template_data[$hook_name]['template'];
    }

    // Step 3b:
    // Not all hooks have template data, so fill these in too.
    foreach ($hook_function_declarations as $hook_name => $hook) {
      $destination = $hook_function_declarations[$hook_name]['destination'];
      if (!isset($hook_data_return[$destination][$hook_name])) {
        $hook_data_return[$destination][$hook_name] = $hook_function_declarations[$hook_name];
      }
      // We have no template data, so fill in the sample from the api.php file,
      // as this is often informative.
      if (empty($hook_data_return[$destination][$hook_name]['template'])) {
        $hook_data_return[$destination][$hook_name]['template'] = $hook_function_declarations[$hook_name]['body'];
      }
    }


    //print_r('step 3:');
    //print_r($hook_data_return);
    return $hook_data_return;
  }

  /**
   * Parse a drupal_code_builder template file.
   *
   * Template files are composed of several sections in the form of:
   *
   * == START [title of template section] ==
   * [the body of the template section]
   * == END ==
   *
   * @param string $file
   *   The template file to parse
   *
   * @return Array
   *   Return array keyed by hook name, whose values are of the form:
   *    array('template' => TEMPLATE BODY)
   */
  function parseTemplate($file) {
    $data = array();

    // Captures a template name and body from a template file.
    $pattern = '#== START (.*?) ==(.*?)== END ==#ms';
     preg_match_all($pattern, $file, $matches);
    $count = count($matches[0]);
    for ($i = 0; $i < $count; $i++) {
      $data[$matches[1][$i]] = array(
        #'title' => $matches[1][$i],
        'template' => $matches[2][$i]
      );
      /*
      $hook_custom_declarations[] = array(
        'title' => $matches[1][$i],
        'data' => $matches[2][$i]
      );
      */
    }
    return $data;
  }

}
