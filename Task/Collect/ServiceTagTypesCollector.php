<?php

namespace DrupalCodeBuilder\Task\Collect;

use DrupalCodeBuilder\Environment\EnvironmentInterface;
use CaseConverter\CaseString;

/**
 *  Task helper for collecting data on tagged services.
 */
class ServiceTagTypesCollector extends CollectorBase  {

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'service_tag_types';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'tagged service types';

  /**
   * The method collector helper
   */
  protected $methodCollector;

  /**
   * The container builder helper.
   */
  protected $containerBuilderGetter;

  /**
   * The names of services to collect for testing sample data.
   */
  protected $testingServiceCollectorNames = [
    'breadcrumb' => TRUE,
    // Note that event_subscriber will also be included as it's hardcoded.
  ];

  /**
   * Constructs a new helper.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment object.
   * @param ContainerBuilderGetter $method_collector
   *   The container builder helper.
   * @param MethodCollector $method_collector
   *   The method collector helper.
   */
  public function __construct(
    EnvironmentInterface $environment,
    ContainerBuilderGetter $container_builder_getter,
    MethodCollector $method_collector
  ) {
    $this->environment = $environment;
    $this->containerBuilderGetter = $container_builder_getter;
    $this->methodCollector = $method_collector;
  }

  /**
   * {@inheritdoc}
   */
  public function getJobList() {
    // No point splitting this up into jobs.
    return NULL;
  }

  /**
   * Collect data on tagged services.
   *
   * @return
   *  An array whose keys are service tags, and whose values arrays containing:
   *    - 'label': A label for the tag.
   *    - 'interface': The fully-qualified name (without leading slash) of the
   *      interface that each tagged service must implement.
   *    - 'methods': An array of the methods of this interface, in the same
   *      format as returned by MethodCollector::collectMethods().
   */
  public function collect($job_list) {
    $container_builder = $this->containerBuilderGetter->getContainerBuilder();

    $collectors_info = $this->getCollectorServiceIds($container_builder);

    $data = [];

    // Declare event subscriber services. These don't have collectors in Drupal
    // as they are native to Symfony, so we need to declare explicitly.
    $data['event_subscriber'] = [
      'label' => 'Event subscriber',
      'interface' => 'Symfony\Component\EventDispatcher\EventSubscriberInterface',
      'methods' => $this->methodCollector->collectMethods('Symfony\Component\EventDispatcher\EventSubscriberInterface'),
      // TODO: services of this type should go in the EventSubscriber namespace.
    ];

    foreach ($collectors_info as $service_name => $tag_infos) {
      // A single service collector service can collect on more than one tag.
      foreach ($tag_infos as $tag_info) {
        $tag = $tag_info['tag'];

        if (!isset($tag_info['call'])) {
          // Shouldn't normally happen, but protected against badly-declated
          // services.
          continue;
        }

        $collecting_method = $tag_info['call'];

        $service_definition = $container_builder->getDefinition($service_name);
        $service_class = $service_definition->getClass();
        $collecting_methodR = new \ReflectionMethod($service_class, $collecting_method);
        $collecting_method_paramR = $collecting_methodR->getParameters();

        // TODO: skip if more than 1 param.
        // getNumberOfParameters

        $type = (string) $collecting_method_paramR[0]->getType();

        if (!interface_exists($type)) {
          // Shouldn't happen, as the typehint will be an interface the
          // collected services must implement.
          continue;
        }

        // Make a label from the interface name: take the short interface name,
        // and remove an 'Interface' suffix, convert to title case.
        $interface_pieces = explode('\\', $type);
        $label = array_pop($interface_pieces);
        $label = preg_replace('@Interface$@', '', $label);
        $label = CaseString::pascal($label)->title();

        $type_hint_methods = $this->methodCollector->collectMethods($type);

        $data[$tag] = [
          'label' => $label,
          'interface' => $type,
          'methods' => $type_hint_methods,
        ];
      }
    }

    // Sort by ID.
    ksort($data);

    return $data;
  }

  /**
   * Gets the names of all service collector services from the container.
   *
   * @param \Symfony\Component\DependencyInjection\TaggedContainerInterface $container_builder
   *  The container.
   *
   * @return string[]
   *  An array of data about services. Structure is as follows:
   *  @code
   *   "path_processor_manager" => array:2 [
   *     0 => array:2 [
   *      "tag" => "path_processor_inbound"
   *      "call" => "addInbound"
   *    ]
   *    1 => array:2 [
   *      "tag" => "path_processor_outbound"
   *      "call" => "addOutbound"
   *    ]
   *  ]
   *  @endcode
   */
  protected function getCollectorServiceIds(\Symfony\Component\DependencyInjection\TaggedContainerInterface $container_builder) {
    // Get the details of all service collector services.
    // Note that the docs for this method are completely wrong.
    $collectors_info = $container_builder->findTaggedServiceIds('service_collector');

    // Filter for testing sample data collection.
    if (!empty($this->environment->sample_data_write)) {
      $collectors_info = array_intersect_key($collectors_info, $this->testingServiceCollectorNames);
    }

    return $collectors_info;
  }

}
