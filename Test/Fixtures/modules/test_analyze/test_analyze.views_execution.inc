<?php

/**
 * @file
 * TODO: Enter file description here.
 */

/**
 * Implements hook_views_pre_view().
 */
function test_analyze_views_pre_view(ViewExecutable $view, $display_id, array &$args) {
  module_invoke_all('test_analyze_views_all', $param);
  module_invoke('other_module', 'test_analyze_views_single', $param);
  drupal_alter('test_analyze_views_alter', $param);

  // Modify contextual filters for my_special_view if user has 'my special permission'.
  $account = \Drupal::currentUser();

  if ($view->id() == 'my_special_view' && $account->hasPermission('my special permission') && $display_id == 'public_display') {
    $args[0] = 'custom value';
  }
}
