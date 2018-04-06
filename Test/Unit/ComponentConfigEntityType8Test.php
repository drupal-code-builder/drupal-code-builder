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
  public function testBasicConfigEntityType() {
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
      'label_collection',
      'label_singular',
      'label_plural',
      'label_count',
      'entity_keys',
      'config_export',
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
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'id'], 'id');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'label'], 'label');
    $annotation_tester->assertPropertyHasValue('config_export', ['id', 'label', 'breed', 'colour']);

    $entity_interface_file = $files['src/Entity/KittyCatInterface.php'];

    $php_tester = new PHPTester($entity_interface_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasInterface('Drupal\test_module\Entity\KittyCatInterface');

    $schema_file = $files['config/schema/test_module.schema.yml'];
    $yaml_tester = new YamlTester($schema_file);
    $yaml_tester->assertHasProperty('test_module.kitty_cat');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat', 'type'], 'config_entity');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat', 'label'], 'Kitty Cat');

    $yaml_tester->assertHasProperty(['test_module.kitty_cat', 'mapping', 'id']);
    $yaml_tester->assertHasProperty(['test_module.kitty_cat', 'mapping', 'label']);

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
          'handler_list_builder' => 'custom',
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(7, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Entity/KittyCat.php", $files, "The files list has an entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatInterface.php", $files, "The files list has an entity interface file.");
    $this->assertArrayHasKey("config/schema/test_module.schema.yml", $files, "The files list has a config schema file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatAccess.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatStorage.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatListBuilder.php", $files, "The files list has an list builder class file.");

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $php_tester = new PHPTester($entity_class_file);

    // Test the entity annotation.
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('ConfigEntityType');
    $annotation_tester->assertHasRootProperties([
      'id',
      'label',
      'label_collection',
      'label_singular',
      'label_plural',
      'label_count',
      'handlers',
      'entity_keys',
      'config_export',
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

    $list_builder_class_file = $files['src/Entity/Handler/KittyCatListBuilder.php'];

    $php_tester = new PHPTester($list_builder_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\Handler\KittyCatListBuilder');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\EntityListBuilder');
    $php_tester->assertClassDocBlockHasLine("Provides the list builder handler for the Kitty Cat entity.");
  }

  /**
   * Test creating a config entity type with a UI.
   *
   * @group entity_ui
   */
  public function testConfigEntityTypeWithUI() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'config_entity_types' => [
        0 => [
          'entity_type_id' => 'kitty_cat',
          // Requesting an entity UI should trigger various other things:
          // - default form handler
          // - admin permission
          // - menu link plugin
          'entity_ui' => 'admin',
          // Check these get overridden.
          'handler_route_provider' => 'none',
          'admin_permission' => FALSE,
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(7, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Entity/KittyCat.php", $files, "The files list has an entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatInterface.php", $files, "The files list has an entity interface file.");
    $this->assertArrayHasKey("config/schema/test_module.schema.yml", $files, "The files list has a config schema file.");
    $this->assertArrayHasKey("test_module.permissions.yml", $files, "The files list has a permissions file.");
    $this->assertArrayHasKey("test_module.links.menu.yml", $files, "The files list has a menu links file.");
    $this->assertArrayHasKey("test_module.links.action.yml", $files, "The files list has an action links file.");

    $entity_class_file = $files['src/Entity/KittyCat.php'];
    $php_tester = new PHPTester($entity_class_file);

    // Test the entity annotation.
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertAnnotationClass('ConfigEntityType');
    $annotation_tester->assertPropertyHasValue(['handlers', 'route_provider', 'html'], 'Drupal\Core\Entity\Routing\AdminHtmlRouteProvider');
    $annotation_tester->assertPropertyHasValue(['handlers', 'form', 'default'], 'Drupal\Core\Entity\EntityForm', 'The entity type has a default form handler.');

    // Check the links are declared.
    $entity_class_file = $files['src/Entity/KittyCat.php'];
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertHasRootProperties([
      'id',
      'label',
      'label_collection',
      'label_singular',
      'label_plural',
      'label_count',
      'handlers',
      'admin_permission',
      'entity_keys',
      'config_export',
      'links',
    ]);
    $annotation_tester->assertPropertyHasValue(['links', 'canonical'], "/admin/structure/kitty_cat/{kitty_cat}");
    $annotation_tester->assertPropertyHasValue(['links', 'collection'], "/admin/structure/kitty_cat");
    $annotation_tester->assertPropertyHasValue(['links', 'add-form'], "/admin/structure/kitty_cat/add");
    $annotation_tester->assertPropertyHasValue(['links', 'edit-form'], "/admin/structure/kitty_cat/{kitty_cat}/edit");
    $annotation_tester->assertPropertyHasValue(['links', 'delete-form'], "/admin/structure/kitty_cat/{kitty_cat}/delete");

    // Check the permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer kitty cats', "The permissions file declares the entity admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cats', 'title'], 'Administer kitty cats', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cats', 'description'], 'Administer kitty cats', "The permission has the expected description.");

    // Check the menu links file.
    $menu_links_file = $files["test_module.links.menu.yml"];

    $yaml_tester = new YamlTester($menu_links_file);
    $yaml_tester->assertHasProperty('entity.kitty_cat.collection', 'The entity type has a collection menu link.');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.collection', 'title'], 'Kitty Cats');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.collection', 'description'], 'Create and manage fields, forms, and display settings for Kitty Cats.');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.collection', 'route_name'], 'entity.kitty_cat.collection');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.collection', 'parent'], 'system.admin_structure');

    // Check the action links file.
    $action_links_file = $files["test_module.links.action.yml"];

    $yaml_tester = new YamlTester($action_links_file);
    $yaml_tester->assertHasProperty('entity.kitty_cat.add', 'The entity type has an add action link.');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.add', 'title'], 'Add Kitty Cat');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.add', 'route_name'], 'entity.kitty_cat.add_form');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.add', 'appears_on'], ['entity.kitty_cat.collection']);
  }

}
