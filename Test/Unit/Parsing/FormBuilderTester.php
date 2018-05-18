<?php

namespace DrupalCodeBuilder\Test\Unit\Parsing;

use PHPUnit\Framework\Assert;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\Variable;

/**
 * Helper class for testing a FormAPI form builder method.
 *
 * Get this from the PHPTester with:
 * @code
 * $method_tester = $php_tester->getMethodTester($method_name)->getFormBuilderTester();
 * @endcode
 */
class FormBuilderTester extends PHPMethodTester {

  /**
   * The array of parsed data about the form elements.
   *
   * @var array
   */
  protected $formElements = [];

  /**
   * Construct a new FormBuilderTester.
   *
   * @param \PhpParser\Node\Stmt\ClassMethod $method_node
   *   The PhpParser method node.
   */
  public function __construct(ClassMethod $method_node) {
    $this->methodNode = $method_node;
    $this->methodName = $method_node->name;

    // TODO: assert the form builder has the right parameters.

    $this->assertStatementIsParentCallAssignment(0, 'form', "The form builder's first statement is a call to the parent.");

    $this->assertReturnsVariable('form', "The form builder returns the completed form.");

    // Get the form element statements.
    $statements = $this->methodNode->getStmts();
    // We know the first statement is the parent call, and the last is the
    // return.
    $form_element_statements = array_slice($statements, 1, -1);

    // Analyse each statement to build up information about the form element
    // it represents.
    foreach ($form_element_statements as $index => $statement) {
      $form_element_data = [
        'index' => $index,
      ];

      // The statement sets a value in the $form array.
      Assert::assertEquals(\PhpParser\Node\Expr\Assign::class, get_class($statement));
      Assert::assertEquals(\PhpParser\Node\Expr\ArrayDimFetch::class, get_class($statement->var));
      Assert::assertEquals(\PhpParser\Node\Expr\Variable::class, get_class($statement->var->var));
      Assert::assertEquals('form', $statement->var->var->name);

      // Get the key in the form array that is set.
      Assert::assertEquals(\PhpParser\Node\Scalar\String_::class, get_class($statement->var->dim));
      $form_element_name = $statement->var->dim->value;
      $form_element_data['name'] = $form_element_name;

      // Analyse the element array.
      Assert::assertEquals(\PhpParser\Node\Expr\Array_::class, get_class($statement->expr));
      $array_node = $statement->expr;
      foreach ($array_node->items as $array_item_node) {
        if ($array_item_node->key->value == '#type') {
          Assert::assertEquals(\PhpParser\Node\Scalar\String_::class, get_class($array_item_node->value));

          $form_element_data['type'] = $array_item_node->value->value;

          continue;
        }

        $form_element_data['attributes'][$array_item_node->key->value] = TRUE;

        $this->formElements[$form_element_name] = $form_element_data;
      }

      // Check some basic things about each form element.
      foreach ($this->formElements as $form_element_name => $form_element) {
        Assert::ArrayHasKey('type', $form_element, "The form element {$form_element_name} has a type set.");
        Assert::ArrayHasKey('#title', $form_element['attributes'], "The form element {$form_element_name} has a title.");
        Assert::ArrayHasKey('#description', $form_element['attributes'], "The form element {$form_element_name} has a description.");
      }
    }
  }

  /**
   * Asserts the number of elements in the form.
   *
   * @var int $expected_count
   *   The expected number of elements.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertElementCount($expected_count, $message = NULL) {
    $message = $message ?? "The form builder has {$expected_count} elements.";

    Assert::assertCount($expected_count, $this->formElements);
  }

  /**
   * Asserts that all the form elements set a default value.
   */
  public function assertAllElementsHaveDefaultValue() {
    foreach ($this->formElements as $form_element_name => $form_element) {
      Assert::ArrayHasKey('#default_value', $form_element['attributes'], "The form element {$form_element_name} has a default value.");
    }
  }

  /**
   * Asserts the form has an element of the given name.
   *
   * @param string $element_name
   *   The element name.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertHasElementName($element_name, $message = NULL) {
    $message = $message ?? "The form contains an element {$element_name}.";

    Assert::assertArrayHasKey($element_name, $this->formElements, $message);
  }

  /**
   * Asserts a specified form element's type.
   *
   * @param string $element_name
   *   The element name.
   * @param string $type
   *   The expected type.
   * @param string $message
   *   (optional) The assertion message.
   */
  public function assertElementType($element_name, $type, $message = NULL) {
    $message = $message ?? "The form's '{$element_name}' element is of type '{$type}'.";

    $this->assertHasElementName($element_name);

    Assert::assertEquals($type, $this->formElements[$element_name]['type'], $message);
  }

}
