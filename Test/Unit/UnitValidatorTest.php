<?php

namespace DrupalCodeBuilder\Test\Unit;

use MutableTypedData\Definition\PropertyDefinition;
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
      PropertyDefinition::create('string')
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
      PropertyDefinition::create('string')
    );

    $data->value = $value;

    $result = $validator->validate($data);

    $this->assertEquals($expected_pass, $result);
  }

}
