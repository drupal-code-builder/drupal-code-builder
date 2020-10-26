<?php

namespace DrupalCodeBuilder\Test\Fixtures\Drupal;

use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Module List which allows the discovery to be set.
 */
class TestModuleExtensionList extends ModuleExtensionList {

  /**
   * @var \Drupal\Core\Extension\ExtensionDiscovery|null
   */
  protected $extensionDiscovery;

  /**
   * @param \Drupal\Core\Extension\ExtensionDiscovery $extension_discovery
   */
  public function setExtensionDiscovery(\Drupal\Core\Extension\ExtensionDiscovery $extension_discovery) {
    $this->extensionDiscovery = $extension_discovery;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtensionDiscovery() {
    return $this->extensionDiscovery ?: parent::getExtensionDiscovery();
  }

}
