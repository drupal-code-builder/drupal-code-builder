<?php

namespace DrupalCodeBuilder\Generator;

use MutableTypedData\Definition\PropertyListInterface;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Generator\Render\DocBlock;
use PhpParser\Comment;
use PhpParser\Node\Const_;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Const_ as ConstStmt;

/**
 * Generator for PHP constants.
 */
class PHPConstant extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    $definition->addProperties([
      // This has no validator as it's only internal and there's no validator
      // suitable yet.
      'name' => PropertyDefinition::create('string')
        ->setLabel('Constant name')
        ->setRequired(TRUE),
      'value' => PropertyDefinition::create('string')
        ->setLabel('Constant value')
        ->setRequired(TRUE),
      'type' => PropertyDefinition::create('string')
        ->setLabel('Data type')
        ->setRequired(TRUE),
      'docblock_lines' => PropertyDefinition::create('mapping')
        ->setRequired(TRUE),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType(): string {
    return 'constant';
  }

  /**
   * {@inheritdoc}
   */
  public function getContents(): array {
    $docblock = Docblock::constant();
    foreach ($this->component_data->docblock_lines->export() as $line) {
      $docblock[] = $line;
    }

    $docblock->var($this->component_data->type->value);

    // $lines = $docblock->render();

    $value = $this->component_data->value->value;

    $value_node = match(TRUE) {
      is_numeric($value) => new LNumber($value),
      default => new String_($value),
    };

    // $this->attributes = $attributes;
    // $this->consts = $consts;

    $const_node = new ConstStmt(
      consts: [
        new Const_($this->component_data->name->value, $value_node),
      ],
      attributes: [
        'comments' => [
          $docblock->toParserCommentNode(),
        ],
      ],
    );

    $printer = new \DrupalPrettyPrinter\DrupalPrettyPrinter(['html' => FALSE]);
    dump($printer->prettyPrint([$const_node]));

    $lines[] = $printer->prettyPrint([$const_node]);

    return $lines;
  }

}
