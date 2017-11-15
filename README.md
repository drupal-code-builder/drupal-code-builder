Drupal Code Builder is a system for generating scaffold code for Drupal modules (and other components).

This is a library, and has no UI of its own. It can be used in several ways:
- with the [Drupal Module Builder project](https://www.drupal.org/project/module_builder), which provides a UI within Drupal. (The Drupal Code Builder library was formerly part of the Module Builder module.)
- with the [Drush command extension](https://github.com/drupal-code-builder/drupal-code-builder-drush).

Drupal Code Builder can be used for any current version of Drupal (7, 8). Older versions are unsupported, but it should produce code for 5 and 6 also.

Tests powered by PHPUnit ensure that the generated PHP code passes PHP linting,
and adheres to Drupal Coding Standards, as enforced by PHP CodeSniffer.

## What Drupal Code Builder can do

Drupal Code Builder can generate the following for a module:
- code files, containing hook implementations
- info file (.info.yml on Drupal 8)
- README file
- PHPUnit test case classes, with presets for different types
- Simpletest test case classes
- plugin classes
- services, with presets for tagged service types
- content entity types, with bundle entity and base fields
- config entity types, with properties
- plugin types
- theme hooks

Definitions of hooks, plugin types, and tagged service types are obtained by
analyzing the current Drupal codebase of the site where the library is used.
This means that Drupal Code Builder automatically knows about all hooks and
plugin types from contrib and custom modules as well as those in Drupal core.

Furthermore, complex subcomponents can generate multiple code elements:
- an admin settings form adds form builder functions and an admin permission
- router paths add menu/router items
- permission names add the scaffold for the permission definition

## Installation

Follow the instructions given in the component that requests Drupal Coder Builder (e.g., Module Builder, Drush command).

Additionally, if [Plugin module](https://www.drupal.org/project/plugin) is present, plugin type definitions will be enhanced with its data.

## Usage

This file covers the API. For instructions on how to install this library to use with code that provides a UI, consult the documentation for the UI in question.

Using DCB can be summarized as follows:
- tell the DCB factory about your current Drupal environment
- get a Task class for what you want to do
- call one of the public methods on the Task class

For example:

```
  // Load the file for the factory class.
  // (Not necessary if using DCB via Composer.)
  include_once('Factory.php');
  // Tell DCB which environment it's being used in and the Drupal core version.
  \DrupalCodeBuilder\Factory::setEnvironmentLocalClass('Drush')
    ->setCoreVersionNumber(8);
  // Get the Task handler.
  $dcb_task_handler_report = \DrupalCodeBuilder\Factory::getTask('ReportHookData');
  // Call a method in the Task handler to perform the operation.
  $hook_declarations = $dcb_task_handler_report->getHookDeclarations();
```

The code generation system is made up of a set of Generator classes, and is operated from the \DrupalCodeBuilder\Task\Generate class. To build code, you need to specify:
- the root generator to use, such as 'module', 'theme', 'profile'. This is the name of a subclass of DrupalCodeBuilder\Generator\RootComponent.
- an array of component data. The options for this depend on the component. A specification for these is obtained by calling getRootComponentDataInfo().

This is done as follows:

```
  // Get the generator task.
  $task = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
  // Get the info about the component data. This is an array keyed by property
  // name, with the definition of each property.
  $component_data_info = $task->getRootComponentDataInfo();
  foreach ($component_data_info as $property_name => &$property_info) {
    // Prepare each property. This sets up the default value and the options.
    // This is to allow each property to use the data entered so far. For
    // example, if the user enters 'foo_bar' for the module machine name, the
    // proposed default for the module readable name will be 'Foo Bar'.
    $task->prepareComponentDataProperty($property_name, $property_info, $component_data);
  }
  // Set your values.
  $component_data['root_name'] = 'foo_bar';
  // Get the files of generated code.
  // This is an array keyed by filename, where each value is the text of the
  // file.
  $files = $task->generateComponent($component_data);
```

The $files array contains code for the component files, keyed by the filenames. This can then be output as desired.
