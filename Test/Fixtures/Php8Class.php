<?php

namespace DrupalCodeBuilder\Test\Fixtures;

/**
 * Class that uses PHP8 syntax.
 *
 * This will cause the CodeAnalyser to fail if the current system has an
 * outdated version of PHP that runs with proc_open() -- as is often the case on
 * OS X.
 */
class Php8Class {

  function __construct(
    protected string $foo,
  ) {

  }

}
