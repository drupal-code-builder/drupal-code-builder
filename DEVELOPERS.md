This file covers the API. For instructions on how to install this library to use
with code that provides a UI, consult the documentation for the UI in question.

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

The code generation system is made up of a set of Generator classes, and is
operated from the \DrupalCodeBuilder\Task\Generate class. To build code, you
need to specify: - the root generator to use, such as 'module', 'theme',
'profile'. This is the name of a subclass of
DrupalCodeBuilder\Generator\RootComponent. - an array of component data. The
options for this depend on the component. A specification for these is obtained
by calling getRootComponentDataInfo().

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

The $files array contains code for the component files, keyed by the filenames.
This can then be output as desired.
