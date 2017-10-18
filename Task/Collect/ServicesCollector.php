<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;

/**
 * Task helper for collecting data on services.
 */
class ServicesCollector {

  /**
   * The names of services to collect for testing sample data.
   */
  protected $testingServiceNames = [
    'current_user' => TRUE,
    'entity.manager' => TRUE,
  ];

  /**
   * Constructs a new helper.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment object.
   */
  public function __construct(
    EnvironmentInterface $environment
  ) {
    $this->environment = $environment;
  }

  /**
   * Get definitions of services from the static container.
   *
   * We collect an incomplete list of services, namely, those which have special
   * methods in the \Drupal static container. This is because (AFAIK) these are
   * the only ones for which we can detect the interface and a description.
   */
  public function collect() {
    // We can get service IDs from the container,
    $static_container_reflection = new \ReflectionClass('\Drupal');
    $filename = $static_container_reflection->getFileName();
    $source = file($filename);

    $methods = $static_container_reflection->getMethods();
    $service_definitions = [];
    foreach ($methods as $method) {
      $name = $method->getName();

      // Skip any which have parameters: the service getter methods have no
      // parameters.
      if ($method->getNumberOfParameters() > 0) {
        continue;
      }

      $start_line = $method->getStartLine();
      $end_line = $method->getEndLine();

      // Skip any which have more than 2 lines: the service getter methods have
      // only 1 line of code.
      if ($end_line - $start_line > 2) {
        continue;
      }

      // Get the single code line.
      $code_line = $source[$start_line];

      // Extract the service ID from the call to getContainer().
      $matches = [];
      $code_line_regex = "@return static::getContainer\(\)->get\('([\w.]+)'\);@";
      if (!preg_match($code_line_regex, $code_line, $matches)) {
        continue;
      }
      $service_id = $matches[1];

      $docblock = $method->getDocComment();

      // Extract the interface for the service from the docblock @return.
      $matches = [];
      preg_match("[@return (.+)]", $docblock, $matches);
      $interface = $matches[1];

      // Extract a description from the docblock first line.
      $docblock_lines = explode("\n", $docblock);
      $doc_first_line = $docblock_lines[1];

      $matches = [];
      preg_match("@(the (.*))\.@", $doc_first_line, $matches);
      $description = ucfirst($matches[1]);
      $label = ucfirst($matches[2]);

      $service_definition = [
        'id' => $service_id,
        'label' => $label,
        'static_method' => $name,
        'interface' => $interface,
        'description' => $description,
      ];
      $service_definitions[$service_id] = $service_definition;
    }

    // Sort by ID.
    ksort($service_definitions);

    // Filter for testing sample data collection.
    if (!empty($this->environment->sample_data_write)) {
      $service_definitions = array_intersect_key($service_definitions, $this->testingServiceNames);
    }

    return $service_definitions;
  }

}
