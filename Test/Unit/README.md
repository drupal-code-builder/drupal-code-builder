# Tests

Drupal Code Builder has a comprehensive suite of tests. These are grouped into
the following folders:

- Unit: Tests which each cover a specific Generator class. The output of the
  Generate task is tested, which includes passing PHP code through PHP linting
  and PHP_CodeSniffer with Drupal Coder's Drupal coding standards.
- Integration/Collection: Tests which verify the analysis of Drupal code.
- Integration/Installation: Tests which install generated Drupal extensions into
  a Drupal site and verify they function correctly.

## Upgrading Drupal core version of tests

Because tests target a specific version of Drupal, because either of the
analysis data they use, or the version they run on, they need to be upgraded
periodically.

These are the steps required.

TODO: The following should be scripted!

### Unit tests

1. Rename test files to have the new major version suffix:

```
ag -g 8Test.php | perl -pe 'print $_; s/8/10/' | xargs -n2 mv
```

Exclude any tests which are no longer relevant on the new major version.

2. Rename classes inside the files:

```
perl -pi -e 's/8Test/10Test/' ./*10Test.php
```

3. Update the major version declared in the test:

```
perl -pi -e 's/drupalMajorVersion = 8/drupalMajorVersion = 10/' ./*10Test.php
```

4. Update assertions for the 'core_version_requirement' property in info files:
   assertPropertyHasValue('core_version_requirement', '^8 || ^9 || ^10'

5. Add hook templates for the new major version:
   cp templates/8/ templates/10/

6. Generate sample analysis data for the new version. This can be done with the
   Module Builder Devel module, for instance.
