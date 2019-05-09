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
          'functionality' => [
            'fieldable',
          ],
          'admin_permission' => TRUE,
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

    $this->assertFiles([
      'test_module.info.yml',
      'src/Entity/KittyCat.php',
      'src/Entity/KittyCatInterface.php',
      'test_module.permissions.yml',
    ], $files);

    $entity_interface_file = $files['src/Entity/KittyCatInterface.php'];
    $php_tester = new PHPTester($entity_interface_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasInterface('Drupal\test_module\Entity\KittyCatInterface');
    $php_tester->assertInterfaceHasParents(['Drupal\Core\Entity\ContentEntityInterface']);

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $php_tester = new PHPTester($entity_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\KittyCat');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\ContentEntityBase');
    $php_tester->assertClassHasInterfaces(['Drupal\test_module\Entity\KittyCatInterface']);
    $php_tester->assertHasMethods(['baseFieldDefinitions']);

    // Test the field definitions.
    $base_fields_definitions_tester = $php_tester->getBaseFieldDefinitionsTester();

    $base_fields_definitions_tester->assertFieldNames([
      'title',
      'breed',
      'colour',
    ]);

    $base_fields_definitions_tester->assertFieldType('string', 'title');

    $base_fields_definitions_tester->assertFieldDefinitionMethodCalls([
      "setLabel",
      "setRequired",
      'setSetting',
      'setDisplayOptions',
      'setDisplayConfigurable',
      'setDisplayConfigurable',
    ], 'title');

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
    $annotation_tester->assertHasProperties([
      'id',
      'label',
      'uuid',
    ], 'entity_keys');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'id'], 'kitty_cat_id');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'label'], 'title');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'uuid'], 'uuid');
    $annotation_tester->assertNotHasProperty(['entity_keys', 'langcode']);
    $annotation_tester->assertNotHasProperty(['entity_keys', 'bundle']);

    $entity_interface_file = $files['src/Entity/KittyCatInterface.php'];

    $php_tester = new PHPTester($entity_interface_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasInterface('Drupal\test_module\Entity\KittyCatInterface');
    $php_tester->assertInterfaceHasParents(['Drupal\Core\Entity\ContentEntityInterface']);

    // Check the .permissions file.
    $permissions_file = $files["$module_name.permissions.yml"];
    $yaml_tester = new YamlTester($permissions_file);

    $yaml_tester->assertHasProperty('administer kitty cats', "The permissions file declares the entity admin permission.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cats', 'title'], 'Administer kitty cats', "The permission has the expected title.");
    $yaml_tester->assertPropertyHasValue(['administer kitty cats', 'description'], 'Administer kitty cats', "The permission has the expected description.");
  }

  /**
   * Tests the parent interfaces.
   *
   * This covers the different combinations of options.
   *
   * @dataProvider providerContentEntityTypeFunctionalityOptions
   */
  public function testContentEntityTypeFunctionalityOptions(
    $interface_option,
    $expected_parent_interfaces,
    $expected_extra_entity_keys = [],
    $expected_traits = [],
    $expected_extra_base_fields = [],
    $expected_base_field_helper_calls = [],
    $expected_extra_methods = []
  ) {
    $module_name = 'test_module';
    $module_data = array(
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test module',
      'content_entity_types' => [
        0 => [
          'entity_type_id' => 'kitty_cat',
          'functionality' => $interface_option
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $entity_interface_file = $files['src/Entity/KittyCatInterface.php'];

    $php_tester = new PHPTester($entity_interface_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasInterface('Drupal\test_module\Entity\KittyCatInterface');
    $php_tester->assertInterfaceHasParents($expected_parent_interfaces);

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $php_tester = new PHPTester($entity_class_file);
    // TODO: remove this coding standards exception when the comment for core
    // issue https://www.drupal.org/project/drupal/issues/2949964 is removed
    // from content entity type generation. See
    // ContentEntityType::collectSectionBlocks().
    $php_tester->assertDrupalCodingStandards(['Drupal.Commenting.InlineComment.SpacingAfter']);

    // Test the additional entity keys.
    $annotation_tester = $php_tester->getAnnotationTesterForClass();
    foreach ($expected_extra_entity_keys as $entity_key => $real_field) {
      $annotation_tester->assertPropertyHasValue(['entity_keys', $entity_key], $real_field);
    }

    $php_tester->assertHasClass('Drupal\test_module\Entity\KittyCat');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\ContentEntityBase');

    $php_tester->assertClassHasTraits($expected_traits);

    // Test the field definitions.
    $base_fields_definitions_tester = $php_tester->getBaseFieldDefinitionsTester();

    $expected_fields = array_merge(
      [
        'title'
      ],
      array_keys($expected_extra_base_fields)
    );
    $base_fields_definitions_tester->assertFieldNames($expected_fields);

    foreach ($expected_extra_base_fields as $field_name => $type) {
      $base_fields_definitions_tester->assertFieldType($type, $field_name);
    }

    $base_fields_definitions_tester->assertHelperMethodCalls($expected_base_field_helper_calls);

    // TODO: make this check for incorrect presence.
    if ($expected_extra_methods) {
      $php_tester->assertHasMethods($expected_extra_methods);
    }
  }

  /**
   * Data provider for testContentEntityTypeFunctionalityOptions.
   */
  public function providerContentEntityTypeFunctionalityOptions() {
    return [
      'empty' => [
        // Option value.
        [],
        // Parent interfaces.
        [
          'Drupal\Core\Entity\ContentEntityInterface'
        ],
      ],
      'owner' => [
        ['owner'],
        [
          'Drupal\Core\Entity\ContentEntityInterface',
          'Drupal\user\EntityOwnerInterface',
        ],
        // Additional entity keys.
        [
          'uid' => 'uid',
          'owner' => 'uid',
        ],
        // Additional traits.
        [
          'Drupal\user\EntityOwnerTrait',
        ],
        // Additional base fields.
        [],
        // Additional base field helper calls.
        [
          'ownerBaseFieldDefinitions'
        ],
        // Additional methods the entity class should have.
        [],
      ],
      'changed' => [
        ['changed'],
        [
          'Drupal\Core\Entity\ContentEntityInterface',
          'Drupal\Core\Entity\EntityChangedInterface',
        ],
        // Additional entity keys.
        [],
        // Additional traits.
        [
          'Drupal\Core\Entity\EntityChangedTrait',
        ],
        // Additional base fields.
        [
          'changed' => 'changed',
        ],
      ],
      'published' => [
        ['published'],
        [
          'Drupal\Core\Entity\ContentEntityInterface',
          'Drupal\Core\Entity\EntityPublishedInterface',
        ],
        // Additional entity keys.
        [
          "published" => "status",
        ],
        // Additional traits.
        [
          'Drupal\Core\Entity\EntityPublishedTrait',
        ],
        // Additional base fields.
        [],
        // Additional base field helper calls.
        [
          'publishedBaseFieldDefinitions',
        ],
        // Additional methods the entity class should have.
        [],
      ],
      'owner + changed' => [
        ['owner', 'changed'],
        [
          'Drupal\Core\Entity\ContentEntityInterface',
          'Drupal\user\EntityOwnerInterface',
          'Drupal\Core\Entity\EntityChangedInterface',
        ],
        // Additional entity keys.
        [
          'uid' => 'uid',
          'owner' => 'uid',
        ],
        // Additional traits.
        [
          'Drupal\Core\Entity\EntityChangedTrait',
          'Drupal\user\EntityOwnerTrait',
        ],
        // Additional base fields.
        [
          'changed' => 'changed',
        ],
        // Additional base field helper calls.
        [
          'ownerBaseFieldDefinitions'
        ],
        // Additional methods the entity class should have.
        [],
      ],
    ];
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
          'functionality' => [
            'fieldable',
            'translatable',
          ],
          'admin_permission' => TRUE,
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

    $this->assertFiles([
      'test_module.info.yml',
      'src/Entity/KittyCat.php',
      'src/Entity/KittyCatInterface.php',
      'test_module.permissions.yml',
    ], $files);

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $php_tester = new PHPTester($entity_class_file);

    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\KittyCat');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\ContentEntityBase');
    $php_tester->assertHasMethods(['baseFieldDefinitions']);

    // Test the field definitions.
    $base_fields_definitions_tester = $php_tester->getBaseFieldDefinitionsTester();

    $base_fields_definitions_tester->assertFieldNames([
      'title',
      'breed',
      'colour',
    ]);

    $base_fields_definitions_tester->assertFieldType('string', 'title');
    $base_fields_definitions_tester->assertFieldType('string', 'breed');
    $base_fields_definitions_tester->assertFieldType('string', 'colour');

    $base_fields_definitions_tester->assertFieldDefinitionMethodCalls([
      "setLabel",
      "setRequired",
      'setTranslatable',
      'setSetting',
      'setDisplayOptions',
      'setDisplayConfigurable',
      'setDisplayConfigurable',
    ], 'title');
    $base_fields_definitions_tester->assertFieldDefinitionMethodCalls([
      "setLabel",
      "setDescription",
      "setTranslatable",
    ], 'breed');
    $base_fields_definitions_tester->assertFieldDefinitionMethodCalls([
      "setLabel",
      "setDescription",
      "setTranslatable",
    ], 'colour');

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
    $annotation_tester->assertHasProperties([
      'id',
      'label',
      'uuid',
      'langcode',
    ], 'entity_keys');
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
          'functionality' => [
            'fieldable',
            'revisionable',
          ],
          'admin_permission' => TRUE,
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

    $this->assertFiles([
      'test_module.info.yml',
      'src/Entity/KittyCat.php',
      'src/Entity/KittyCatInterface.php',
      'test_module.permissions.yml',
    ], $files);

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $php_tester = new PHPTester($entity_class_file);

    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\KittyCat');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\ContentEntityBase');
    $php_tester->assertHasMethods(['baseFieldDefinitions']);

    // Test field definitions.
    $base_fields_definitions_tester = $php_tester->getBaseFieldDefinitionsTester();

    $base_fields_definitions_tester->assertFieldNames([
      'title',
      'breed',
      'colour',
    ]);

    $base_fields_definitions_tester->assertFieldType('string', 'title');
    $base_fields_definitions_tester->assertFieldType('string', 'breed');
    $base_fields_definitions_tester->assertFieldType('string', 'colour');

    $base_fields_definitions_tester->assertFieldDefinitionMethodCalls([
      "setLabel",
      "setRequired",
      'setRevisionable',
      'setSetting',
      'setDisplayOptions',
      'setDisplayConfigurable',
      'setDisplayConfigurable',
    ], 'title');
    $base_fields_definitions_tester->assertFieldDefinitionMethodCalls([
      "setLabel",
      "setDescription",
      "setRevisionable",
    ], 'breed');
    $base_fields_definitions_tester->assertFieldDefinitionMethodCalls([
      "setLabel",
      "setDescription",
      "setRevisionable",
    ], 'colour');

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
    $annotation_tester->assertHasProperties([
      'id',
      'label',
      'uuid',
      'revision',
    ], 'entity_keys');
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
          'functionality' => [
            'fieldable',
            'owner',
          ],
          'bundle_entity' => [
            0 => [
              // Don't specify entity_type_id, let it be derived.
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

    $this->assertFiles([
      'test_module.info.yml',
      'src/Entity/KittyCat.php',
      'src/Entity/KittyCatInterface.php',
      'src/Entity/KittyCatType.php',
      'src/Entity/KittyCatTypeInterface.php',
      'test_module.permissions.yml',
      'config/schema/test_module.schema.yml',
    ], $files);

    $entity_class_file = $files['src/Entity/KittyCat.php'];

    $php_tester = new PHPTester($entity_class_file);
    // TODO: remove this coding standards exception when the comment for core
    // issue https://www.drupal.org/project/drupal/issues/2949964 is removed
    // from content entity type generation. See
    // ContentEntityType::collectSectionBlocks().
    $php_tester->assertDrupalCodingStandards(['Drupal.Commenting.InlineComment.SpacingAfter']);
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
    ], "The content entity has the expected root annotation properties.");
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
    $annotation_tester->assertHasProperties([
      'id',
      'label',
      'uuid',
      'bundle',
      'owner',
      'uid',
    ], 'entity_keys', "The content entity has the expected entity_keys annotation properties.");
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'id'], 'kitty_cat_id');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'label'], 'title');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'uuid'], 'uuid');
    $annotation_tester->assertPropertyHasValue(['entity_keys', 'bundle'], 'type');
    $annotation_tester->assertPropertyHasValue('bundle_entity_type', 'kitty_cat_type');
    $annotation_tester->assertPropertyHasValue('field_ui_base_route', 'entity.kitty_cat_type.edit_form');

    $bundle_entity_class_file = $files['src/Entity/KittyCatType.php'];

    $php_tester = new PHPTester($bundle_entity_class_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass('Drupal\test_module\Entity\KittyCatType');
    $php_tester->assertClassHasParent('Drupal\Core\Config\Entity\ConfigEntityBundleBase');
    $php_tester->assertClassHasInterfaces(['Drupal\test_module\Entity\KittyCatTypeInterface']);
    $php_tester->assertHasNoMethods();

    // Test the bundle entity annotation.
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
      'admin_permission',
      'bundle_of',
      'entity_keys',
      'config_export',
    ]);
    $annotation_tester->assertPropertyHasValue('bundle_of', "kitty_cat");

    $config_yaml_file = $files['config/schema/test_module.schema.yml'];
    $yaml_tester = new YamlTester($config_yaml_file);
    $yaml_tester->assertHasProperty('test_module.kitty_cat_type.*');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat_type.*', 'type'], 'config_entity');
    $yaml_tester->assertPropertyHasValue(['test_module.kitty_cat_type.*', 'label'], 'Kitty Cat Type');
    $yaml_tester->assertHasProperty(['test_module.kitty_cat_type.*', 'mapping', 'foo']);
    $yaml_tester->assertHasProperty(['test_module.kitty_cat_type.*', 'mapping', 'colour']);

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
    $handler_filenames = array_values(preg_grep('@^src/(Entity/Handler|Form)@', array_keys($files)));
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
            'default' => 'Drupal\test_module\Form\KittyCatForm',
          ],
          'list_builder' => 'Drupal\Core\Entity\EntityListBuilder',
        ],
        [
          'src/Form/KittyCatForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
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
            'default' => 'Drupal\test_module\Form\KittyCatForm',
          ],
        ],
        [
          'src/Form/KittyCatForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
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
            'default' => 'Drupal\test_module\Form\KittyCatForm',
            'add' => 'Drupal\test_module\Form\KittyCatForm',
          ],
        ],
        [
          'src/Form/KittyCatForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
        ],
      ],
      'custom add form overriding default form set to empty' => [
        [
          'handler_form_add' => 'custom',
        ],
        [
          'form' => [
            'default' => 'Drupal\Core\Entity\ContentEntityForm',
            'add' => 'Drupal\test_module\Form\KittyCatAddForm',
          ],
        ],
        [
          'src/Form/KittyCatAddForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
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
            'add' => 'Drupal\test_module\Form\KittyCatAddForm',
          ],
        ],
        [
          'src/Form/KittyCatAddForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
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
            'add' => 'Drupal\test_module\Form\KittyCatAddForm',
          ],
        ],
        [
          'src/Form/KittyCatAddForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
        ],
      ],
      'custom add form with default form set to custom' => [
        [
          'handler_form_default' => 'custom',
          'handler_form_add' => 'custom',
        ],
        [
          'form' => [
            'default' => 'Drupal\test_module\Form\KittyCatForm',
            'add' => 'Drupal\test_module\Form\KittyCatAddForm',
          ],
        ],
        [
          'src/Form/KittyCatForm.php' => 'Drupal\Core\Entity\ContentEntityForm',
          'src/Form/KittyCatAddForm.php' => 'Drupal\test_module\Form\KittyCatForm',
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
          'handler_form_default' => 'custom',
          'handler_views_data' => 'custom',
          'handler_translation' => TRUE,
          'handler_route_provider' => 'custom',
        ],
      ],
      'readme' => FALSE,
    );

    $files = $this->generateModuleFiles($module_data);

    $this->assertArrayHasKey("$module_name.permissions.yml", $files, "The admin permission property was overridden.");

    $handler_filenames = preg_grep('@^src/(Entity/Handler|Form)@', array_keys($files));
    $this->assertCount(9, $handler_filenames, "Expected number of handler files is returned.");

    $this->assertArrayHasKey("src/Entity/Handler/KittyCatStorage.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatStorageSchema.php", $files, "The files list has a storage schema class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatAccess.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatViewBuilder.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatListBuilder.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatViewsData.php", $files, "The files list has an list builder class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatTranslation.php", $files, "The files list has a translation class file.");
    $this->assertArrayHasKey("src/Entity/Handler/KittyCatRouteProvider.php", $files, "The files list has a route provider class file.");
    $this->assertArrayHasKey("src/Form/KittyCatForm.php", $files, "The files list has a form class file.");

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
    $php_tester->assertHasMethods(['buildHeader', 'buildRow']);

    // TODO: add some more precise assertions for these.
    $header_builder_tester = $php_tester->getMethodTester('buildHeader');
    $header_builder_tester->assertHasLine("\$header['label'] = \$this->t('Label');");
    $header_builder_tester->assertHasLine("return \$header + parent::buildHeader();");

    $row_builder_tester = $php_tester->getMethodTester('buildRow');
    $row_builder_tester->assertHasLine("\$row['label']['data'] = [");
    $row_builder_tester->assertHasLine("return \$row + parent::buildRow(\$entity);");

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

    $form_class_file = $files["src/Form/KittyCatForm.php"];

    $php_tester = new PHPTester($form_class_file);
    // The form class has overridden methods that only call the parent for
    // developers to start working with, so ignore the sniff for this.
    $php_tester->assertDrupalCodingStandards(['Generic.CodeAnalysis.UselessOverridingMethod.Found']);
    $php_tester->assertHasClass('Drupal\test_module\Form\KittyCatForm');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\ContentEntityForm');
    $php_tester->assertClassDocBlockHasLine("Provides the default form handler for the Kitty Cat entity.");
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
          'functionality' => [],
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
    $annotation_tester->assertPropertyHasValue(['handlers', 'form', 'default'], 'Drupal\test_module\Form\KittyCatForm');
    $annotation_tester->assertPropertyHasValue(['handlers', 'form', 'delete'], "Drupal\Core\Entity\ContentEntityDeleteForm");
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

    // Check the action links file.
    $action_links_file = $files["test_module.links.action.yml"];

    $yaml_tester = new YamlTester($action_links_file);
    $yaml_tester->assertHasProperty('entity.kitty_cat.add', 'The content entity type has an add action link.');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.add', 'title'], 'Add Kitty Cat');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.add', 'route_name'], 'entity.kitty_cat.add_form', "The route for adding a content entity is for the add form.");
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.add', 'appears_on'], ['entity.kitty_cat.collection']);

    // Check the content entity form file.
    $entity_form_file = $files['src/Form/KittyCatForm.php'];

    $php_tester = new PHPTester($entity_form_file);
    // We override formSubmit() empty so it's there for the developer to add to,
    // so disable the sniff for empty overrides.
    $php_tester->assertDrupalCodingStandards(['Generic.CodeAnalysis.UselessOverridingMethod.Found']);
    $php_tester->assertHasClass('Drupal\test_module\Form\KittyCatForm');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\ContentEntityForm');
    $php_tester->assertHasMethods(['form', 'submitForm', 'save']);

    // Check the form elements in the bundle entity's form handler.
    // TODO: allow call to parent in the form builder.
    // $form_builder_tester = $php_tester->getMethodTester('form')->getFormBuilderTester();
    // $form_builder_tester->assertElementCount(0);

    $save_method_tester = $php_tester->getMethodTester('save');
    $save_method_tester->assertHasLine('$form_state->setRedirectUrl($this->entity->toUrl(\'canonical\'));');
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
          'functionality' => [],
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

    $this->assertFiles([
      'test_module.info.yml',
      'src/Entity/KittyCat.php',
      'src/Entity/KittyCatInterface.php',
      'src/Entity/KittyCatType.php',
      'src/Entity/KittyCatTypeInterface.php',
      'src/Form/KittyCatForm.php',
      'src/Form/KittyCatTypeForm.php',
      'src/Entity/Handler/KittyCatListBuilder.php',
      'src/Entity/Handler/KittyCatTypeListBuilder.php',
      'test_module.permissions.yml',
      'config/schema/test_module.schema.yml',
      'test_module.links.menu.yml',
      'test_module.links.task.yml',
      'test_module.links.action.yml',
    ], $files);

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
    $annotation_tester->assertPropertyHasValue('bundle_label', "Kitty Cat Type");
    $annotation_tester->assertPropertyHasValue('bundle_entity_type', "kitty_cat_type");

    $bundle_entity_class_file = $files['src/Entity/KittyCatType.php'];

    $php_tester = new PHPTester($bundle_entity_class_file);
    $php_tester->assertClassHasParent('Drupal\Core\Config\Entity\ConfigEntityBundleBase');

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
      'bundle_of',
      'entity_keys',
      'config_export',
      'links',
    ]);
    $annotation_tester->assertPropertyHasValue('bundle_of', "kitty_cat");

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

    // Check the action links file.
    $action_links_file = $files["test_module.links.action.yml"];

    $yaml_tester = new YamlTester($action_links_file);
    $yaml_tester->assertHasProperty('entity.kitty_cat_type.add', 'The bundle entity type has an add action link.');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat_type.add', 'title'], 'Add Kitty Cat Type');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat_type.add', 'route_name'], 'entity.kitty_cat_type.add_form');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat_type.add', 'appears_on'], ['entity.kitty_cat_type.collection']);
    $yaml_tester->assertHasProperty('entity.kitty_cat.add', 'The content entity type has an add action link.');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.add', 'title'], 'Add Kitty Cat');
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.add', 'route_name'], 'entity.kitty_cat.add_page', "The route for adding a content entity is for the add page, rather than the add form.");
    $yaml_tester->assertPropertyHasValue(['entity.kitty_cat.add', 'appears_on'], ['entity.kitty_cat.collection']);

    // Check the content entity form file.
    $entity_form_file = $files['src/Form/KittyCatForm.php'];

    $php_tester = new PHPTester($entity_form_file);
    // We override formSubmit() empty so it's there for the developer to add to,
    // so disable the sniff for empty overrides.
    $php_tester->assertDrupalCodingStandards(['Generic.CodeAnalysis.UselessOverridingMethod.Found']);
    $php_tester->assertHasClass('Drupal\test_module\Form\KittyCatForm');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\ContentEntityForm');
    $php_tester->assertHasMethods(['form', 'submitForm', 'save']);

    $form_builder_tester = $php_tester->getMethodTester('form')->getFormBuilderTester();
    $form_builder_tester->assertElementCount(0);

    $save_method_tester = $php_tester->getMethodTester('save');
    $save_method_tester->assertHasLine('$form_state->setRedirectUrl($this->entity->toUrl(\'canonical\'));');

    // Check the bundle entity form file.
    $entity_type_form_file = $files['src/Form/KittyCatTypeForm.php'];

    $php_tester = new PHPTester($entity_type_form_file);
    // We override formSubmit() empty so it's there for the developer to add to,
    // so disable the sniff for empty overrides.
    $php_tester->assertDrupalCodingStandards(['Generic.CodeAnalysis.UselessOverridingMethod.Found']);
    $php_tester->assertHasClass('Drupal\test_module\Form\KittyCatTypeForm');
    $php_tester->assertClassHasParent('Drupal\Core\Entity\BundleEntityFormBase');
    $php_tester->assertHasMethods(['form', 'submitForm']);

    // Check the form elements in the bundle entity's form handler.
    $form_builder_tester = $php_tester->getMethodTester('form')->getFormBuilderTester();
    $form_builder_tester->assertElementCount(2);
    $form_builder_tester->assertAllElementsHaveDefaultValue();
    $form_builder_tester->assertElementType('id', 'machine_name');
    $form_builder_tester->assertElementType('label', 'textfield');

    $save_method_tester = $php_tester->getMethodTester('save');
    $save_method_tester->assertHasLine('$form_state->setRedirectUrl($this->entity->toUrl(\'collection\'));');
  }

}
