<?php

namespace DrupalCodeBuilder\Task\Collect;

use PhpParser\ParserFactory;
use PhpParser\{Node, NodeTraverser, NodeVisitorAbstract};
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitor\NameResolver;

/**
 * Task helper for collecting data on event names.
 */
class EventNamesCollector extends CollectorBase {

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'event_names';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'event_names';

  /**
   * The event names to collect for testing sample data.
   */
  protected $testingIds = [
    '\Drupal\Core\Entity\EntityTypeEvents::CREATE',
    '\Drupal\Core\Entity\EntityTypeEvents::UPDATE',
    '\Drupal\Core\Entity\EntityTypeEvents::DELETE',
    '\Drupal\Core\Config\ConfigEvents::DELETE',
  ];

  /**
   * {@inheritdoc}
   */
  public function getJobList() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function collect($job_list) {
    // Core is not consistent about a namespace for event name classes. This
    // will get some false positives!
    $files = $this->findFiles('Events.php$');
    // dsm($files);
    $parser = (new ParserFactory)->createForHostVersion();

    // Node visitor which collects class constants.
    // We can instantiate this just the once, because the keys of the $events
    // array have the fully-qualified class names.
    $visitor = new class extends NodeVisitorAbstract {
      // Array keyed by qualified event constants, whose values are the docblock
      // text or NULL if the event constant is missing documentation.
      public $events = [];

      public function enterNode(Node $node) {
        if ($node instanceof Class_) {
          $class_name = '\\' . $node->namespacedName->toString();

          foreach ($node->getConstants() as $constant_node) {
            $this->events[$class_name . '::' . $constant_node->consts[0]->name->name] = $constant_node->getDocComment()?->getReformattedText();
          }

          return NodeTraverser::STOP_TRAVERSAL;
        }
      }
    };

    foreach ($files as $file_info) {
      $code = file_get_contents($file_info['uri']);

      try {
        $stmts = $parser->parse($code);
      }
      catch (\Error $e) {
        // Bad file, skip.
        continue;
      }

      $traverser = new NodeTraverser;

      $name_resolver = new NameResolver();
      $traverser->addVisitor($name_resolver);

      $traverser->addVisitor($visitor);

      $traverser->traverse($stmts);
    }

    // Use just the first line of the docblock.
    array_walk(
      $visitor->events,
      fn (&$docblock, $name) => $docblock = $docblock
        // If there's a docblock, take the first line.
        ? $this->getDocblockFirstLine($docblock)
        // If there isn't, take the constant name.
        : explode('::', $name)[1]
    );

    return $visitor->events;
  }

}
