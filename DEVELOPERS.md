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
need to specify:
- the root generator to use, such as 'module', 'theme', 'profile'. This is the
  name of a subclass of DrupalCodeBuilder\Generator\RootComponent.
- the component data. This is a \MutableTypedData\Data\DataItem object, which
  can be obtained by calling getRootComponentData().

This is done as follows:

```
  // Get the generator task.
  $task = \DrupalCodeBuilder\Factory::getTask('Generate', 'module');
  // Get the initial component data object.
  $component_data = $task->getRootComponentData();
  // Iterate over the component data to present options to the user.
  foreach ($component_data as $property_name => $child_data) {
    // Get label and description.
    $child_data->getLabel();
    $child_data->getDescription();
    // Get type.
    $child_data->getType();
    // Etc.
    // Set value.
    $child_data->value = 'foo_bar';
  }
  // Get the files of generated code.
  // This is an array of objects implementing
  // \DrupalCodeBuilder\File\CodeFileInterface.
  $files = $task->generateComponent($component_data);
```

The $files array contains \DrupalCodeBuilder\File\CodeFileInterface objects for
the component files, keyed by the filenames. This can then be output as desired.

## Merging with existing code

If the module to be generated already exists on disk, some of the existing code
files can be merged with the generated code (depending on the type of file; this
is not yet supported for all files.)

To do this, get a DrupalExtension object for the existing module, and pass it to
the Generate task:

```
$analyse_extension_task = \DrupalCodeBuilder\Factory::getTask('AnalyseExtension');
$existing_extension = $analyse_extension_task->createExtension('module', $existing_module_path);

$files = $task->generateComponent($component_data, [], NULL, $existing_extension);
```

## Configuration

Different components can have configuration for how the code is generated. This
is held in a \MutableTypedData\Data\DataItem object, obtained like this:

```
$configuration_task = \DrupalCodeBuilder\Factory::getTask('Configuration');
$config_data = $configuration_task->getConfigurationData('module');
```

The config object can be used in the same way as the component data. To use
the config, pass it in when generating the code:

```
$files = $task->generateComponent($component_data, [], $config_data);
```
