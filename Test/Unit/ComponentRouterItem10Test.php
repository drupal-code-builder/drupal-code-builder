<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Test\Unit\Parsing\PHPTester;
use DrupalCodeBuilder\Test\Unit\Parsing\YamlTester;

/**
 * Tests for Router item component.
 *
 * @group yaml
 */
class ComponentRouterItem10Test extends TestBase {

  /**
   * The Drupal core major version to set up for this test.
   *
   * @var int
   */
  protected $drupalMajorVersion = 10;

  /**
   * Test generating a module with routes.
   *
   * Covers the different access types.
   */
  public function testBasicRouteGeneration() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'router_items' => [
        [
          'path' => '/my/path',
          'controller' => [
            'controller_type' => 'controller',
          ],
          'access' => [
            'access_type' => 'permission',
          ],
        ],
        [
          'path' => '/my/other-path',
          'title' => 'My Other Page',
          'controller' => [
            'controller_type' => 'controller',
          ],
          'access' => [
            'access_type' => 'role',
          ],
        ],
        [
          'path' => '/my/{parameter}/path',
          'title' => 'My Parameter Page',
          'controller' => [
            'controller_type' => 'controller',
          ],
          'access' => [
            'access_type' => 'entity_access',
            'entity_type_id' => 'node',
            'entity_access_operation' => 'update',
          ],
        ],
        [
          'path' => '/my/path.with.dots-and-dashes_and_underscores',
          'title' => 'My Parameter Page',
          'controller' => [
            'controller_type' => 'controller',
          ],
          'access' => [
            'access_type' => 'entity_access',
            'entity_type_id' => 'node',
            'entity_access_operation' => 'update',
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "$module_name.routing.yml",
      "src/Controller/MyPathController.php",
      "src/Controller/MyOtherPathController.php",
      "src/Controller/MyParameterPathController.php",
      'src/Controller/MyPathWithDotsAndDashesAndUnderscoresController.php',
    ], $files);

    $routing_file = $files["$module_name.routing.yml"];
    $yaml_tester = new YamlTester($routing_file);

    $yaml_tester->assertHasProperty('test_module.my.path', "The routing file has the property for the route.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'path'], '/my/path', "The routing file declares the route path.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'defaults', '_controller'], '\Drupal\test_module\Controller\MyPathController::content', "The routing file declares the route controller.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'defaults', '_title'], 'myPage', "The routing file declares the route title.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'requirements', '_permission'], 'access content', "The routing file declares the route permission.");

    $yaml_tester->assertHasProperty('test_module.my.other_path', "The routing file has the property for the route.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.other_path', 'path'], '/my/other-path', "The routing file declares the route path.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.other_path', 'defaults', '_controller'], '\Drupal\test_module\Controller\MyOtherPathController::content', "The routing file declares the route controller.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.other_path', 'defaults', '_title'], 'My Other Page', "The routing file declares the route title.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.other_path', 'requirements', '_role'], 'authenticated', "The routing file declares the route role.");

    $yaml_tester->assertHasProperty('test_module.my.parameter.path', "The routing file has the property for the route.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.parameter.path', 'path'], '/my/{parameter}/path', "The routing file declares the route path.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.parameter.path', 'defaults', '_controller'], '\Drupal\test_module\Controller\MyParameterPathController::content', "The routing file declares the route controller.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.parameter.path', 'defaults', '_title'], 'My Parameter Page', "The routing file declares the route title.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.parameter.path', 'requirements', '_entity_access'], 'node.update', "The routing file declares the route entity access.");

    $controller_file = $files["src/Controller/MyPathController.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $controller_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass("Drupal\\{$module_name}\Controller\MyPathController");

    $method_tester = $php_tester->getMethodTester('content');
    $method_tester->assertMethodHasDocblockLine('Callback for the test_module.my.path route.');

    $controller_file = $files['src/Controller/MyPathWithDotsAndDashesAndUnderscoresController.php'];
    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $controller_file);
    // The first line of the callback's docblock will be too long because of the
    // long route name.
    $php_tester->assertDrupalCodingStandards(['Drupal.Commenting.DocComment.ShortSingleLine']);
    $php_tester->assertHasClass("Drupal\\{$module_name}\Controller\MyPathWithDotsAndDashesAndUnderscoresController");
  }

  /**
   * Tests the different controller types.
   */
  public function testRouteControllerTypes() {
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'router_items' => [
        0 => [
          'path' => '/my/path/controller',
          'controller' => [
            'controller_type' => 'controller',
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
        1 => [
          'path' => '/my/path/form',
          'controller' => [
            'controller_type' => 'form',
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
        2 => [
          'path' => '/my/path/entity-view',
          'controller' => [
            'controller_type' => 'entity_view',
            'entity_type_id' => 'node',
            'entity_view_mode' => 'teaser'
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
        3 => [
          'path' => '/my/path/entity-form',
          'controller' => [
            'controller_type' => 'entity_form',
            'entity_type_id' => 'node',
            'entity_form_mode' => 'edit'
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
        4 => [
          'path' => '/my/path/entity-list',
          'controller' => [
            'controller_type' => 'entity_list',
            'entity_type_id' => 'node',
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "$module_name.routing.yml",
      "src/Controller/MyPathControllerController.php",
    ], $files);

    $routing_file = $files["$module_name.routing.yml"];
    $yaml_tester = new YamlTester($routing_file);

    $yaml_tester->assertPropertyHasValue(['test_module.my.path.controller', 'defaults', '_controller'], '\Drupal\test_module\Controller\MyPathControllerController::content');
    $yaml_tester->assertPropertyHasValue(['test_module.my.path.form', 'defaults', '_form'], '\Drupal\module\Form\FormClassName');
    $yaml_tester->assertPropertyHasValue(['test_module.my.path.entity_view', 'defaults', '_entity_view'], 'node.teaser');
    $yaml_tester->assertPropertyHasValue(['test_module.my.path.entity_form', 'defaults', '_entity_form'], 'node.edit');
    $yaml_tester->assertPropertyHasValue(['test_module.my.path.entity_list', 'defaults', '_entity_list'], 'node');
  }

  /**
   * Tests the different access types.
   *
   * @param array $access
   *   The array of values for the 'access' property in the module data.
   * @param string $yaml_property
   *   The name of the YAML property to expect in the routing file.
   * @param string $yaml_value
   *   The expected value of the $yaml_property.
   * @param array $controller
   *   (optional) An array of values for the 'controller' property in the
   *   module data.
   * @param array $class_names_and_methods
   *   (optional) An array of data about expected classes and their methods.
   *   Keys are the relative class names. Values are arrays of method names.
   *
   * @dataProvider dataRouteAccessTypes
   */
  public function testRouteAccessTypes(
    array $access,
    string $yaml_property,
    string $yaml_value,
    array $controller = [],
    array $class_names_and_methods = []
  ) {
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'router_items' => [
        0 => [
          'path' => '/my/path/controller',
          // By default, don't request a controller.
          'controller' => $controller ?: [
            'controller_type' => 'entity_view',
            'entity_type_id' => 'node',
            'entity_view_mode' => 'default',
          ],
          'access' => $access,
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $expected_files = [
      "test_module.info.yml",
      "test_module.routing.yml",
    ];
    $expected_methods = [];

    foreach ($class_names_and_methods as $relative_class_name => $class_methods) {
      $filename = 'src/' . str_replace('\\', '/', $relative_class_name) . '.php';

      $expected_files[] = $filename;
      $expected_methods[$filename] = $class_methods;
    }

    $this->assertFiles($expected_files, $files);

    $routing_file = $files["test_module.routing.yml"];

    $yaml_tester = new YamlTester($routing_file);

    $yaml_tester->assertPropertyHasValue(['test_module.my.path.controller', 'requirements', $yaml_property], $yaml_value);

    foreach ($expected_methods as $filename => $class_methods) {
      $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $files[$filename]);
      foreach ($class_methods as $method_name) {
        $php_tester->assertHasMethod($method_name);

        $method_tester = $php_tester->getMethodTester($method_name);
        $method_tester->assertMethodHasDocblockLine(match ($method_name) {
          'content' => 'Callback for the test_module.my.path.controller route.',
          'access' => 'Checks access for the test_module.my.path.controller route.',
        });
      }
    }
  }

  /**
   * Data provider for testRouteAccessTypes().
   */
  public function dataRouteAccessTypes() {
    return [
      'none' => [
        // The value for the 'access' property in the component data.
        [
          'access_type' => 'access',
        ],
        // The YAML key to test for.
        '_access',
        // The expect value for the YAML key.
        'TRUE',
      ],
      'permission' => [
        [
          'access_type' => 'permission',
          'routing_value' => 'my permission'
        ],
        '_permission',
        'my permission',
      ],
      'role' => [
        [
          'access_type' => 'role',
          'routing_value' => 'my role'
        ],
        '_role',
        'my role',
      ],
      'entity_access' => [
        [
          'access_type' => 'entity_access',
          'entity_type_id' => 'node',
          'entity_access_operation' => 'view',
        ],
        '_entity_access',
        'node.view',
      ],
      // Access in the controller, but without requesting a controller class.
      // Weird case but can happen.
      'custom_access-controller-without-content' => [
        [
          'access_type' => 'custom_access',
          'custom_access_callback' => [
            'callback_location' => 'controller',
          ],
        ],
        '_custom_access',
        '\Drupal\test_module\Controller\MyPathControllerController::access',
        [
          'controller_type' => 'controller',
        ],
        [
          'Controller\MyPathControllerController' => [
            'content',
            'access',
          ],
        ],
      ],
      // Access in the controller, and also requesting a controller class.
      'custom_access-controller-with-content' => [
        [
          'access_type' => 'custom_access',
          'custom_access_callback' => [
            'callback_location' => 'controller',
          ],
        ],
        '_custom_access',
        '\Drupal\test_module\Controller\MyPathControllerController::access',
        [],
        [
          'Controller\MyPathControllerController' => [
            'access',
          ],
        ],
      ],
      'custom_access-custom' => [
        [
          'access_type' => 'custom_access',
          'custom_access_callback' => [
            'callback_location' => 'custom',
            'routing_value' => 'Access\RouteCustomAccess'
          ],
        ],
        '_custom_access',
        '\Drupal\test_module\Access\RouteCustomAccess::access',
        [],
        [
          'Access\RouteCustomAccess' => [
            'access',
          ],
        ],
      ],
      'custom_access-existing' => [
        [
          'access_type' => 'custom_access',
          'custom_access_callback' => [
            'callback_location' => 'existing',
            'routing_value' => '\Drupal\other_module\Access\ExistingCustomAccess::access'
          ],
        ],
        '_custom_access',
        '\Drupal\other_module\Access\ExistingCustomAccess::access',
      ],
    ];
  }

  /**
   * Test options for the controller class
   */
  public function testRouteControllerClass() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'router_items' => [
        [
          'path' => '/my/path/no-base',
          'controller' => [
            'controller_type' => 'controller',
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
        [
          'path' => '/my/path/controller-base',
          'title' => 'My Controller Base Page',
          'controller' => [
            'controller_type' => 'controller',
            'use_base' => TRUE,
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
        [
          'path' => '/my/path/string-translation',
          'title' => 'My Controller Base Page',
          'controller' => [
            'controller_type' => 'controller',
            'import_stringtranslation' => TRUE,
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "$module_name.routing.yml",
      "src/Controller/MyPathNoBaseController.php",
      "src/Controller/MyPathControllerBaseController.php",
      "src/Controller/MyPathStringTranslationController.php",
    ], $files);

    $controller_file = $files["src/Controller/MyPathNoBaseController.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $controller_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass("Drupal\\{$module_name}\Controller\MyPathNoBaseController");
    $php_tester->assertClassHasNoParent();
    $php_tester->assertHasMethod('content');
    $php_tester->assertClassHasTraits([]);

    $controller_file = $files["src/Controller/MyPathControllerBaseController.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $controller_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass("Drupal\\{$module_name}\Controller\MyPathControllerBaseController");
    $php_tester->assertClassHasParent('Drupal\Core\Controller\ControllerBase');
    $php_tester->assertHasMethod('content');
    $php_tester->assertClassHasTraits([]);

    $controller_file = $files["src/Controller/MyPathStringTranslationController.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $controller_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass("Drupal\\{$module_name}\Controller\MyPathStringTranslationController");
    $php_tester->assertClassHasNoParent();
    $php_tester->assertHasMethod('content');
    $php_tester->assertClassHasTraits([
      'Drupal\Core\StringTranslation\StringTranslationTrait',
    ]);
  }

  /**
   * Test options for the controller class
   *
   * @group di
   */
  public function testRouteControllerClassWithDI() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'router_items' => [
        [
          'path' => '/my/path/injection',
          'title' => 'My Controller Base Page',
          'controller' => [
            'controller_type' => 'controller',
            'injected_services' => [
              'current_user',
              'entity_type.manager',
            ],
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "$module_name.routing.yml",
      "src/Controller/MyPathInjectionController.php",
    ], $files);

    $controller_file = $files["src/Controller/MyPathInjectionController.php"];

    $php_tester = PHPTester::fromCodeFile($this->drupalMajorVersion, $controller_file);
    $php_tester->assertDrupalCodingStandards();
    $php_tester->assertHasClass("Drupal\\{$module_name}\Controller\MyPathInjectionController");
    $php_tester->assertClassHasNoParent();
    $php_tester->assertClassHasInterfaces(['Drupal\Core\DependencyInjection\ContainerInjectionInterface']);
    $php_tester->assertHasMethodOrder(['create', '__construct', 'content']);

    // Check service injection.
    $php_tester->assertInjectedServicesWithFactory([
      [
        'typehint' => 'Drupal\Core\Session\AccountProxyInterface',
        'service_name' => 'current_user',
        'property_name' => 'currentUser',
        'parameter_name' => 'current_user',
      ],
      [
        'typehint' => 'Drupal\Core\Entity\EntityTypeManagerInterface',
        'service_name' => 'entity_type.manager',
        'property_name' => 'entityTypeManager',
        'parameter_name' => 'entity_type_manager',
      ],
    ]);
  }

  /**
   * Tests specifying the form class.
   */
  public function testRouteForm() {
    $module_data = [
      'base' => 'module',
      'root_name' => 'test_module',
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'router_items' => [
        0 => [
          'path' => '/my/path/existing-form',
          'controller' => [
            'controller_type' => 'form',
            'form_class' => '\Drupal\other_module\Form\ExistingForm',
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
        1 => [
          'path' => '/my/path/generated-form',
          'controller' => [
            'controller_type' => 'form',
            'form_class' => '!1',
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
      ],
      'forms' => [
        0 => [
          'plain_class_name' => 'GeneratedForm',
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      'test_module.info.yml',
      'test_module.routing.yml',
      'src/Form/GeneratedForm.php',
    ], $files);

    $routing_file = $files['test_module.routing.yml'];
    $yaml_tester = new YamlTester($routing_file);

    $yaml_tester->assertPropertyHasValue(['test_module.my.path.existing_form', 'defaults', '_form'], '\Drupal\other_module\Form\ExistingForm');
    $yaml_tester->assertPropertyHasValue(['test_module.my.path.generated_form', 'defaults', '_form'], '\Drupal\test_module\Form\GeneratedForm');
  }

  /**
   * Test generating a route with a menu link.
   */
  public function testRouteGenerationWithMenuLink() {
    // Assemble module data.
    $module_name = 'test_module';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'router_items' => [
        0 => [
          'path' => '/my/path',
          'controller' => [
            'controller_type' => 'controller',
          ],
          'access' => [
            'access_type' => 'permission',
          ],
          'title' => 'My Page',
          'menu_link' => [
            'title' => 'My link',
          ],
        ],
        1 => [
          'path' => '/my/other-path',
          'controller' => [
            'controller_type' => 'controller',
          ],
          'access' => [
            'access_type' => 'permission',
          ],
        ],
      ],
      'readme' => FALSE,
    ];

    $files = $this->generateModuleFiles($module_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "$module_name.routing.yml",
      "src/Controller/MyPathController.php",
      "src/Controller/MyOtherPathController.php",
      "$module_name.links.menu.yml",
    ], $files);

    $this->assertCount(5, $files, "The expected number of files is returned.");

    $this->assertArrayHasKey("$module_name.info.yml", $files, "The files list has a .info file.");
    $this->assertArrayHasKey("$module_name.routing.yml", $files, "The files list has a routing file.");
    $this->assertArrayHasKey("src/Controller/MyPathController.php", $files, "The files list has a controller class file.");
    $this->assertArrayHasKey("src/Controller/MyOtherPathController.php", $files, "The files list has a controller class file.");
    $this->assertArrayHasKey("$module_name.links.menu.yml", $files, "The files list has a menu links file.");

    $menu_links_file = $files["$module_name.links.menu.yml"];
    $yaml_tester = new YamlTester($menu_links_file);

    $yaml_tester->assertHasProperty('test_module.my.path', "The menu links file has the property for the menu link.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'title'], 'My link', "The menu links file declares the link title.");
    $yaml_tester->assertPropertyHasValue(['test_module.my.path', 'route_name'], 'test_module.my.path', "The menu links file declares the link route.");
  }

  /**
   * Tests adoption of existing form.
   *
   * @group adopt
   */
  public function testExistingRouterItemAdoption() {
    // First pass: generate the files we'll mock as existing.
    $module_name = 'existing';
    $module_data = [
      'base' => 'module',
      'root_name' => $module_name,
      'readable_name' => 'Test Module',
      'short_description' => 'Test Module description',
      'module_package' => 'Test Package',
      'readme' => FALSE,
      'router_items' => [
        [
          'path' => '/path/existing',
          'title' => 'Existing Base Page',
          'controller' => [
            'controller_type' => 'controller',
            'injected_services' => [
              'current_user',
              'entity_type.manager',
            ],
          ],
          'access' => [
            'access_type' => 'access',
          ],
        ],
      ],
    ];

    $existing_files = $this->generateModuleFiles($module_data);
    $this->assertFiles([
      "$module_name.info.yml",
      "$module_name.routing.yml",
      "src/Controller/PathExistingController.php",
    ], $existing_files);

    $extension = $this->getMockedExtension('module', $existing_files);

    // Now create the component data, and adopt the router item.
    unset($module_data['router_items']);
    $component_data = $this->getRootComponentBlankData('module');
    $component_data->set($module_data);

    $task_handler_adopt = \DrupalCodeBuilder\Factory::getTask('Adopt');
    $adoptable_items = $task_handler_adopt->listAdoptableComponents($component_data, $extension);

    $this->assertArrayHasKey('module:router_items', $adoptable_items);
    $this->assertArrayHasKey('existing.path.existing', $adoptable_items['module:router_items']);

    $task_handler_adopt->adoptComponent($component_data, $extension, 'module:router_items', 'existing.path.existing');

    // Don't pass in the existing extension, to check the adopted form is
    // getting generated from scratch.
    $files = $this->generateComponentFilesFromData($component_data);

    $this->assertFiles([
      "$module_name.info.yml",
      "$module_name.routing.yml",
      "src/Controller/PathExistingController.php",
    ], $files);

    $routing_file = $files["$module_name.routing.yml"];
    $yaml_tester = new YamlTester($routing_file);

    $yaml_tester->assertPropertyHasValue(['path.existing', 'defaults', '_controller'], '\Drupal\\existing\Controller\PathExistingController::content');
  }

}
