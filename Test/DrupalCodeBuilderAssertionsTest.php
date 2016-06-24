<?php

/**
 * @file
 * Contains DrupalCodeBuilderAssertionsTest.
 */

namespace DrupalCodeBuilder\Test;

/**
 * Tests the custom assertions in our base tests class.
 *
 * Run with:
 * @code
 *   vendor/phpunit/phpunit/phpunit Test/DrupalCodeBuilderAssertionsTest.php
 * @endcode
 *
 * @see http://stackoverflow.com/questions/12412601/phpunit-writing-tests-for-custom-assertions
 */
class DrupalCodeBuilderAssertionsTest extends TestBase {

  /**
   * Tests the assertNoTrailingWhitespace() assertion.
   *
   * @dataProvider providerAssertNoTrailingWhitespace
   *
   * @param $code
   *  The code to test with the assertion.
   * @param $pass
   *  Whether the assertion is expected to pass (TRUE) or fail (FALSE).
   */
  public function testAssertNoTrailingWhitespace($code, $pass) {
    try {
      $this->assertNoTrailingWhitespace($code);
      // Assertion passed.
      if (!$pass) {
        self::fail("assertNoTrailingWhitespace() should fail for '$code'");
      }
    }
    catch (\PHPUnit_Framework_ExpectationFailedException $e) {
      // Assertion failed.
      if ($pass) {
        self::fail("assertNoTrailingWhitespace() should pass for '$code'");
      }
    }
  }

  /**
   * Data provider for testAssertNoTrailingWhitespace().
   */
  public function providerAssertNoTrailingWhitespace() {
    return array(
      array("", TRUE),
      array("code();", TRUE),
      array("code(); ", FALSE),
      array("code();\n \ncode();", FALSE),
      array("code();\n\ncode();", TRUE),
    );
  }

  /**
   * Tests the assertFunctionParameter() assertion.
   *
   * @dataProvider providerAssertFunctionParameter
   *
   * @param $code
   *  The code to test with the assertion.
   * @param $pass
   *  Whether the assertion is expected to pass (TRUE) or fail (FALSE).
   */
  public function testAssertFunctionParameter($code, $pass) {
    try {
      $this->assertFunctionParameter($code);
      // Assertion passed.
      if (!$pass) {
        self::fail("assertFunctionParameter() should fail for '$code'");
      }
    }
    catch (\PHPUnit_Framework_ExpectationFailedException $e) {
      // Assertion failed.
      if ($pass) {
        throw $e;
        self::fail("assertFunctionParameter() should pass for '$code'");
      }
    }
  }

  /**
   * Data provider for testAssertFunctionParameter().
   */
  public function providerAssertFunctionParameter() {
    return array(
      array('$foo', TRUE),
      array('&$foo', TRUE),
      array('array $foo', TRUE),
      array('Class $foo', TRUE),
      array('\\Qualified\\Class $foo', TRUE),
      array('$foo = NULL', TRUE),
      array('$foo = \'\'', TRUE),
      array('cake', FALSE),
      array('cake = badvalue', FALSE),
      array('Bad Class $foo', FALSE),
    );
  }

  /**
   * Tests the assertFunction() assertion.
   *
   * @dataProvider providerAssertFunction
   *
   * @param $code
   *  The code to test with the assertion.
   * @param $pass
   *  Whether the assertion is expected to pass (TRUE) or fail (FALSE).
   */
  public function testAssertFunction($code, $pass) {
    try {
      $this->assertFunction('do_the_thing', $code);
      // Assertion passed.
      if (!$pass) {
        self::fail("assertFunction() should fail for '$code'");
      }
    }
    catch (\PHPUnit_Framework_ExpectationFailedException $e) {
      // Assertion failed.
      if ($pass) {
        throw $e;
        self::fail("assertFunction() should pass for '$code'");
      }
    }
  }

  /**
   * Data provider for testAssertFunction().
   */
  public function providerAssertFunction() {
    $data = [];

    $data[0] = [
      'function do_the_thing() {}',
      TRUE,
    ];
    $data[1] = [
      'function do_the_other_thing() {}',
      FALSE,
    ];
    $data[2] = [
      <<<'EOT'
function do_the_other_thing() {

}

function do_the_thing(array $param_1, &$param_2, $param_3 = 'foo') {
  // Some code.
}

EOT
      ,
      TRUE,
    ];
    $data[3] = [
      <<<'EOT'
function do_the_other_thing() {

}

function do_the_thing(SomeClass $param_1, \Namespace\OtherClass $param_2, $param_3 = 'foo') {
  // Some code.
}

EOT
      ,
      TRUE,
    ];

    return $data;
  }

}
