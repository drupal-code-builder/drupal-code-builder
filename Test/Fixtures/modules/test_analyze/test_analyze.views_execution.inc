<?php

/**
 * @file
 * TODO: Enter file description here.
 */

/**
 * Implements hook_views_pre_view().
 */
function test_analyze_views_pre_view(ViewExecutable $view, $display_id, array &$args) {

  // Modify contextual filters for my_special_view if user has 'my special permission'.
  $account = \Drupal::currentUser();

  if ($view->id() == 'my_special_view' && $account->hasPermission('my special permission') && $display_id == 'public_display') {
    $args[0] = 'custom value';
  }
}
