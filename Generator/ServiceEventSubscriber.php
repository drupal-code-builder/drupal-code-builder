<?php

namespace DrupalCodeBuilder\Generator;

use CaseConverter\CaseString;
use DrupalCodeBuilder\Definition\PropertyDefinition;
use DrupalCodeBuilder\Definition\PropertyListInterface;

/**
 * Generator for an event subscriber service.
 *
 * This is a separate component rather than a variant because having a single
 * variant seems faffy. Also because the service tag type can be empty, we'd
 * have to deal with an potentially empty variant type property.
 */
class ServiceEventSubscriber extends Service {

  /**
   * {@inheritdoc}
   */
  public static function addToGeneratorDefinition(PropertyListInterface $definition) {
    parent::addToGeneratorDefinition($definition);

    // Hide the service tag type. Setting a default here doesn't work (due to
    // the order of things in ComponentCollector). Removing the property doesn't
    // work either, as various methods in the parent class expect it.
    $definition->getProperty('service_tag_type')
      ->setInternal(TRUE);

    // Set these directly, since we can't set a default value on the
    // service_tag_type property.
    $definition->getProperty('tags')
      ->setLiteralDefault([
        [
          'name' => 'event_subscriber',
        ],
      ]);
    $definition->getProperty('interfaces')
      ->setLiteralDefault(['\\Symfony\\Component\\EventDispatcher\\EventSubscriberInterface']);

    $definition->getProperty('service_name')
      ->setLabel('Event subscriber service name');

    $definition->addPropertyAfter('service_name', PropertyDefinition::create('string')
      ->setName('event_names')
      ->setLabel('Event names')
      ->setDescription("The events this subscribers reacts to.")
      ->setMultiple(TRUE)
      ->setOptionsProvider(\DrupalCodeBuilder\Factory::getTask('ReportEventNames')),
    );

    $definition->getProperty('relative_namespace')
      ->setLiteralDefault('EventSubscriber');
  }

  /**
   * {@inheritdoc}
   */
  public function requiredComponents(): array {
    $components = parent::requiredComponents();

    $task_handler_report_services = \DrupalCodeBuilder\Factory::getTask('ReportServiceData');
    $service_types_data = $task_handler_report_services->listServiceTypeData();

    $components['function-getSubscribedEvents'] = $this->createFunctionComponentFromMethodData($service_types_data['event_subscriber']['methods']['getSubscribedEvents']);
    $components['function-getSubscribedEvents']['body'] = [];

    // Add a method for each event we subscribe to.
    $event_name_data = \DrupalCodeBuilder\Factory::getTask('ReportEventNames')->listEventNames();
    // Array of method names used so far (but without the 'on' prefix, for
    // simplicity).
    $method_names = [];
    foreach ($this->component_data->event_names as $event_name) {
      // We need to ensure the method names are unique, so we work back up the
      // event name from the constant name (which may not be unique) up fully-
      // qualified class name.
      // TODO: if there is a clash and a need to differentiate, then BOTH
      // methods should get prefixed, not just the one that comes second.
      $fully_qualified_constant = $event_name->value;
      $pieces = preg_split('@(\\\\|::)@', $fully_qualified_constant);
      // We'll get an empty first element because of the initial '\'.
      $pieces = array_filter($pieces);
      // Pop off the constant, leaving only the namespace.
      $short_constant = array_pop($pieces);

      // Start by taking the short constant name as the potential method name.
      // That won't be suitable if another subscribed event uses the same
      // constant name in a different class.
      $potential_method_name = CaseString::snake(strtolower($short_constant))->pascal();
      while (TRUE) {
        // Check this at the top of the loop because we've already got a
        // potential method name of just the constant name.
        if (!isset($method_names[$potential_method_name])) {
          $method_names[$potential_method_name] = TRUE;
          break;
        }

        if (empty($pieces)) {
          // We really shouldn't get here, we should have found something
          // unique by now!
          break;
        }

        $potential_method_name = array_pop($pieces) . $potential_method_name;
      };

      $method_name = 'on' . $potential_method_name;

      // Add the method in the getSubscribedEvents() method, with a comment
      // with the event name constant definition's comment.
      $body[] = '// ' . $event_name_data[$fully_qualified_constant];
      $body[] = "£events[$fully_qualified_constant] = ['$method_name'];";

      // The method itself.
      $components['function-' . $method_name] = [
        'component_type' => 'PHPFunction',
        'function_name' => $method_name,
        'function_docblock_lines' => [
          // TODO: differentiate this?
          "Reacts to the $short_constant event.",
        ],
        'prefixes' => ['public'],
        'parameters' => [
          [
            'name' => 'event',
            'typehint' => '\Drupal\Component\EventDispatcher\Event',
            'description' => 'The event. TODO: Change this to the specific event class.',
          ],
        ],
        'containing_component' => '%requester',
      ];
    }
    $body[] = 'return £events;';
    $components['function-getSubscribedEvents']['body'] = $body;

    return $components;
  }

}
