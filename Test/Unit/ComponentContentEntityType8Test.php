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
class ComponentContentEntityType8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Test creating a content entity type.
   */
  public function testBasicContentEntityType() {
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
    $annotation_tester->assertNotHasProperty(['entity_keys', 'langcode']);
    $annotation_tester->assertNotHasProperty(['entity_keys', 'bundle']);

    $entity_interface_file = $files['src/Entity/KittyCatInterface.php'];

    $php_tester = new PHPTester($entity_interface_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasInterface('Drupal\test_module\Entity\KittyCatInterface');

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer kitty cats', "The permissions file declares the entity admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cats', 'title'], 'Administer kitty cats', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cats', 'description'], 'Administer kitty cats', "The permission has the expected description.");
  }

  /**
   * Test creating a translatable content entity type.
   */
  public function testEntityTypeWithTranslation() {
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
          'translatable' => TRUE,
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

    $expected_method_calls = [
      "setLabel",
      "setDescription",
      "setTranslatable",
    ];
    // The first statement is the call to the parent; subsequent statements
    // should all be field creation.
    $php_tester->assertStatementHasChainedMethodCalls($expected_method_calls, 'baseFieldDefinitions', 1);
    $php_tester->assertStatementHasChainedMethodCalls($expected_method_calls, 'baseFieldDefinitions', 2);

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
      'data_table',
      'translatable',
      'handlers',
      'admin_permission',
      'entity_keys',
      'field_ui_base_route',
    ]);
    $annotation_tester->assertPropertyHasValue('translatable', 'TRUE');
    $annotation_tester->assertPropertyHasValue('base_table', 'kitty_cat');
    $annotation_tester->assertPropertyHasValue('data_table', 'kitty_cat_field_data');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'id'], 'kitty_cat_id');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'langcode'], 'langcode');
  }

  /**
   * Test creating a revisionable content entity type.
   */
  public function testEntityTypeWithRevisions() {
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
          'revisionable' => TRUE,
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

    $expected_method_calls = [
      "setLabel",
      "setDescription",
      "setRevisionable",
    ];
    // The first statement is the call to the parent; subsequent statements
    // should all be field creation.
    $php_tester->assertStatementHasChainedMethodCalls($expected_method_calls, 'baseFieldDefinitions', 1);
    $php_tester->assertStatementHasChainedMethodCalls($expected_method_calls, 'baseFieldDefinitions', 2);

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
      'revision_table',
      'handlers',
      'admin_permission',
      'entity_keys',
      'field_ui_base_route',
    ]);
    $annotation_tester->assertPropertyHasValue('base_table', 'kitty_cat');
    $annotation_tester->assertPropertyHasValue('revision_table', 'kitty_cat_revision');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'id'], 'kitty_cat_id');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'revision'], 'revision_id');
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
              // Request a route provider so UI features are generated.
              'handler_route_provider' => 'admin',
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

    $this->assertCount(7, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Entity/KittyCat.php", $files, "The files list has an entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatInterface.php", $files, "The files list has an entity interface file.");
    $this->assertArrayHasKey("src/Entity/KittyCatType.php", $files, "The files list has a bundle entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatTypeInterface.php", $files, "The files list has a bundle entity interface file.");
    $this->assertArrayHasKey("config/schema/test_module.schema.yml", $files, "The files list has a config schema file.");
    $this->assertArrayHasKey("test_module.permissions.yml", $files, "The files list has a permissions file.");

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
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'bundle'], 'type');
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

    // Check the permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer kitty cat types', "The permissions file declares the entity admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cat types', 'title'], 'Administer kitty cat types', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cat types', 'description'], 'Administer kitty cat types', "The permission has the expected description.");
  }

  /**
   * Tests creating a content entity type with handlers.
   *
   * This covers the different combinations of options.
   *
   * @dataProvider providerHandlers
   */
  public function testContentEntityTypeHandlers($handler_properties, $expected_handlers_annotation, $expected_files_base_classes) {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'content_entity_types' => [
        0 => [
          'entity_type_id' => 'kitty_cat',
        ]
        + $handler_properties,
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    // Use array_values() as preg_grep() will keep original numeric keys.
    $handler_filenames = array_values(preg_grep('@^src/Entity/Handler@', array_keys($files)));
    $expected_filenames = array_keys($expected_files_base_classes);
    $this->assertEquals($expected_filenames, $handler_filenames);

    // Test the entity annotation.
    $entity_class_file = $files['src/Entity/KittyCat.php'];
    $php_tester = new PHPTester($entity_class_file);
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    $annotation_tester->assertPropertyHasValue(['handlers'], $expected_handlers_annotation);

    // Test the base classes.
    foreach ($expected_files_base_classes as $filename => $base_class) {
      $handler_class_file = $files[$filename];
      $php_tester = new PHPTester($handler_class_file);
      $php_tester->assertClassHasParent($base_class);
    }
  }

  /**
   * Data provider for testContentEntityTypeHandlers()
   */
  public function providerHandlers() {
    return [
      'custom access' => [
        // Handler properties.
        [
          'handler_access' => TRUE,
        ],
        // Anotation.
        [
          'access' => 'Drupal\test_module\Entity\Handler\KittyCatAccess',
        ],
        // Expected handler files and base classes.
        [
          'src/Entity/Handler/KittyCatAccess.php' => 'Drupal\Core\Entity\EntityAccessControlHandler',
        ],
      ],
      'custom storage' => [
        [
          'handler_storage' => TRUE,
        ],
        [
          'storage' => 'Drupal\test_module\Entity\Handler\KittyCatStorage',
        ],
        [
          'src/Entity/Handler/KittyCatStorage.php' => 'Drupal\Core\Entity\Sql\SqlContentEntityStorage',
        ],
      ],
      'custom storage schema' => [
        [
          'handler_storage_schema' => TRUE,
        ],
        [
          'storage_schema' => 'Drupal\test_module\Entity\Handler\KittyCatStorageSchema',
        ],
        [
          'src/Entity/Handler/KittyCatStorageSchema.php' => 'Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema',
        ],
      ],
      'custom view builder' => [
        [
          'handler_view_builder' => TRUE,
        ],
        [
          'view_builder' => 'Drupal\test_module\Entity\Handler\KittyCatViewBuilder',
        ],
        [
          'src/Entity/Handler/KittyCatViewBuilder.php' => 'Drupal\Core\Entity\EntityViewBuilder',
        ],
      ],
      'custom translation' => [
        [
          'handler_translation' => TRUE,
        ],
        [
          'translation' => 'Drupal\test_module\Entity\Handler\KittyCatTranslation',
        ],
        [
          'src/Entity/Handler/KittyCatTranslation.php' => 'Drupal\content_translation\ContentTranslationHandler',
        ],
      ],
      // Tests the 'none' option for handlers that aren't filled in by core.
      'no list builder' => [
        [
          'handler_list_builder' => 'none',
        ],
        // No handler annotations.
        NULL,
        // No custom handler classes are generated.
        [],
      ],
      'core list builder' => [
        [
          'handler_list_builder' => 'core',
        ],
        [
          'list_builder' => 'Drupal\Core\Entity\EntityListBuilder',
        ],
        // No custom handler classes are generated.
        [],
      ],
      'custom list builder' => [
        [
          'handler_list_builder' => 'custom',
        ],
        [
          'list_builder' => 'Drupal\test_module\Entity\Handler\KittyCatListBuilder',
        ],
        [
          'src/Entity/Handler/KittyCatListBuilder.php' => 'Drupal\Core\Entity\EntityListBuilder',
        ],
      ],
      'core views data' => [
        [
          'handler_views_data' => 'core',
        ],
        [
          'views_data' => 'Drupal\views\EntityViewsData',
        ],
        // No custom handler classes are generated.
        [],
      ],
      'custom views data' => [
        [
          'handler_views_data' => 'custom',
        ],
        [
          'views_data' => 'Drupal\test_module\Entity\Handler\KittyCatViewsData',
        ],
        [
          'src/Entity/Handler/KittyCatViewsData.php' => 'Drupal\views\EntityViewsData',
        ],
      ],
      'no route provider' => [
        [
          'handler_route_provider' => 'none',
        ],
        NULL,
        // No custom handler classes are generated.
        [],
      ],
      'default core route provider' => [
        [
          'handler_route_provider' => 'default',
        ],
        [
          'route_provider' => [
            'html' => 'Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider',
          ],
          // Forces the default form and list handlers.
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
          ],
          'list_builder' => 'Drupal\Core\Entity\EntityListBuilder',
        ],
        [],
      ],
      'admin core route provider' => [
        [
          'handler_route_provider' => 'admin',
        ],
        [
          'route_provider' => [
            'html' => 'Drupal\Core\Entity\Routing\AdminHtmlRouteProvider',
          ],
          // Forces the default form and list handlers.
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
          ],
          'list_builder' => 'Drupal\Core\Entity\EntityListBuilder',
        ],
        [],
      ],
      'custom route provider' => [
        [
          'handler_route_provider' => 'custom',
        ],
        [
          'route_provider' => [
            'html' => 'Drupal\test_module\Entity\Handler\KittyCatRouteProvider',
          ],
          // Forces the default form and list handlers.
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
          ],
          'list_builder' => 'Drupal\Core\Entity\EntityListBuilder',
        ],
        [
          'src/Entity/Handler/KittyCatRouteProvider.php' => 'Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider',
        ],
      ],
      'default core route provider with custom default form' => [
        // Tests the the route handler forcing the form doesn't kick in when
        // the form is specified.
        [
          'handler_route_provider' => 'default',
          'handler_form_default' => 'custom',
        ],
        [
          'route_provider' => [
            'html' => 'Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider',
          ],
          'form' => [
            'default' => 'Drupal\test_module\Entity\Handler\KittyCatForm',
          ],
          'list_builder' => 'Drupal\Core\Entity\EntityListBuilder',
        ],
        [
          'src/Entity/Handler/KittyCatForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
        ],
      ],
      'default core route provider with custom list builder' => [
        // Tests the the route handler forcing the form doesn't kick in when
        // the list builder is specified.
        [
          'handler_route_provider' => 'default',
          'handler_list_builder' => 'custom',
        ],
        [
          'route_provider' => [
            'html' => 'Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider',
          ],
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
          ],
          'list_builder' => 'Drupal\test_module\Entity\Handler\KittyCatListBuilder',
        ],
        [
          'src/Entity/Handler/KittyCatListBuilder.php' => 'Drupal\Core\Entity\EntityListBuilder',
        ],
      ],
      'no default form' => [
        [
          'handler_form_default' => 'none',
        ],
        NULL,
        // No custom handler classes are generated.
        [],
      ],
      'core default form' => [
        [
          'handler_form_default' => 'core',
        ],
        [
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
          ],
        ],
        // No custom handler classes are generated.
        [],
      ],
      'custom default form' => [
        [
          'handler_form_default' => 'custom',
        ],
        [
          'form' => [
            'default' => 'Drupal\test_module\Entity\Handler\KittyCatForm',
          ],
        ],
        [
          'src/Entity/Handler/KittyCatForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
        ],
      ],
      'no add form' => [
        [
          'handler_form_add' => 'none',
        ],
        NULL,
        [],
      ],
      'default add form overriding default form set to empty' => [
        [
          'handler_form_add' => 'default',
        ],
        [
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
            'add' => 'Drupal\Core\Entity\ContentEntityForm',
          ],
        ],
        [],
      ],
      'default add form overriding default form set to none' => [
        [
          'handler_form_default' => 'none',
          'handler_form_add' => 'default',
        ],
        [
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
            'add' => 'Drupal\Core\Entity\ContentEntityForm',
          ],
        ],
        [],
      ],
      'default add form with default form set to core' => [
        [
          'handler_form_default' => 'core',
          'handler_form_add' => 'default',
        ],
        [
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
            'add' => 'Drupal\Core\Entity\ContentEntityForm',
          ],
        ],
        [],
      ],
      'default add form with default form set to custom' => [
        [
          'handler_form_default' => 'custom',
          'handler_form_add' => 'default',
        ],
        [
          'form' => [
            'default' => 'Drupal\test_module\Entity\Handler\KittyCatForm',
            'add' => 'Drupal\test_module\Entity\Handler\KittyCatForm',
          ],
        ],
        [
          'src/Entity/Handler/KittyCatForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
        ],
      ],
      'custom add form overriding default form set to empty' => [
        [
          'handler_form_add' => 'custom',
        ],
        [
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
            'add' => 'Drupal\test_module\Entity\Handler\KittyCatAddForm',
          ],
        ],
        [
          'src/Entity/Handler/KittyCatAddForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
        ],
      ],
      'custom add form overriding default form set to none' => [
        [
          'handler_form_default' => 'none',
          'handler_form_add' => 'custom',
        ],
        [
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
            'add' => 'Drupal\test_module\Entity\Handler\KittyCatAddForm',
          ],
        ],
        [
          'src/Entity/Handler/KittyCatAddForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
        ],
      ],
      'custom add form with default form set to core' => [
        [
          'handler_form_default' => 'core',
          'handler_form_add' => 'custom',
        ],
        [
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
            'add' => 'Drupal\test_module\Entity\Handler\KittyCatAddForm',
          ],
        ],
        [
          'src/Entity/Handler/KittyCatAddForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
        ],
      ],
      'custom add form with default form set to custom' => [
        [
          'handler_form_default' => 'custom',
          'handler_form_add' => 'custom',
        ],
        [
          'form' => [
            'default' => 'Drupal\test_module\Entity\Handler\KittyCatForm',
            'add' => 'Drupal\test_module\Entity\Handler\KittyCatAddForm',
          ],
        ],
        [
          'src/Entity/Handler/KittyCatForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
          'src/Entity/Handler/KittyCatAddForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
        ],
      ],
    ];
  }

  /**
   * Tests generating of custom handler classes.
   */
  public function testContentEntityTypeCustomHandlers() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'content_entity_types' => [
        0 => [
          'entity_type_id' => 'kitty_cat',
          'admin_permission' => FALSE,
          'handler_access' => TRUE,
          'handler_storage' => TRUE,
          'handler_storage_schema' => TRUE,
          'handler_view_builder' => TRUE,
          'handler_list_builder' => 'custom',
          'handler_views_data' => 'custom',
          'handler_translation' => TRUE,
          'handler_route_provider' => 'custom',
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertArrayHasKey("$module_name.permissions.yml", $files, "The admin permission property was overridden.");

    $handler_filenames = preg_grep('@^src/Entity/Handler@', array_keys($files));
    $this->assertCount(8, $handler_filenames, "Expected number of handler files is returned.");

    $this->assertArrayHasKey("src/Entity/Handler/KittyCatStorage.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatStorageSchema.php", $files, "The files list has a storage schema class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatAccess.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatViewBuilder.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatListBuilder.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatViewsData.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatTranslation.php", $files, "The files list has a translation class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatRouteProvider.php", $files, "The files list has a route provider class file.");

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

    $router_provider_class_file = $files['src/Entity/Handler/KittyCatRouteProvider.php'];

    $php_tester = new PHPTester($router_provider_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\Handler\KittyCatRouteProvider');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider');
    $php_tester->assertClassDocBlockHasLine("Provides the route provider handler for the Kitty Cat entity.");
  }

  /**
   * Tests creating a content entity with a UI.
   *
   * @group entity_ui
   */
  public function testContentEntityTypeWithUI() {
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'content_entity_types' => [
        0 => [
          'entity_type_id' => 'kitty_cat',
          'entity_ui' => 'admin',
          // Check these get overridden.
          'handler_route_provider' => 'none',
          'admin_permission' => FALSE,
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertArrayHasKey("$module_name.permissions.yml", $files, "The admin permission property was overridden.");
    $this->assertArrayHasKey("$module_name.links.task.yml", $files, "The admin permission property was overridden.");

    // Check the links are declared.
    $entity_class_file = $files['src/Entity/KittyCat.php'];
    $php_tester = new PHPTester($entity_class_file);
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
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
      'links',
    ]);
    $annotation_tester->assertPropertyHasValue(['links', 'add-form'], "/kitty_cat/add");
    $annotation_tester->assertPropertyHasValue(['links', 'canonical'], "/kitty_cat/{kitty_cat}");
    $annotation_tester->assertPropertyHasValue(['links', 'collection'], "/admin/content/kitty_cat");
    $annotation_tester->assertPropertyHasValue(['links', 'delete-form'], "/kitty_cat/{kitty_cat}/delete");
    $annotation_tester->assertPropertyHasValue(['links', 'edit-form'], "/kitty_cat/{kitty_cat}/edit");

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer kitty cats', "The permissions file declares the entity admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cats', 'title'], 'Administer kitty cats', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cats', 'description'], 'Administer kitty cats', "The permission has the expected description.");

    // Check the tasks plugin file.
    $tasks_file = $files["$module_name.links.task.yml"];
    $yaml_tester = new YamlTester($tasks_file);

    $yaml_tester->assertHasProperty('entity.kitty_cat.collection', "The tasks file defines the task for the collection.");
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.collection', 'title'], 'Kitty Cats');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.collection', 'route_name'], 'entity.kitty_cat.collection');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.collection', 'base_route'], 'system.admin_content');

    // TODO: expand these.
    $yaml_tester->assertHasProperty('entity.kitty_cat.canonical', "The tasks file defines the task for the view route.");
    $yaml_tester->assertHasProperty('entity.kitty_cat.edit_form', "The tasks file defines the task for the edit form.");
    $yaml_tester->assertHasProperty('entity.kitty_cat.delete_form', "The tasks file defines the task for the delete form.");
  }

  /**
   * Test creating a content entity type with a bundle entity and UI.
   *
   * @group entity_ui
   */
  public function testEntityTypeWithUIAndBundleEntity() {
    // Create a module.
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'content_entity_types' => [
        0 => [
          'entity_type_id' => 'kitty_cat',
          'entity_ui' => 'admin',
          'bundle_entity' => [
            0 => [
              'entity_type_id' => 'kitty_cat_type',
              'entity_ui' => 'admin',
            ],
          ],
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertCount(10, $files, "Expected number of files is returned.");
    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info.yml file.");
    $this->assertArrayHasKey("src/Entity/KittyCat.php", $files, "The files list has an entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatInterface.php", $files, "The files list has an entity interface file.");
    $this->assertArrayHasKey("src/Entity/KittyCatType.php", $files, "The files list has a bundle entity class file.");
    $this->assertArrayHasKey("src/Entity/KittyCatTypeInterface.php", $files, "The files list has a bundle entity interface file.");
    $this->assertArrayHasKey("config/schema/test_module.schema.yml", $files, "The files list has a config schema file.");
    $this->assertArrayHasKey("test_module.permissions.yml", $files, "The files list has a permissions file.");
    $this->assertArrayHasKey("test_module.links.menu.yml", $files, "The files list has a menu links file.");
    $this->assertArrayHasKey("test_module.links.action.yml", $files, "The files list has an action links file.");
    $this->assertArrayHasKey("test_module.links.task.yml", $files, "The files list has an task links file.");

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $php_tester = new PHPTester($entity_class_file);

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
      'handlers',
      'admin_permission',
      'entity_keys',
      'bundle_entity_type',
      'links',
    ]);

    $bundle_entity_class_file = $files['src/Entity/KittyCatType.php'];

    $php_tester = new PHPTester($bundle_entity_class_file);

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
      'bundle_of',
    ]);

    // Check the permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer kitty cat types', "The permissions file declares the entity admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cat types', 'title'], 'Administer kitty cat types', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cat types', 'description'], 'Administer kitty cat types', "The permission has the expected description.");

    // Check the menu links file.
    $menu_links_file = $files["test_module.links.menu.yml"];

    $yaml_tester = new YamlTester($menu_links_file);
    $yaml_tester->assertHasProperty('entity.kitty_cat_type.collection');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat_type.collection', 'title'], 'Kitty Cat Types');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat_type.collection', 'description'], 'Create and manage fields, forms, and display settings for Kitty Cat Types.');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat_type.collection', 'route_name'], 'entity.kitty_cat_type.collection');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat_type.collection', 'parent'], 'system.admin_structure');
  }

}
