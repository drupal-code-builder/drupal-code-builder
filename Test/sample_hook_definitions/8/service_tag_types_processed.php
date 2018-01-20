<?php $data =
array (
  'breadcrumb_builder' => 
  array (
    'label' => 'Breadcrumb Builder',
    'interface' => 'Drupal\\Core\\Breadcrumb\\BreadcrumbBuilderInterface',
    'methods' => 
    array (
      'applies' => 
      array (
        'name' => 'applies',
        'declaration' => 'public function applies(\\Drupal\\Core\\Routing\\RouteMatchInterface $route_match);',
        'description' => 'Whether this breadcrumb builder should be used to build the breadcrumb.',
      ),
      'build' => 
      array (
        'name' => 'build',
        'declaration' => 'public function build(\\Drupal\\Core\\Routing\\RouteMatchInterface $route_match);',
        'description' => 'Builds the breadcrumb.',
      ),
    ),
  ),
  'event_subscriber' => 
  array (
    'label' => 'Event subscriber',
    'interface' => 'Symfony\\Component\\EventDispatcher\\EventSubscriberInterface',
    'methods' => 
    array (
      'getSubscribedEvents' => 
      array (
        'name' => 'getSubscribedEvents',
        'declaration' => 'public static function getSubscribedEvents();',
        'description' => 'Returns an array of event names this subscriber wants to listen to.',
      ),
    ),
  ),
);