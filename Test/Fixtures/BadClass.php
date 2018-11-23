<?php

namespace DrupalCodeBuilder\Test\Fixtures;

/**
 * Badly-formed class for testing CodeAnalyser.
 *
 * This class will deliberately crash PHP with a fatal error as soon as this
 * file is loaded.
 */
class BadClass implements BadClassInterface {

  /**
   * Implement foo() with the wrong parameters, to cause a crash.
   */
  function foo($wrong) {

  }

}

/**
 * Interface for BadClass.
 */
interface BadClassInterface {

  function foo();

}
