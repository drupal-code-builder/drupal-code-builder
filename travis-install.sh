#!/usr/bin/env sh

# Installation script for TravisCI.
# This is a workaround for multiline conditionals not working properly inside
# the .travis.yml file.

# Get output from this script in Travis.
set -x

if [ "$TESTTYPE" = "dcb" ]; then
  composer install;
fi
if [ "$TESTTYPE" = "drupal" ]; then
  # Install Drupal alongside DCB rather than inside it.
  # WARNING: This depends on where travis chooses to clone DCB!
  cd /home/travis/build
  mkdir drupal
  cd drupal

  # Copy the composer.json and PHPUnit config files to set up the project and
  # run its tests. These are in a fixtures folder in the DCB repository.
  cp /home/travis/build/drupal-code-builder/drupal-code-builder/travis/travis.composer.json composer.json
  cp /home/travis/build/drupal-code-builder/drupal-code-builder/travis/travis.phpunit.xml phpunit.xml

  # Install the project.
  composer install

  mysql -e 'create database dcb'
fi
