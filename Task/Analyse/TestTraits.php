<?php

namespace DrupalCodeBuilder\Task\Analyse;

use MutableTypedData\Definition\OptionSetDefininitionInterface;
use DrupalCodeBuilder\Environment\EnvironmentInterface;
use DrupalCodeBuilder\Task\Collect\CollectorBase;
use DrupalCodeBuilder\Task\Report\SectionReportInterface;
use DrupalCodeBuilder\Task\SectionReportSimpleCountTrait;
use DrupalCodeBuilder\Definition\OptionDefinition;

/**
 * Task helper for analysing and reporting on traits intended for use in tests.
 *
 * TODO: Experimental hybrid collector/reporter task. If this seems nice DX,
 * move and merge the other tasks to this namespace.
 */
class TestTraits extends CollectorBase implements SectionReportInterface, OptionSetDefininitionInterface {
  use SectionReportSimpleCountTrait;

  /**
   * {@inheritdoc}
   */
  protected $saveDataKey = 'test_creation_traits';

  /**
   * {@inheritdoc}
   */
  protected $reportingString = 'test creation traits';

  /**
   * {@inheritdoc}
   */
  protected $testingIds = [
    '\\Drupal\\Tests\\user\\Traits\\UserCreationTrait',
    '\\Drupal\\Tests\\block\\Traits\\BlockCreationTrait',
  ];

  /**
   * Cached data.
   *
   * @var array
   */
  protected $data;

  /**
   * The environment object.
   *
   * @var \DrupalCodeBuilder\Environment\EnvironmentInterface
   */
  protected $environment;

  /**
   * Constructs a new helper.
   *
   * @param \DrupalCodeBuilder\Environment\EnvironmentInterface $environment
   *   The environment object.
   */
  public function __construct(
    EnvironmentInterface $environment,
  ) {
    $this->environment = $environment;
  }

  /**
   * {@inheritdoc}
   */
  public function getJobList() {
    // No point splitting this up into jobs.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function collect($job_list) {
    $finder = new \Symfony\Component\Finder\Finder();
    $finder
      ->in($this->environment->getRoot())
      // Find test traits both in core components and modules.
      ->path(['Tests', 'tests/src'])
      ->name('*CreationTrait.php')
      ->ignoreUnreadableDirs();

    $traits = [];
    foreach ($finder->getIterator() as $file) {
      $relative_pathname = $file->getRelativePathname();
      $classname = '';

      // TODO: use DIRECTORY_SEPARATOR for people on Windows :/
      if (str_starts_with($relative_pathname, 'core/tests/Drupal/')) {
        // TODO: naively assumes no core component traits are in a subfolder!
        $classname = '\\Drupal\\Tests\\' . $file->getFilenameWithoutExtension();
      }
      elseif (str_starts_with($relative_pathname, 'core/modules')) {
        $matches = [];
        preg_match('@core/modules/(?P<module>\w+)/tests/src/(?P<namespace>.+).php@', $relative_pathname, $matches);

        $classname = "\\Drupal\\Tests\\{$matches['module']}\\" . str_replace(DIRECTORY_SEPARATOR, '\\', $matches['namespace']);
      }
      elseif (str_starts_with($relative_pathname, 'modules') && str_contains($relative_pathname, '/tests/src/')) {
        $matches = [];
        // Don't anchor the regex at the front, as could be a submodule, or
        // in /custom or /contrib or top-level.
        preg_match('@(?P<module>\w+)/tests/src/(?P<namespace>.+).php@', $relative_pathname, $matches);

        $classname = "\\Drupal\\Tests\\{$matches['module']}\\" . str_replace(DIRECTORY_SEPARATOR, '\\', $matches['namespace']);
      }
      elseif (str_starts_with($relative_pathname, 'modules')) {
        // Babysit modules that put test traits in the main /src instead of
        // /test/src.
        $matches = [];
        // Don't anchor the regex at the front, as could be a submodule, or
        // in /custom or /contrib or top-level.
        preg_match('@(?P<module>\w+)/src/(?P<namespace>.+).php@', $relative_pathname, $matches);

        $classname = "\\Drupal\\{$matches['module']}\\" . str_replace(DIRECTORY_SEPARATOR, '\\', $matches['namespace']);
      }
      // TODO: Module in a profile.

      $short_trait_name = $file->getFilenameWithoutExtension();

      // Temporary workaround.
      // See https://github.com/drupal-code-builder/drupal-code-builder/issues/420.
      if ($short_trait_name == 'BlockContentCreationTrait') {
        continue;
      }

      // Files in test folders aren't in the regular Composer autoloader, so
      // include the file so we can use reflection on the class.
      include_once($relative_pathname);

      $class_reflection = new \ReflectionClass($classname);
      $docblock = $class_reflection->getDocComment();

      // TODO: move this code to a common helper and merge with that in
      // MethodCollector.
      // Allow for crappy docs whose first line spans multiple lines even though
      // it shouldn't.
      $text = preg_replace("@\s*\n \* @", ' ', $docblock);
      $matches = [];
      preg_match("@[^\w]*(?P<first_line>.+)\n@", $text, $matches);

      $traits[$classname] = [
        'label' => $short_trait_name,
        'full_name' => $classname,
        'description' => $matches['first_line'],
      ];
    }

    return $traits;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return [
      'key' => 'test_creation_traits',
      'label' => 'Test creation traits',
      'weight' => 40,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataSummary(): array {
    // TODO: move this to a trait.
    if (!isset($this->data)) {
      $this->data = $this->environment->getStorage()->retrieve($this->getInfo()['key']);
    }

    $list = [];
    foreach ($this->data as $id => $item) {
      $list[$id] = $item['label'];
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(): array {
    if (!isset($this->data)) {
      $this->data = $this->environment->getStorage()->retrieve($this->getInfo()['key']);
    }

    $options = [];
    foreach ($this->data as $id => $item) {
      $options[$id] = OptionDefinition::create($id, $item['label'], $item['description']);
    }

    return $options;
  }

}
