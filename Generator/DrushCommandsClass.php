<?php

namespace DrupalCodeBuilder\Generator;


/**
 * Generator for a class holding Drus commands.
 */
class DrushCommandsClass extends PHPClassFileWithInjection {

  /**
   * {@inheritdoc}
   */
  protected $hasStaticFactoryMethod = TRUE;

  /**
   * {@inheritdoc}
   */
  protected string $containerInterface = '\\Psr\\Container\\ContainerInterface';

}
