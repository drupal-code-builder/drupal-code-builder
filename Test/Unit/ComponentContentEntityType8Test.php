<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Tests the entity type generator class.
 *
 * @group yaml
 * @group annotation
 * @group entity
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
          'fieldable' => TRUE,
          'admin_permission' => TRUE,
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

    $this->assertCount(4, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("$module_name.permissions.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Entity/KittyCat.php", $files, "The files list has an entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatInterface.php", $files, "The files list has an entity interface file.");

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $php_tester = new PHPTester($entity_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\KittyCat');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\ContentEntityBase');
    $php_tester->assertHasMethods(['baseFieldDefinitions']);

    // Test the entity annotation.
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('ContentEntityType');
    $annotation_tester->assertHasRootProperties([
      'id',
      'label',
      'label_collection',
      'label_singular',
      'label_plural',
      'label_count',
      'base_table',
      'handlers',
      'admin_permission',
      'entity_keys',
      'field_ui_base_route',
    ]);
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
    $annotation_tester->assertPropertyHasValue('admin_permission', 'administer kitty cats');
    $annotation_tester->assertPropertyHasValue('field_ui_base_route', 'entity.kitty_cat.admin_form');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'id'], 'kitty_cat_id');

    $entity_interface_file = $files['src/Entity/KittyCatInterface.php'];

    $php_tester = new PHPTester($entity_interface_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasInterface('Drupal\test_module\Entity\KittyCatInterface');

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer kitty cats', "The permissions file declares the entity admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cats', 'title'], 'administer kitty cats', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cats', 'description'], 'Administer kitty cats', "The permission has the expected description.");
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
          'fieldable' => TRUE,
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

    $php_tester = new PHPTester($entity_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\KittyCat');
    $php_tester->assertHasMethods(['baseFieldDefinitions']);

    // Test the entity annotation.
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('ContentEntityType');
    $annotation_tester->assertHasRootProperties([
      'id',
      'label',
      'label_collection',
      'label_singular',
      'label_plural',
      'label_count',
      'bundle_label',
      'base_table',
      'entity_keys',
      'bundle_entity_type',
      'field_ui_base_route',
    ]);
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
    $annotation_tester->assertPropertyHasValue('bundle_label', 'Kitty Cat Type');
    $annotation_tester->assertPropertyHasTranslation('bundle_label');
    $annotation_tester->assertPropertyHasValue('base_table', 'kitty_cat');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'id'], 'kitty_cat_id');
    $annotation_tester->assertPropertyHasValue('bundle_entity_type', 'kitty_cat_type');
    $annotation_tester->assertPropertyHasValue('field_ui_base_route', 'entity.kitty_cat_type.edit_form');

    $bundle_entity_class_file = $files['src/Entity/KittyCatType.php'];

    $php_tester = new PHPTester($bundle_entity_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\KittyCatType');
    $php_tester->assertClassHasParent('Drupal\Core\Config\Entity\ConfigEntityBase');
    $php_tester->assertHasNoMethods();

    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('ConfigEntityType');

    $config_yaml_file = $files['config/schema/test_module.schema.yml'];
    $yaml_tester = new YamlTester($config_yaml_file);
    $yaml_tester->assertHasProperty('test_module.kitty_cat_type');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat_type', 'type'], 'config_entity');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat_type', 'label'], 'Kitty Cat Type');
    $yaml_tester->assertHasProperty(['test_module.kitty_cat_type', 'mapping', 'foo']);
    $yaml_tester->assertHasProperty(['test_module.kitty_cat_type', 'mapping', 'colour']);
  }

  /**
   * Test creating a content entity type with handlers.
   */
  public function testContentEntityTypeWithHandlers() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'content_entity_types' => [
        0 => [
          'entity_type_id' => 'kitty_cat',
          'handler_access' => TRUE,
          'handler_storage' => TRUE,
          'handler_storage_schema' => TRUE,
          'handler_view_builder' => TRUE,
          'handler_list_builder' => 'custom',
          'handler_views_data' => 'custom',
          'handler_translation' => TRUE,
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertArrayHasKey("src/Entity/Handler/KittyCatStorage.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatStorageSchema.php", $files, "The files list has a storage schema class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatAccess.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatViewBuilder.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatListBuilder.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatViewsData.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatTranslation.php", $files, "The files list has a translation class file.");

    $storage_class_file = $files['src/Entity/Handler/KittyCatStorage.php'];

    $php_tester = new PHPTester($storage_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\Handler\KittyCatStorage');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\Sql\SqlContentEntityStorage');
    $php_tester->assertClassDocBlockHasLine("Provides the storage handler for the Kitty Cat entity.");

    $storage_schema_class_file = $files['src/Entity/Handler/KittyCatStorageSchema.php'];

    $php_tester = new PHPTester($storage_schema_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\Handler\KittyCatStorageSchema');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema');
    $php_tester->assertClassDocBlockHasLine("Provides the storage schema handler for the Kitty Cat entity.");

    $access_class_file = $files['src/Entity/Handler/KittyCatAccess.php'];

    $php_tester = new PHPTester($access_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\Handler\KittyCatAccess');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\EntityAccessControlHandler');
    $php_tester->assertClassDocBlockHasLine("Provides the access handler for the Kitty Cat entity.");

    $view_builder_class_file = $files['src/Entity/Handler/KittyCatViewBuilder.php'];

    $php_tester = new PHPTester($view_builder_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\Handler\KittyCatViewBuilder');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\EntityViewBuilder');
    $php_tester->assertClassDocBlockHasLine("Provides the view builder handler for the Kitty Cat entity.");

    $list_builder_class_file = $files['src/Entity/Handler/KittyCatListBuilder.php'];

    $php_tester = new PHPTester($list_builder_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\Handler\KittyCatListBuilder');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\EntityListBuilder');
    $php_tester->assertClassDocBlockHasLine("Provides the list builder handler for the Kitty Cat entity.");

    $views_data_class_file = $files['src/Entity/Handler/KittyCatViewsData.php'];

    $php_tester = new PHPTester($views_data_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\Handler\KittyCatViewsData');
    $php_tester->assertClassHasParent('Drupal\views\EntityViewsData');
    $php_tester->assertClassDocBlockHasLine("Provides the Views data handler for the Kitty Cat entity.");

    $translation_class_file = $files['src/Entity/Handler/KittyCatTranslation.php'];

    $php_tester = new PHPTester($translation_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\Handler\KittyCatTranslation');
    $php_tester->assertClassHasParent('Drupal\content_translation\ContentTranslationHandler');
    $php_tester->assertClassDocBlockHasLine("Provides the translation handler for the Kitty Cat entity.");
  }

}
