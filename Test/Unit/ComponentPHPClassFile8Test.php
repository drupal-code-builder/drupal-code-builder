<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Generator\PHPClassFile;
use DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory;
use MutableTypedData\Definition\DefaultDefinition;

/**
 * Tests the PHP Class File generator class.
 */
class ComponentPHPClassFile8Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 8;

  /**
   * Tests the various class name and namespace properties work together.
   *
   * These need to be settable in different orders and have the lazy defaults
   * work.
   */
  public function testClassNameInterdependentProperties() {
    $definition = PHPClassFile::getPropertyDefinition();
    $definition->setName('root');

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);

    $data->root_component_name->value = '%module';

    // Set the plain class name, relying on the relative namespace to be empty.
    $data->plain_class_name->value = 'MyClass';

    $this->assertEquals('MyClass', $data->relative_class_name->value);
    $this->assertEquals(['MyClass'], $data->relative_class_name_pieces->value);
    $this->assertEquals(['Drupal', '%module', 'MyClass'], $data->qualified_class_name_pieces->value);
    $this->assertEquals('Drupal\%module\MyClass', $data->qualified_class_name->value);

    // $value = $data->relative_class_name_pieces->value;
    // dump("dumping relative_class_name_pieces :::");
    // dump($value);

    // Now add a relative namespace literal default.
    $definition = PHPClassFile::getPropertyDefinition();
    $definition->getProperty('relative_namespace')
      ->setDefault(DefaultDefinition::create()
        ->setLiteral('Plugin\views')
    );

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);

    $data->root_component_name->value = '%module';

    // Set the plain class name, relying on the relative namespace to be empty.
    $data->plain_class_name->value = 'MyClass';

    $this->assertEquals('Plugin\views\MyClass', $data->relative_class_name->value);
    $this->assertEquals(['Plugin', 'views', 'MyClass'], $data->relative_class_name_pieces->value);
    $this->assertEquals(['Drupal', '%module', 'Plugin', 'views', 'MyClass'], $data->qualified_class_name_pieces->value);
    $this->assertEquals('Drupal\%module\Plugin\views\MyClass', $data->qualified_class_name->value);

    // Set the relative class name.
    $definition = PHPClassFile::getPropertyDefinition();

    $data = DrupalCodeBuilderDataItemFactory::createFromDefinition($definition);

    $data->root_component_name->value = '%module';

    $data->relative_class_name->value = 'Name\Space\MyClass';
    $this->assertEquals('Name\Space\MyClass', $data->relative_class_name->value);
    $this->assertEquals(['Name', 'Space', 'MyClass'], $data->relative_class_name_pieces->value);
    $this->assertEquals(['Drupal', '%module', 'Name', 'Space', 'MyClass'], $data->qualified_class_name_pieces->value);
    $this->assertEquals('Drupal\%module\Name\Space\MyClass', $data->qualified_class_name->value);
  }

}
