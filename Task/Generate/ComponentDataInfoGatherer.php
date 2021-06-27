<?php

namespace DrupalCodeBuilder\Task\Generate;

/**
 * Task helper for getting info on data properties from components.
 *
 * This takes data that a component generator class defines in
 * componentDataDefinition() and prepares it for use by UIs.
 *
 * @see BaseGenerator::componentDataDefinition()
 * @see Generate::getRootComponentDataInfo()
 */
class ComponentDataInfoGatherer {

  /**
   * The class handler helper.
   */
  protected $classHandler;

  /**
   * Creates a new ComponentDataInfoGatherer.
   *
   * @param ComponentClassHandler $class_handler
   *  The class handler helper.
   */
  public function __construct(ComponentClassHandler $class_handler) {
    $this->classHandler = $class_handler;
  }

}
