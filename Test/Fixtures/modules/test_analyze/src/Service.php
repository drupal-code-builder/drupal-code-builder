<?php

namespace Drupal\test_analyze;

/**
 * TODO: class docs.
 */
class Service {

  public function service() {
    module_invoke_all('test_analyze_service_all', $param);
    module_invoke('other_module', 'test_analyze_service_single', $param);
    drupal_alter('test_analyze_service_alter', $param);
  }

}
