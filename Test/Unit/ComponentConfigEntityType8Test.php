<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests the config entity type generator class.
 *
 * @group yaml
 * @group entity
 */
class ComponentConfigEntityType8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test creating a config entity type.
   */
  public function testConfigEntityType() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'config_entity_types' => [
        0 => [
          // Use an ID string with an underscore to test class names and labels
          // correctly have it removed.
          'entity_type_id' => 'kitty_cat',
          'entity_properties' => [
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

    $this->assertCount(4, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Entity/KittyCat.php", $files, "The files list has an entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatInterface.php", $files, "The files list has an entity interface file.");
    $this->assertArrayHasKey("config/schema/test_module.schema.yml", $files, "The files list has a config schema file.");

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $php_tester = new PHPTester($entity_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\KittyCat');
    $php_tester->assertClassHasParent('Drupal\Core\Config\Entity\ConfigEntityBase');
    $php_tester->assertHasNoMethods();
    $php_tester->assertClassHasProtectedProperty('breed', 'string', '');
    $php_tester->assertClassHasProtectedProperty('colour', 'string', '');

    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('ConfigEntityType');
    $annotation_tester->assertHasRootProperties([
      'id',
      'label',
      'entity_keys',
      'config_export',
    ]);
    $annotation_tester->assertPropertyHasValue('id', 'kitty_cat');
    $annotation_tester->assertPropertyHasValue('label', 'Kitty Cat');
    $annotation_tester->assertPropertyHasTranslation('label');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'id'], 'kitty_cat_id');
    $annotation_tester->assertPropertyHasValue('config_export', ['breed', 'colour']);

    $entity_interface_file = $files['src/Entity/KittyCatInterface.php'];

    $php_tester = new PHPTester($entity_interface_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasInterface('Drupal\test_module\Entity\KittyCatInterface');

    $schema_file = $files['config/schema/test_module.schema.yml'];
    $yaml_tester = new YamlTester($schema_file);
    $yaml_tester->assertHasProperty('test_module.kitty_cat');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat', 'type'], 'config_entity');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat', 'label'], 'Kitty Cat');

    $yaml_tester->assertHasProperty(['test_module.kitty_cat', 'mapping', 'breed']);
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat', 'mapping', 'breed', 'type'], 'string');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat', 'mapping', 'breed', 'label'], 'Breed');

    $yaml_tester->assertHasProperty(['test_module.kitty_cat', 'mapping', 'colour']);
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat', 'mapping', 'colour', 'type'], 'string');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat', 'mapping', 'colour', 'label'], 'Colour');
  }

  /**
   * Test the formatting of the schema for multiple entity types.
   */
  public function testConfigEntityTypeSchemaFormatting() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'config_entity_types' => [
        0 => [
          'entity_type_id' => 'alpha',
          'entity_properties' => [
            0 => [
              'name' => 'breed',
              'type' => 'string',
            ],
          ],
        ],
        1 => [
          'entity_type_id' => 'beta',
          'entity_properties' => [
            0 => [
              'name' => 'colour',
              'type' => 'string',
            ],
          ],
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);
    $schema_file = $files['config/schema/test_module.schema.yml'];

    $yaml_tester = new YamlTester($schema_file);
    $yaml_tester->assertPropertyHasBlankLineBefore(['test_module.beta']);
  }

  /**
   * Test creating a config entity type with handlers.
   */
  public function testConfigEntityTypeWithHandlers() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'config_entity_types' => [
        0 => [
          'entity_type_id' => 'kitty_cat',
          'handler_access' => TRUE,
          'handler_storage' => TRUE,
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(6, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Entity/KittyCat.php", $files, "The files list has an entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatInterface.php", $files, "The files list has an entity interface file.");
    $this->assertArrayHasKey("config/schema/test_module.schema.yml", $files, "The files list has a config schema file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatAccess.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatStorage.php", $files, "The files list has an list builder class file.");

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $php_tester = new PHPTester($entity_class_file);

    // Test the entity annotation.
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('ConfigEntityType');
    $annotation_tester->assertHasRootProperties([
      'id',
      'label',
      'handlers',
      'entity_keys',
    ]);
    $annotation_tester->assertPropertyHasValue(['handlers', 'access'], 'Drupal\test_module\Entity\Handler\KittyCatAccess');
    $annotation_tester->assertPropertyHasValue(['handlers', 'storage'], 'Drupal\test_module\Entity\Handler\KittyCatStorage');

    $access_class_file = $files['src/Entity/Handler/KittyCatAccess.php'];

    $php_tester = new PHPTester($access_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\Handler\KittyCatAccess');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\EntityAccessControlHandler');
    $php_tester->assertClassDocBlockHasLine("Provides the access handler for the Kitty Cat entity.");

    $storage_class_file = $files['src/Entity/Handler/KittyCatStorage.php'];

    $php_tester = new PHPTester($storage_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\Handler\KittyCatStorage');
    $php_tester->assertClassHasParent('Drupal\Core\Config\Entity\ConfigEntityStorage');
    $php_tester->assertClassDocBlockHasLine("Provides the storage handler for the Kitty Cat entity.");
  }

}
