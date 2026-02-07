<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the D8/9 entity type generator class.
 *
 * @group yaml
 * @group annotation
 * @group entity
 */
class ComponentContentEntityType9Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 9;

  /**
   * Tests creating a content entity with a revision UI.
   *
   * @group entity_ui
   * @group form
   */
  public function testContentEntityTypeWithRevisionEntityUI() {
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'content_entity_types' => [
        0 => [
          'entity_type_id' => 'kitty_cat',
          'functionality' => [
            'revisionable',
          ],
          'entity_ui' => 'admin',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $entity_class_file = $files['src/Entity/KittyCat.php'];
    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $entity_class_file);
    $annotation_tester = $php_tester->getAnnotationTesterForClass();

    $annotation_tester->assertNotHasProperty(['handlers', 'route_provider', 'revision']);
  }

}
