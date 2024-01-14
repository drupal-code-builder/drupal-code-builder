<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Definition\PropertyDefinition;

/**
 * Tests our additions to the MTD property definition.
 */
class UnitPropertyDefinitionTest extends TestCase {

  /**
   * Tests inserting properties among existing ones.
   */
  public function testInsertProperties() {
    $definition = PropertyDefinition::create('complex')
      ->setName('base')
      ->setProperties([
        'one' => PropertyDefinition::create('string'),
        'two' => PropertyDefinition::create('string'),
        'three' => PropertyDefinition::create('string'),
      ]);

    $definition->addPropertyAfter(
      'one',
      PropertyDefinition::create('string')->setName('one-a'),
      PropertyDefinition::create('string')->setName('one-b'),
    );

    $this->assertEquals(
      [
        'one',
        'one-a',
        'one-b',
        'two',
        'three',
      ],
      $definition->getPropertyNames(),
    );
  }

}
