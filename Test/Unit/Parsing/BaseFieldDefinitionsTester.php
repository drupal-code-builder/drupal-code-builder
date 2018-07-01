<?php

namespace DrupalCodeBuilder\Test\Unit\Parsing;

use PHPUnit\Framework\Assert;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\Variable;

/**
 * Helper class for testing a content entity baseFieldDefinitions() method.
 *
 * Get this from the PHPTester with:
 * @code
 * $method_tester = $php_tester->getBaseFieldDefinitionsTester();
 * @endcode
 */
class BaseFieldDefinitionsTester extends PHPMethodTester {

  /**
   * An array of field names and types.
   *
   * @var array
   */
  protected $baseFields = [];

  /**
   * An array keyed by field names whose values are arrays of the chained calls.
   *
   * @var array
   */
  protected $baseFieldMethodCalls = [];

  /**
   * An array of the names of helper methods that are called.
   *
   * @var array
   */
  protected $helperMethodCalls = [];

  /**
   * Construct a new BaseFieldDefinitionsTester.
   *
   * @param \PhpParser\Node\Stmt\ClassMethod $method_node
   *   The PhpParser method node.
   * @param \DrupalCodeBuilder\Test\Unit\Parsing\PHPTester file_tester
   *   The PHP tester for the file this method is in.
   * @param string $php_code
   *   The complete PHP code being tested.
   */
  public function __construct(ClassMethod $method_node, PHPTester $file_tester, $php_code) {
    parent::__construct($method_node, $file_tester, $php_code);

    $this->assertStatementIsParentCallAssignment(0, 'fields', "The baseFieldDefinitions() method's first statement is a call to the parent.");

    $this->assertReturnsVariable('fields', "The baseFieldDefinitions() method returns the \$fields array.");

    // Get the form element statements.
    $statements = $this->methodNode->getStmts();
    // We know the first statement is the parent call, and the last is the
    // return.
    $statements = array_slice($statements, 1, -1);

    // There should only be two kinds of statement now we've eliminated the
    // parent call and the return:
    // - static calls to a helper that merge $fields
    // - assignments of a new item to the $fields
    foreach ($statements as $statement) {
      // dump($statement);
      switch (get_class($statement)) {
        case \PhpParser\Node\Expr\AssignOp\Plus::class:
          Assert::assertEquals('fields', $statement->var->name, "The helper call merges the \$fields array.");
          Assert::assertCount(1, $statement->expr->args, "The helper call has one argument.");
          Assert::assertEquals('entity_type', $statement->expr->args[0]->value->name, "The helper call passes the \$entity_type variable.");

          $this->helperMethodCalls[] = $statement->expr->name;

          break;

        case \PhpParser\Node\Expr\Assign::class:
          // The statement sets a value in the $form array.
          Assert::assertEquals(\PhpParser\Node\Expr\Assign::class, get_class($statement), "The statement assigns a value.");
          Assert::assertEquals(\PhpParser\Node\Expr\ArrayDimFetch::class, get_class($statement->var), "The statement assigns a value to an array key.");
          Assert::assertEquals(\PhpParser\Node\Expr\Variable::class, get_class($statement->var->var));
          Assert::assertEquals('fields', $statement->var->var->name, "The statement assigns to the \$fields variable.");

          // Get the key in the $fields array that is set.
          Assert::assertEquals(\PhpParser\Node\Scalar\String_::class, get_class($statement->var->dim));
          $field_name = $statement->var->dim->value;

          // Extract a list of the chained method calls.
          $method_calls = [];
          $method_call = $statement->expr;
          while (isset($method_call->var)) {
            $method_calls[] = $method_call->name;

            $method_call = $method_call->var;
          }

          // The deepest method call in the parser tree is the first one in the
          // actual source code: check that is a call to
          // BaseFieldDefinition::create() and get the type.
          Assert::assertEquals(\PhpParser\Node\Expr\StaticCall::class, get_class($method_call), "The chained call starts with a static call");
          Assert::assertEquals('BaseFieldDefinition', $method_call->class->parts[0], "The chained call starts with BaseFieldDefinition.");
          Assert::assertEquals('create', $method_call->name, "The chained call starts with a call to create().");
          Assert::assertCount(1, $method_call->args, "The chained call starts with a call to create().");

          $field_type = $method_call->args[0]->value->value;

          // Reverse the array, as we encounter them in reverse order.
          $method_calls = array_reverse($method_calls);

          $this->baseFields[$field_name] = $field_type;
          $this->baseFieldMethodCalls[$field_name] = $method_calls;

          break;
      }
    }
  }

  /**
   * Asserts the names of the defined fields are equal to the given list.
   *
   * @param string[] $expected_names
   *   The expected field names.
   */
  public function assertFieldNames($expected_names) {
    Assert::assertEquals($expected_names, array_keys($this->baseFields), "The baseFieldDefinitions() method defines the expected fields.");
  }

  /**
   * Asserts a field's type.
   *
   * @param string $expected_type
   *   The expected type.
   * @param string $field_name
   *   The name of the field to check.
   */
  public function assertFieldType($expected_type, $field_name) {
    Assert::assertArrayHasKey($field_name, $this->baseFields, "The baseFieldDefinitions() method defines the field {$field_name}.");
    Assert::assertEquals($expected_type, $this->baseFields[$field_name], "The field {$field_name} is of type {$expected_type}.");
  }

  /**
   * Asserts the method calls for a field's definition.
   *
   * @param string $expected_type
   *   The expected type.
   * @param string $field_name
   *   The name of the field to check.
   */
  public function assertFieldDefinitionMethodCalls($expected_calls, $field_name) {
    Assert::assertArrayHasKey($field_name, $this->baseFields, "The baseFieldDefinitions() method defines the field {$field_name}.");
    Assert::assertEquals($expected_calls, $this->baseFieldMethodCalls[$field_name], "The definition for the field {$field_name} has the expected method calls.");
  }

  /**
   * Asserts the helper method calls.
   *
   * These are methods that traits provide with additional field definitions,
   * that baseFieldDefinitions() should then call to merge with its own list.
   *
   * @param string[] $expected_method_names
   *   The names of the methods that should be called.
   */
  public function assertHelperMethodCalls($expected_method_names) {
    Assert::assertEquals($expected_method_names, $this->helperMethodCalls, "The baseFieldDefinitions() method has the expected helper method calls.");
  }

}
