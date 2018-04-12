#!/usr/bin/env sh

if [ "$TESTTYPE" = "dcb" ]; then
  composer install;
fi
if [ "$TESTTYPE" = "drupal" ]; then
  mysql -e 'create database dcb'
  # Export database variable for kernel tests.
  export SIMPLETEST_DB=mysql://root:@127.0.0.1/dcb
  # Download Drupal 8 core.
  travis_retry git clone --branch 8.5.x --depth 1 http://git.drupal.org/project/drupal.git
  cd drupal
  composer self-update
  composer install -n

  composer require joachim-n/case-converter

  # Reference DCB in build site.
  # Do this lasts as it hacks Composer's vendor folder.
  ln -s $TESTDIR vendor/drupal-code-builder
fi
