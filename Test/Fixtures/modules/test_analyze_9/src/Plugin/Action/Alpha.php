<?php

namespace Drupal\test_analyze_9\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TODO: class docs.
 *
 * @Action(
 *   id = "test_analyze_9_alpha",
 *   label = @Translation("Alpha"),
 *   confirm_form_route_name = "TODO: replace this with a value",
 *   type = "TODO: replace this with a value",
 *   category = @Translation("TODO: replace this with a value"),
 * )
 */
class Alpha extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a Alpha instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    $this->moduleHandler->invokeAll('test_analyze_9_di_all_cats', [$purr, $miaow]);
    $this->moduleHandler->invoke('kitty_module', 'test_analyze_9_di_one_cat', [$purr, $miaow]);
    $this->moduleHandler->alter('test_analyze_9_di_change_cat', $purr, $miaow);
    $this->moduleHandler->alter(['test_analyze_9_di_change_cat_1', 'test_analyze_9_di_change_cat_2'], $purr, $miaow);
    // Executes the plugin for an array of objects.
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    // Checks object access.
  }

}
