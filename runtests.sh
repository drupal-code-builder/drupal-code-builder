#!/bin/bash

# Shell script to run unit tests in Test/Unit.
#
# Usage:
#   - runtests.sh - run all unit tests.
#   - runtests.sh group - run unit tests in the specified PHPUnit annotation
#     group, where 'group' is a lower case string.
#   - runtests.sh ClassName - run unit tests in the specified short class name,
#     where 'ClassName' must be a title case string. 

if [[ $1 =~ ^[a-z] ]]; then
  # Lowercase implies a group.
  FILE="Test/Unit";
  GROUP="--group=$1";
elif [[ $1 = "" ]]; then
  # Run all tests.
  FILE="Test/Unit";
  GROUP="";
else
  # Anything else is a class name.
  FILE="Test/Unit/$1.php";
  GROUP="";
fi

vendor/phpunit/phpunit/phpunit "$FILE" "$GROUP"
