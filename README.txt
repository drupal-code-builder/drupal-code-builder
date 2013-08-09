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

- modules, containing:
  - hook implementations
  - test class
  - api.php file
  - README file
- custom themes, containing:
  - theme template overrides

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

This framework has a public API which can be used by other modules.

To get started with using the Module Builder API, see:
  - module_builder_get_factory()
  - the classes in Environment/Environment.php
  - the tasks handlers in the Task folder.

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
