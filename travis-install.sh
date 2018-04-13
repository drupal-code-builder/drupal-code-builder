#!/usr/bin/env sh

if [ "$TESTTYPE" = "dcb" ]; then
  composer install;
fi
if [ "$TESTTYPE" = "drupal" ]; then
  # Install Drupal alongside DCB rather than inside it.
  # WARNING: This depends on where travis chooses to clone DCB!
  cd /home/travis/build
  pwd
  ls

  mysql -e 'create database dcb'
  # Export database variable for kernel tests.
  export SIMPLETEST_DB=mysql://root:@127.0.0.1/dcb
  # Download Drupal 8 core.
  git clone --branch 8.5.x --depth 1 http://git.drupal.org/project/drupal.git
  cd drupal
  composer install -n

  composer require joachim-n/case-converter

  # Reference DCB in build site.
  # Do this lasts as it hacks Composer's vendor folder.
  # ln -s $TESTDIR vendor/drupal-code-builder
fi
