<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Unit tests for the BaseFieldDefinitionsTester test helper.
 */
class ParserBaseFieldDefinitionsTesterTest extends TestCase {

  public function testBaseFieldFinding() {
    $code = <<<'EOT'
      <?php

      class MyEntityClass {

        /**
         * {@inheritdoc}
         */
        public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
          $fields = parent::baseFieldDefinitions($entity_type);

          $fields += static::ownerBaseFieldDefinitions($entity_type);

          $fields += static::publishedBaseFieldDefinitions($entity_type);

          // For ease of debugging BaseFieldDefinitionsTester, not a real
          // code sample!
          $fields['simple'] = BaseFieldDefinition::create('string');

          $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t("Title"))
            ->setRequired(TRUE)
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting("max_length", 255)
            ->setDisplayOptions("form", [
              'type' => "string_textfield",
              'weight' => "-5",
            ])
            ->setDisplayConfigurable("view", TRUE)
            ->setDisplayConfigurable("form", TRUE);

          $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t("Changed"))
            ->setDescription(t("The time that the entity was last edited."))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE);

          $fields['cake'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Cake'))
            ->setDescription(t('TODO: description of field.'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE);

          $fields['pants'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Pants'))
            ->setDescription(t('TODO: description of field.'))
            ->setRevisionable(TRUE)
            ->setTranslatable(TRUE);

          return $fields;
        }

      }

      EOT;

    $php_tester = new PHPTester(8, $code);
    $base_fields_tester = $php_tester->getBaseFieldDefinitionsTester();

    $base_fields_tester->assertFieldNames([
      'simple',
      'title',
      'changed',
      'cake',
      'pants',
    ]);

    // TODO: assert the static calls.
  }

}
