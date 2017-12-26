<?php

namespace DrupalCodeBuilder\Test\Integration\Installation;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests a module that has a content entity type.
 *
 * These need to be run from Drupal's PHPUnit, rather than ours:
 * @code
 *  [drupal]/core $ ../vendor/bin/phpunit ../vendor/drupal-code-builder/drupal-code-builder/Test/Integration/Installation/ContentEntityTypeTest.php
 * @endcode
 */
class ContentEntityTypeTest extends InstallationTestBase {

  /**
   * Tests a content entity type without bundles.
   */
  public function testSimpleContentEntityType() {
    // Create a module.
    $module_name = 'dcb_test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'short_description' => 'Test Module description',
      'hooks' => array(
      ),
      'readme' => FALSE,
      'content_entity_types' => [
        0 => [
          // Use an ID string with an underscore to test class names and labels
          // correctly have it removed.
          'entity_type_id' => 'kitty_cat',
          'interface_parents' => [
            'EntityOwnerInterface',
          ],
          'base_fields' => [
            0 => [
              'name' => 'breed',
              'type' => 'string',
            ],
            1 => [
              'name' => 'colour',
              'type' => 'string',
            ],
          ],
        ],
      ],
    );
    $files = $this->generateModuleFiles($module_data);

    $this->writeModuleFiles($module_name, $files);

    $this->installModule($module_name);

    // Get the entity type definition to check the entity class properly defines
    // it.
    \Drupal::service('entity_type.manager')->clearCachedDefinitions();
    $definition = \Drupal::service('entity_type.manager')->getDefinition('kitty_cat');
  }

}
