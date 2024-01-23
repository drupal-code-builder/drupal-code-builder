<?php

namespace DrupalCodeBuilder\Test\Unit;

/**
 * Basic test class.
 */
class BasicTheme10Test extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected $drupalMajorVersion = 10;

  /**
   * Test the component data is correctly formed.
   */
  public function testComponentData() {
    $component_data = $this->getRootComponentBlankData('theme');

    $this->assertEquals('theme', $component_data->getName());

    $theme_data = [
      'base' => 'theme',
      'root_name' => 'sparkling_ponies',
    ];

    $component_data->set($theme_data);


    $files = $this->generateComponentFilesFromData($component_data);
    dump($files);
  }

}
