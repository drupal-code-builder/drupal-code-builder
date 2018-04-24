<?php

namespace DrupalCodeBuilder\Test\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;

/**
 * Unit tests for the PHPTester test helper.
 */
class ParserPHPTest extends TestCase {

  /**
   * Tests the assertClassHasInterfaces() assertion.
   *
   * @dataProvider providerAssertClassInterfaces
   */
  public function testAssertClassInterfaces($expected_interfaces, $pass) {
    $php = <<<EOT
<?php

use Some\Space\Namespaced;
use Some\Other\Space\NamespacedOther;
use Yet\Another\Space\Irrelevant;

class Foo implements Plain, Namespaced, NamespacedOther {

}

EOT;

    $php_tester = new PHPTester($php);

    try {
      $php_tester->assertClassHasInterfaces($expected_interfaces);
      // Assertion passed.
      if (!$pass) {
        $this->fail("assertClassHasInterfaces() should fail");
      }
    }
    catch (ExpectationFailedException $e) {
      // Assertion failed.
      if ($pass) {
        $this->fail("assertClassHasInterfaces() should pass.");
      }
    }
  }

  /**
   * Data provider for testAssertHasProperty().
   */
  public function providerAssertClassInterfaces() {
    return [
      /*
      // TODO: fails!
      'single plain interface' => [
        ['Plain'],
        TRUE,
      ],
      */
      'single namespaced interface' => [
        ['Some\Space\Namespaced'],
        TRUE,
      ],
      'single namespaced interface bad class' => [
        ['Some\Space\NotHere'],
        FALSE,
      ],
      'single namespaced interface bad interface' => [
        ['Some\WrongSpace\Namespaced'],
        FALSE,
      ],
      'multiple namespaced interfaces' => [
        ['Some\Space\Namespaced', 'Some\Other\Space\NamespacedOther'],
        TRUE,
      ],
    ];
  }

}
