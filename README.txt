Module Builder
==============

Welcome to Module Builder!

Module Builder is a system that simplifies the process of creating code, by
creating files complete with scaffold code that can be filled in.

For example, for generating a custom module, simply fill out the form, select
the hooks you want and the script will automatically generate a skeleton module
file for you, along with PHPDoc comments and function definitions. This saves
you the trouble of looking at api.drupal.org 50 times a day to remember what
arguments and what order different hooks use. Score one for laziness! ;)

What Module Builder can create
------------------------------

Module builder can generate the following for a module:
- code files, containing hook implementations
- info file (.info.yml on Drupal 8)
- README file
- test case classes
- plugin classes

Furthermore, complex subcomponents can generate multiple code elements:
- an admin settings form adds form builder functions and an admin permission
- router paths add menu/router items
- permission names add the scaffold for the permission definition (on D7 and,
  earlier, hook_permission(), on D8 a permissions.yml file)

Module builder can also build themes and install profiles, though these are
currently still experimental.

How Module Builder can be used
------------------------------

Module builder can be used in a variety of ways:

- as a Drush plugin, providing a Drush command
- as a Drupal module, providing an admin UI in Drupal
- as a library, which can be used by other Drupal modules via Libraries API

Module builder and Drupal core versions
---------------------------------------

Module builder can be used for any version of Drupal (5, 6, 7, 8) when used as
Drush plugin or a library.

When used as a regular Drupal module however, you need the version that matches
the core version of your Drupal.

Installing module builder
-------------------------

### Installation as a Drush plugin ###

1. Place this folder somewhere where Drush can find the command.
   Inside ~/.drush will do it; see the Drush documentation has details on other
   possible locations.
2. From a Drupal installation, do:
     $ drush mbdl
   This will download hook data to that Drupal's files/hooks folder.
   Hooks are downloaded for core and any other modules in this installation of
   Drupal that have an api.php file to document their hooks.
3. You can now generate module code. For details, do:
     $ drush help mb

If you use Drush with multiple Drupal installations, you can store your hook
data centrally. To do so, specify the --data option when both downloading hooks
and generating code. You can use Drush's drushrc.php to set this option
automatically.

The advantage of this is that you only download hooks data once for all your
sites. If your sites have different contrib modules, you can simply execute the
download command in each one: the data for different modules accumulates, even
if a particular module is absent in one site.

### Installation as a Drupal module ###

1. Place this folder into your modules/ directory like you would any
   other module.
2. Enable it from Administration > Modules.
3. At Administration › Configuration › Development › Module Builder › Settings,
   specify the path to save the hook documentation files.
4. (On Drupal 5 and 6 only) The first time you visit the module builder form,
   the module will retrieve hook documentation from git.drupal.org and store it
   locally.
   When you want to update this documentation later on, return to the
   settings page and click the "Update" button.
5. (Optional) Create custom function declaration template file(s) if you
   don't like the default output.
6. (Optional) Create your own hook groupings if you don't like the
   default ones.

Note that in this configuration, Drush will register the Module Builder Drush
plugin, and you can thus also use Drush in this installation of Drupal. However,
a consequence of this is that if you *also* have Module Builder installed with
Drush globally, in this installation of Drupal, Drush will load Module Builder
twice, which will cause problems!

### Installation as a library ###

If another module instructs to use this as a library, place this folder is
the sites/all/libraries folder.

Using Module Builder
--------------------

### Use on Drupal

1. Go to Administration › Modules › Module Builder.
   (Note: you will require 'access module builder' privileges to see this link.)
2. Enter a module name, description, and so on.
3. Select from one of the available hook groupings to automatically
   select hook choices for you, or expand the fieldsets and choose
   hooks individually.
4. Click the "Submit" button and watch your module's code generated
   before your eyes! ;)
5. Copy and paste the code into a files called <your_module>.module,
   <your_module>.info and <your_module>.install and save them to
   a <your_module> directory under one of the modules directories.
6. Start customizing it to your needs; most of the tedious work is
   already done for you! ;)

### Use on Drush

Full help is available via 'drush help mb'.

The drush command uses an interactive mode by default to request any
parameters not initially given. To disable this and use default values for
anything omitted, specify the --noi option.

It's a good idea to set up your drushrc.php with:
  $options['data'] = '/path/to/drupal_hooks/';

and if you prefer the non-interactive command line style:
  $options['noi'] = 1;

Module Builder API
------------------

Module builder is primarily a framework for generating code files, that happens
to be packaged with two UIs that access it: the Drush plugin for use on the
command line, and the Drupal module for use in the web UI.

This framework has a public API which can be used by other modules. This
consists of a number of classes in the \ModuleBuilder\Task namespace, which
provide public methods.

As well as Tasks, Module Builder consists of Environment classes, which deal
with the differences between running in different environments (e.g., as a
Drush plugin on Drupal 8 versus as a Drupal module on Drupal 7), and Generator
classes, which produce the output code.

The basic operation for Module Builder is as in this example:

    // Load the file for the factory class.
    // (Not necessary if using MB via Composer.)
    include_once('Factory.php');
    // Tell MB which environment it's being used in and the Drupal core version.
    \ModuleBuilder\Factory::setEnvironmentClass('Drush', 8);
    // Get the Task handler.
    $mb_task_handler_report = \ModuleBuilder\Factory::getTask('ReportHookData');
    // Call a method in the Task handler to perform the operation.
    $hook_declarations = $mb_task_handler_report->getHookDeclarations();

The code generation system is made up of a set of Generator classes, and is
operated from the \ModuleBuilder\Task\Generate class. To build code, you need
to specify:
  - the root generator to use, such as 'module', 'theme', 'profile'. This is
    the name of a subclass of ModuleBuilder\Generator\RootComponent.
  - an array of component data. The options for this depend on the component.

 This is done as follows:

  // Get the generator task.
  $task = \ModuleBuilder\Factory::getTask('Generate', 'module');
  // Get the info about the component data. This is an array keyed by property
  // name, with the definition of each property.
  $component_data_info = $mb_task_handler_generate->getRootComponentDataInfo();
  foreach ($component_data_info as $property_name => &$property_info) {
    // Prepare each property. This sets up the default value and the options.
    // This is to allow each property to use the data entered so far. For
    // example, if the user enters 'foo_bar' for the module machine name, the
    // proposed default for the module readable name will be 'Foo Bar'.
    $task->prepareComponentDataProperty($property_name, $property_info, $component_data);
  }
  // Set your values.
  $component_data['root_name'] = 'foo_bar';
  // Perform any final processing on the component data.
  // This prepares data, for example expands options such as hook presets.
  $task->processComponentData($component_data_info, $component_data);
  // Get the files of generated code.
  // This is an array keyed by filename, where each value is the text of the
  // file.
  $files = $task->generateComponent($component_data);

Todo/wishlist
-------------

* Maybe some nicer theming/swooshy boxes on hook descriptions or
  something to make the form look nicer/less cluttered
* I would like to add the option to import help text from a Drupal.org
  handbook page, to help encourage authors to write standardized
  documentation in http://www.drupal.org/handbook/modules/

Known issues
------------

* Can't set default values in PHP 5 for some strange reason
* Fieldsets in Opera look mondo bizarr-o
* If using D6 private file system that is not writeable by the account running
  drush then you must specify a path for the data using the --data option
  that is writeable by the account using drush.

CONTRIBUTORS
------------
* Owen Barton (grugnog2), Chad Phillips (hunmonk), and Chris Johnson
  (chrisxj) for initial brainstorming stuff @ OSCMS in Vancouver
* Jeff Robbins for the nice mockup to work from and some great suggestions
* Karthik/Zen/|gatsby| for helping debug some hairy Forms API issues
* Steven Wittens and David Carrington for their nice JS checkbox magic
* jsloan for the excellent "automatically generate module file" feature
* Folks who have submitted bug reports and given encouragement, thank you
  so much! :)
