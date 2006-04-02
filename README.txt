$Id$

Welcome to module_builder.module! This is an early prototype of a 
module designed to help expedite the process of creating custom 
modules. Simply fill out the form, select the hooks you want and the 
script will automatically generate a skeleton module file for you, 
along with PHPDoc comments and function definitions.

FEATURES
--------
* Automatically parses available hook names from CVS at your command...
  so help keep those files updated, won't you? ;)
* Comes with some sample standard hook function declarations to get 
  you started and a default module header; but if you don't like those,
  simply rename the .template files to -custom.template instead and 
  create your own definitions!
* Saves you the trouble of looking at drupaldocs.org 50 times a day to 
  remember what arguments and what order different hooks use. Score one
  for laziness! ;)
* NEW! Automatically selects hooks based on your needs.

INSTALL/CONFIG
--------------
1. Move this folder into your modules/ directory like you would any 
   other module.
2. Enable it from administer >> modules.
3. Go to administer >> settings >> module_builder and specify the path
   to save the hook documentation files
4. (Optional) Create custom function declaration template file if you
   don't like the default output.
5. (Optional) Create your own hook groupings if you don't like the 
   default ones.

TODO/WISHLIST
-------------
* Maybe some nicer theming/swooshy boxes on hook descriptions or 
  something to make the form look nicer/less cluttered
* I would like to add the option to import help text from a Drupal.org
  handbook page, to help encourage authors to write standardized 
  documentation in http://www.drupal.org/handbook/modules/

KNOWN ISSUES
------------
* Can't set default values in PHP 5 for some strange reason
* Nothing is done with the 'readable' name (or node types in general) yet.

CONTRIBUTORS
------------
* Owen Barton (grugnog2), Chad Phillips (hunmonk), and Chris Johnson 
  (chrisxj) for initial brainstorming stuff @ OSCMS in Vancouver
* Jeff Robbins for the nice mockup to work from and some great suggestions
* Karthik/Zen/|gatsby| for helping debug some hairy Forms API issues
* Steven Wittens and David Carrington for their nice JS checkbox magic
* Folks who have submitted bug reports and given encouragement, thank you
  so much! :)
