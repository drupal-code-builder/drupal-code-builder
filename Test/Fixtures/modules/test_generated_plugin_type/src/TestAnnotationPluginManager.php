<?php

namespace Drupal\test_generated_plugin_type;

use Drupal\test_generated_plugin_type\Plugin\TestAnnotationPlugin\TestAnnotationPluginInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\test_generated_plugin_type\Annotation\TestAnnotationPlugin;

/**
 * Manages discovery and instantiation of Test Annotation Plugin plugins.
 */
class TestAnnotationPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new TestAnnotationPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/TestAnnotationPlugin',
      $namespaces,
      $module_handler,
      TestAnnotationPluginInterface::class,
      TestAnnotationPlugin::class
    );

    $this->alterInfo('test_annotation_plugin_info');
    $this->setCacheBackend($cache_backend, 'test_annotation_plugin_plugins');
  }

}
