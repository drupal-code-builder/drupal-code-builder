<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests the config entity type generator class.
 */
class ComponentConfigEntityType8Test extends TestBaseComponentGeneration {

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

    $this->assertWellFormedPHP($entity_class_file);
    $this->assertDrupalCodingStandards($entity_class_file);
    $this->assertNoTrailingWhitespace($entity_class_file);
    $this->assertClassFileFormatting($entity_class_file);

    $this->parseCode($entity_class_file);
    $this->assertHasClass('Drupal\test_module\Entity\KittyCat');
    $this->assertClassHasParent('Drupal\Core\Config\Entity\ConfigEntityBase');
    $this->assertClassHasProtectedProperty('breed', 'string', '');
    $this->assertClassHasProtectedProperty('colour', 'string', '');

    // TODO: the annotation assertion doens't handle arrays or nested
    // annotations.
    //$this->assertClassAnnotation('ContentEntityType', [], $entity_class_file);

    $entity_interface_file = $files['src/Entity/KittyCatInterface.php'];

    $this->assertWellFormedPHP($entity_interface_file);
    $this->assertDrupalCodingStandards($entity_interface_file);
    $this->assertNoTrailingWhitespace($entity_interface_file);

    $this->parseCode($entity_interface_file);
    $this->assertHasInterface('Drupal\test_module\Entity\KittyCatInterface');

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

}
