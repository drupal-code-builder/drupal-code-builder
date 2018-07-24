<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Task helper for collecting admin routes.
 *
 * These are used as options for where to place an admin settings form.
 */
class AdminRoutesCollector extends CollectorBase  {

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'admin_routes';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'admin routes';

  /**
   * {@inheritdoc}
   */
  public function getJobList() {
    return NULL;
  }

  /**
   * Gets definitions of admin routes that show submenu blocks.
   *
   * @return array
   *   An array whose keys are the route names, and whose values are arrays
   *   containing:
   *   - 'route_name': The route name.
   *   - 'path': The path.
   *   - 'title': The route title.
   */
  public function collect($job_list) {
    $route_provider = \Drupal::service('router.route_provider');
    // TODO: figure out how on earth getRoutesByPattern() is meant to work!
    $routes = $route_provider->getAllRoutes();

    $admin_routes = [];
    foreach ($routes as $route_name => $route) {
      $controller = $route->getDefault('_controller');
      if ($controller == '\Drupal\system\Controller\SystemController::systemAdminMenuBlockPage') {
        $route_path = $route->getPath();

        // Skip the '/admin' path, as you don't put items there.
        if ($route_path == '/admin') {
          continue;
        }

        $admin_routes[$route_name] = [
          'route_name' => $route_name,
          'path' => $route_path,
          'title' => $route->getDefault('_title'),
        ];
      }
    }

    return $admin_routes;
  }

}
