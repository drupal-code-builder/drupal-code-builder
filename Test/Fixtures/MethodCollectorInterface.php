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

}
