<?php

/**
 * @file
 * Module builder code processing code.
 *
 * Turns downloaded data and shipped templates into data about hooks.
 */

/**
 * Parse a module_builder template file.
 *
 * Template files are composed of several sections in the form of:
 *
 * == START [title of template section] ==
 * [the body of the template section]
 * == END ==
 *
 * @param string $file
 *   The template file to parse
 * @return Array
 *   Return array keyed by hook name, whose values are of the form:
 *    array('template' => TEMPLATE BODY)
 */
function module_builder_parse_template($file) {
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
