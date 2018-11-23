<?php

namespace DrupalCodeBuilder\Test\Fixtures;

/**
 * Class that uses PHP7 syntax.
 *
 * This will cause the CodeAnalyser to fail if the current system has an
 * outdated version of PHP that runs with proc_open() -- as is often the case on
 * OS X.
 */
class Php7Class {

  function foo(string $string): array {
    return [$string];
  }

}
