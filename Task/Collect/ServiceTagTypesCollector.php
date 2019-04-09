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
      // Sloppy array structure...
      $collector_type = $tag_infos['collector_type'];
      unset($tag_infos['collector_type']);

      $service_definition = $container_builder->getDefinition($service_name);
      $service_class = $service_definition->getClass();

      // A single service collector service can collect on more than one tag.
      foreach ($tag_infos as $tag_info) {
        $tag = $tag_info['tag'];

        if ($collector_type == 'service_id_collector') {
          // Service ID collectors don't give us anything but the service ID,
          // so nothing can be detected about the interface collected services
          // should use.
          // The only example of a service collector using this tag
          // in core is 'theme.negotiator', which is self-consuming: it expects
          // its tagged services to implement its own interface. So all we can
          // do is assume other implementations will do the same.
          $service_class_reflection = new \ReflectionClass($service_class);

          $label = CaseString::pascal($service_class_reflection->getShortName())->title();

          // Hope there's only one interface...
          $service_interfaces = $service_class_reflection->getInterfaceNames();
          $collected_services_interface = array_shift($service_interfaces);

          $interface_methods = $this->methodCollector->collectMethods($collected_services_interface);
        }
        else {
          // Service collectors tell us what the container should call on the
          // collector to add a tagged service, and so from that we can deduce
          // the interface that tagged services must implement.
          if (!isset($tag_info['call'])) {
            // Shouldn't normally happen, but protected against badly-declated
            // services.
            continue;
          }

          $collecting_method = $tag_info['call'];

          $collecting_methodR = new \ReflectionMethod($service_class, $collecting_method);
          $collecting_method_paramR = $collecting_methodR->getParameters();

          // TODO: skip if more than 1 param.
          // getNumberOfParameters

          $collected_services_interface = (string) $collecting_method_paramR[0]->getType();

          if (!interface_exists($collected_services_interface)) {
            // Shouldn't happen, as the typehint will be an interface the
            // collected services must implement.
            continue;
          }

          // Make a label from the interface name: take the short interface name,
          // and remove an 'Interface' suffix, convert to title case.
          $interface_pieces = explode('\\', $collected_services_interface);
          $label = array_pop($interface_pieces);
          $label = preg_replace('@Interface$@', '', $label);
          $label = CaseString::pascal($label)->title();

          $interface_methods = $this->methodCollector->collectMethods($collected_services_interface);
        }

        $data[$tag] = [
          'label' => $label,
          'collector_type' => $collector_type,
          'interface' => $collected_services_interface,
          'methods' => $interface_methods,
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
   *     "collector_type" => "service_collector"
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
    // Also, there are TWO possible tags for collectors, and the lazy version,
    // 'service_id_collector', doesn't specify any data such as the 'call'
    // property.
    $service_collectors_info = $container_builder->findTaggedServiceIds('service_collector');
    $service_id_collectors_info = $container_builder->findTaggedServiceIds('service_id_collector');

    array_walk($service_collectors_info, function(&$item) {
      $item['collector_type'] = 'service_collector';
    });
    array_walk($service_id_collectors_info, function(&$item) {
      $item['collector_type'] = 'service_id_collector';
    });

    // We're going to assume that there is no collecting service that uses BOTH
    // systems to collect two tags, that would be crazy.
    $collectors_info = $service_collectors_info + $service_id_collectors_info;

    // Filter for testing sample data collection.
    if (!empty($this->environment->sample_data_write)) {
      $collectors_info = array_intersect_key($collectors_info, $this->testingServiceCollectorNames);
    }

    return $collectors_info;
  }

}
