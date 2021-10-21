<?php

namespace DrupalCodeBuilder\Test\Unit;

use MutableTypedData\Definition\DataDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Tests the validator classes.
 */
class UnitValidatorTest extends TestCase {

  public function providerClassNameValidator() {
    return [
      'one-part name' => [
        'Name',
        TRUE,
      ],
      'two-part name' => [
        'ClassName',
        TRUE,
      ],
      'three-part name' => [
        'AnotherClassName',
        TRUE,
      ],
      'caps in name' => [
        'NameWithACRONYMInside',
        FALSE,
      ]
    ];
  }

  /**
   * Tests the class name validator.
   *
   * @dataProvider providerClassNameValidator
   */
  public function testClassNameValidator($value, $expected_pass) {
    $validator = new \DrupalCodeBuilder\MutableTypedData\Validator\ClassName();

    $data = \DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory::createFromDefinition(
      DataDefinition::create('string')
    );

    $data->value = $value;

    $result = $validator->validate($data);

    $this->assertEquals($expected_pass, $result);
  }

  /**
   * Data provider for testMachineNameValidator().
   */
  public function providerMachineNameValidator() {
    return [
      'one-part name' => [
        'name',
        TRUE,
      ],
      'two-part name' => [
        'machine_name',
        TRUE,
      ],
      'three-part name' => [
        'another_machine_name',
        TRUE,
      ],
      'title case' => [
        'Title_case',
        FALSE,
      ],
      'spaces' => [
        'has spaces',
        FALSE,
      ],
      'numbers' => [
        'machine_name_1',
        TRUE,
      ],
    ];
  }

  /**
   * Tests the machine name validator.
   *
   * @dataProvider providerMachineNameValidator
   */
  public function testMachineNameValidator($value, $expected_pass) {
    $validator = new \DrupalCodeBuilder\MutableTypedData\Validator\MachineName();

    $data = \DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory::createFromDefinition(
      DataDefinition::create('string')
    );

    $data->value = $value;

    $result = $validator->validate($data);

    $this->assertEquals($expected_pass, $result);
  }

  /**
   * Tests the path validator.
   */
  public function testPathValidator() {
    $validator = new \DrupalCodeBuilder\MutableTypedData\Validator\Path();

    $data = \DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory::createFromDefinition(
      DataDefinition::create('string')
    );

    $data->value = 'no_slash/at/start';

    $result = $validator->validate($data);

    $this->assertEquals(FALSE, $result);

    $data->value = '/has/slash';

    $result = $validator->validate($data);

    $this->assertEquals(TRUE, $result);
  }

  /**
   * Data provider for testPluginNameValidator().
   */
  public function providerPluginNameValidator() {
    return [
      'one-part name' => [
        'plugin',
        TRUE,
      ],
      'two-part name' => [
        'plugin_name',
        TRUE,
      ],
      'three-part name' => [
        'another_plugin_name',
        TRUE,
      ],
      'title case' => [
        'Title_case',
        FALSE,
      ],
      'spaces' => [
        'has spaces',
        FALSE,
      ],
      'numbers' => [
        'plugin_name_1',
        TRUE,
      ],
      'initial number' => [
        '1_plugin_name_1',
        FALSE,
      ],
      'initial_colon' => [
        ':plugin_name:suffix',
        FALSE,
      ],
    ];
  }

  /**
   * Tests the plugin name validator.
   *
   * @dataProvider providerPluginNameValidator
   */
  public function testPluginNameValidator($value, $expected_pass) {
    $validator = new \DrupalCodeBuilder\MutableTypedData\Validator\PluginName();

    $data = \DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory::createFromDefinition(
      DataDefinition::create('string')
    );

    $data->value = $value;

    $result = $validator->validate($data);

    $this->assertEquals($expected_pass, $result);
  }

  /**
   * Data provider for testPluginNameValidator().
   */
  public function providerYamlPluginNameValidator() {
    return [
      'one-part name' => [
        'plugin',
        TRUE,
      ],
      'two-part name' => [
        'plugin_name',
        TRUE,
      ],
      'three-part name' => [
        'another_plugin_name',
        TRUE,
      ],
      'title case' => [
        'Title_case',
        FALSE,
      ],
      'spaces' => [
        'has spaces',
        FALSE,
      ],
      'numbers' => [
        'plugin_name_1',
        TRUE,
      ],
      'periods' => [
        'plugin_name.1',
        TRUE,
      ],
      'initial number' => [
        '1_plugin_name_1',
        FALSE,
      ],
      'initial_colon' => [
        ':plugin_name:suffix',
        FALSE,
      ],
      'initial_period' => [
        '.plugin_name.1',
        FALSE,
      ],
    ];
  }

  /**
   * Tests the YAML plugin name validator.
   *
   * @dataProvider providerYamlPluginNameValidator
   */
  public function testYamlPluginNameValidator($value, $expected_pass) {
    $validator = new \DrupalCodeBuilder\MutableTypedData\Validator\YamlPluginName();

    $data = \DrupalCodeBuilder\MutableTypedData\DrupalCodeBuilderDataItemFactory::createFromDefinition(
      DataDefinition::create('string')
    );

    $data->value = $value;

    $result = $validator->validate($data);

    $this->assertEquals($expected_pass, $result);
  }

}
