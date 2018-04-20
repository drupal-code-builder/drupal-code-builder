#!/usr/bin/env sh

# Installation script for TravisCI.
# This is a workaround for multiline conditionals not working properly inside
# the .travis.yml file.

if [ "$TESTTYPE" = "dcb" ]; then
  composer install;
fi
if [ "$TESTTYPE" = "drupal" ]; then
  # Install DCB dependencies, without dev, as otherwise we'd get two copies of
  # PHPUnit.
  # This will zap our repository's autoload files, which we want, as (currently)
  # they include dev requirements.
  composer install --no-dev;

  # Install Drupal alongside DCB rather than inside it.
  # WARNING: This depends on where travis chooses to clone DCB!
  cd /home/travis/build

  mysql -e 'create database dcb'

  # Download Drupal 8 core.
  git clone --branch 8.5.x --depth 1 http://git.drupal.org/project/drupal.git
  cd drupal
  composer install -n

  composer require joachim-n/case-converter
fi
