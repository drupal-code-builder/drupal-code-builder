<?php

/**
 * @file
 * TODO: Enter file description here.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_tokens().
 */
function test_analyze_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  module_invoke_all('test_analyze_tokens_all', $param);
  module_invoke('other_module', 'test_analyze_tokens_single', $param);
  drupal_alter('test_analyze_tokens_alter', $param);

  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = [];

  if ($type == 'node' && !empty($data['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the node.
        case 'nid':
          $replacements[$original] = $node->nid;
          break;

        case 'title':
          $replacements[$original] = $node->getTitle();
          break;

        case 'edit-url':
          $replacements[$original] = $node->toUrl('edit-form', $url_options)->toString();
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $node->getOwner() ? $node->getOwner() : User::load(0);
          $replacements[$original] = $account->label();
          $bubbleable_metadata->addCacheableDependency($account);
          break;

        case 'created':
          $replacements[$original] = \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $node->getOwner()], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $node->getCreatedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
