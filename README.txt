$Id$

Welcome to module_builder.module! This is an early prototype of a 
module designed to help expedite the process of creating custom 
modules. Simply fill out the form, select the hooks you want and the 
script will automatically generate a skeleton module file for you, 
along with PHPDoc comments and function definitions.

FEATURES
--------
* Automatically parses available hook names from CVS, so is as 
  up-to-date as your contrib repository (and the hook documentation...
  so help keep those files updated, won't you? ;))
* Comes with some sample standard hook function declarations to get 
  you started and a default module header; but if you don't like those,
  simply rename the .template files to -custom.template instead and 
  create your own definitions!
* Saves you the trouble of looking at drupaldocs.org 50 times a day to 
  remember what arguments and what order different hooks use. Score one
  for laziness! ;)

INSTALL/CONFIG
--------------
1. Move this folder into your modules/ directory like you would any 
   other module.
2. Enable it from administer >> modules.
3. Go to administer >> settings >> module_builder and specify the path
   to the docs/developer/hooks folder in a local checkout of contrib.
   (if above sounded like gobbeldygook, then please check 
   http://drupal.org/node/321)
4. (Optional) Create custom function declaration or header files.

TODO/WISHLIST
-------------
* I want to make a selection of module types and create a list of 
  dependencies (example: node modules need hook_node_info, hook_insert,
  etc.)
* I need a fancy JavaScript/AJAX guru to hook up magic so when you
  select from the available module types, it automatically selects
* Maybe some nicer theming than 'default' :P
* I would like to add the option to import help text from a Drupal.org
  handbook page, to help encourage authors to write standardized 
  documentation in http://www.drupal.org/handbook/modules/

KNOWN ISSUES
------------
* Regex for parsing hooks out of documentation files is a little buggy
* Form validation routines don't fire
* Can't set default values in PHP 5 for some strange reason
* Spacing is off for some reason on some of the function declarations.
* Nothing is done with the 'readable' name yet.
