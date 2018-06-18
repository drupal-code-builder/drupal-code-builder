Drupal Code Builder is a system for generating scaffold code for Drupal modules (and other components).

This is a library, and has no UI of its own. It can be used in several ways:
- with the [Drupal Module Builder
  project](https://www.drupal.org/project/module_builder), which provides a UI
  within Drupal. (The Drupal Code Builder library was formerly part of the
  Module Builder module.)
- with the [Drush command
  extension](https://github.com/drupal-code-builder/drupal-code-builder-drush).

Drupal Code Builder can be used for any current version of Drupal (7, 8). Older
versions are unsupported, but it should produce code for 5 and 6 also.

Tests powered by PHPUnit ensure that the generated PHP code passes PHP linting,
and adheres to Drupal Coding Standards, as enforced by PHP CodeSniffer.

## What Drupal Code Builder can do

Drupal Code Builder can generate the following for a module:
- code files, containing hook implementations
- info.yml file (.info file on Drupal 7 and older)
- README file
- PHPUnit test case classes, with presets for different types
- Simpletest test case classes
- annotated class plugins
- YAML file plugins
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

Follow the instructions given in the component that requests Drupal Coder
Builder (e.g., Module Builder, Drush command).

Additionally, if [Plugin module](https://www.drupal.org/project/plugin) is
present, plugin type definitions will be enhanced with its data.
