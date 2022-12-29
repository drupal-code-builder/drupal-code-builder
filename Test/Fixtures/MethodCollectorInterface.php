<?php

namespace DrupalCodeBuilder\Test\Fixtures;

use DrupalCodeBuilder\Test\Fixtures\Typehint\Alpha;
use DrupalCodeBuilder\Test\Fixtures\Typehint\Beta;
use Iterator;
use Exception;

/**
 * Fixture for UnitMethodCollectorTest.
 */
interface MethodCollectorInterface {

  public function noParams();

  public function scalarParams($one, $two);

  public function qualifiedRootNamespaceParams(\DateTime $one, \Reflection $two);

  public function importedRootNamespaceParams(Iterator $one, Exception $two);

  public function namespaceParams(Alpha $one, Beta $two);

  public function scalarReturn($one): int;

  public function qualifiedRootNamespaceReturn($one): \DateTime;

  public function importedRootNamespaceReturn($one): Iterator;

  public function namespaceReturn(Alpha $one): Beta;

}
