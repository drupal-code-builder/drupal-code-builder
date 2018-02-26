<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the entity type generator class.
 *
 * @group yaml
 * @group annotation
 */
class ComponentContentEntityType8Test extends TestBaseComponentGeneration {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test creating a content entity type.
   */
  public function testEntityTypeWithoutBundleEntity() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'content_entity_types' => [
        0 => [
          // Use an ID string with an underscore to test class names and labels
          // correctly have it removed.
          'entity_type_id' => 'kitty_cat',
          'interface_parents' => [
            'EntityOwnerInterface',
          ],
          'handler_list_builder' => 'core',
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
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(3, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Entity/KittyCat.php", $files, "The files list has an entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatInterface.php", $files, "The files list has an entity interface file.");

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $php_tester = new PHPTester($entity_class_file);

    // TODO - convert rest of this to use PHP tester.
    $this->assertWellFormedPHP($entity_class_file);
    $this->assertDrupalCodingStandards($entity_class_file);
    $this->assertNoTrailingWhitespace($entity_class_file);
    $this->assertClassFileFormatting($entity_class_file);

    $this->parseCode($entity_class_file);
    $this->assertHasClass('Drupal\test_module\Entity\KittyCat');
    $this->assertClassHasParent('Drupal\Core\Entity\ContentEntityBase');
    $this->assertHasMethods(['baseFieldDefinitions']);

    // Test the entity annotation.
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('ContentEntityType');
    $annotation_tester->assertPropertyHasValue('id', 'kitty_cat');
    $annotation_tester->assertPropertyHasValue('label', 'Kitty Cat');
    $annotation_tester->assertPropertyHasTranslation('label');
    $annotation_tester->assertPropertyHasValue('label_collection', 'Kitty Cats');
    $annotation_tester->assertPropertyHasTranslation('label_collection');
    $annotation_tester->assertPropertyHasValue('label_singular', 'kitty cat');
    $annotation_tester->assertPropertyHasTranslation('label_singular');
    $annotation_tester->assertPropertyHasValue('label_plural', 'kitty cats');
    $annotation_tester->assertPropertyHasTranslation('label_plural');
    $annotation_tester->assertPropertyHasAnnotationClass('label_count', 'PluralTranslation');
    $annotation_tester->assertPropertyHasValue(['label_count', 'singular'], '@count kitty cat');
    $annotation_tester->assertPropertyHasValue(['label_count', 'plural'], '@count kitty cats');
    $annotation_tester->assertPropertyHasValue('base_table', 'kitty_cat');
    $annotation_tester->assertPropertyHasValue(['handlers', 'list_builder'], 'Drupal\Core\Entity\EntityListBuilder');
    $annotation_tester->assertPropertyHasValue('fieldable', 'TRUE');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'id'], 'kitty_cat_id');

    $entity_interface_file = $files['src/Entity/KittyCatInterface.php'];

    $this->assertWellFormedPHP($entity_interface_file);
    $this->assertDrupalCodingStandards($entity_interface_file);
    $this->assertNoTrailingWhitespace($entity_interface_file);

    $this->parseCode($entity_interface_file);
    $this->assertHasInterface('Drupal\test_module\Entity\KittyCatInterface');
  }

  /**
   * Test creating a content entity type with a bundle entity.
   */
  public function testEntityTypeWithBundleEntity() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'content_entity_types' => [
        0 => [
          // Use an ID string with an underscore to test class names and labels
          // correctly have it removed.
          'entity_type_id' => 'kitty_cat',
          'interface_parents' => [
            'EntityOwnerInterface',
          ],
          'bundle_entity' => [
            0 => [
              'entity_type_id' => 'kitty_cat_type',
              'entity_properties' => [
                0 => [
                  'name' => 'foo',
                  'type' => 'string',
                ],
                1 => [
                  'name' => 'colour',
                  'type' => 'string',
                ],
              ],
            ],
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
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(6, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Entity/KittyCat.php", $files, "The files list has an entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatInterface.php", $files, "The files list has an entity interface file.");
    $this->assertArrayHasKey("src/Entity/KittyCatType.php", $files, "The files list has a bundle entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatTypeInterface.php", $files, "The files list has a bundle entity interface file.");
    $this->assertArrayHasKey("config/schema/test_module.schema.yml", $files, "The files list has a config schema file.");

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $this->assertWellFormedPHP($entity_class_file);
    $this->assertNoTrailingWhitespace($entity_class_file);
    $this->assertClassFileFormatting($entity_class_file);

    $this->parseCode($entity_class_file);
    $this->assertHasClass('Drupal\test_module\Entity\KittyCat');
    $this->assertClassHasParent('Drupal\Core\Entity\ContentEntityBase');
    $this->assertHasMethods(['baseFieldDefinitions']);

    // TODO: the annotation assertion doens't handle arrays or nested
    // annotations.
    //$this->assertClassAnnotation('ContentEntityType', [], $entity_class_file);

    $bundle_entity_class_file = $files['src/Entity/KittyCatType.php'];

    $this->assertWellFormedPHP($bundle_entity_class_file);
    $this->assertNoTrailingWhitespace($bundle_entity_class_file);
    $this->assertClassFileFormatting($bundle_entity_class_file);

    $this->parseCode($bundle_entity_class_file);
    $this->assertHasClass('Drupal\test_module\Entity\KittyCatType');
    $this->assertClassHasParent('Drupal\Core\Config\Entity\ConfigEntityBase');
    $this->assertHasNoMethods();

    $config_yaml_file = $files['config/schema/test_module.schema.yml'];
    $yaml_tester = new YamlTester($config_yaml_file);
    $yaml_tester->assertHasProperty('test_module.kitty_cat_type');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat_type', 'type'], 'config_entity');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat_type', 'label'], 'Kitty Cat Type');
    $yaml_tester->assertHasProperty(['test_module.kitty_cat_type', 'mapping', 'foo']);
    $yaml_tester->assertHasProperty(['test_module.kitty_cat_type', 'mapping', 'colour']);
  }

}
