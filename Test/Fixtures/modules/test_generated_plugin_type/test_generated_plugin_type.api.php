<?php

/**
 * @file
 * Hooks provided by the Test Generated Plugin Type module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on Test Annotation Plugin definitions.
 *
 * @param array $info
 *   Array of information on Test Annotation Plugin plugins.
 */
function hook_test_annotation_plugin_info_alter(array &$info) {
  // Change the class of the 'foo' plugin.
  $info['foo']['class'] = SomeOtherClass::class;
}

/**
 * @} End of "addtogroup hooks".
 */
