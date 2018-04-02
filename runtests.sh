#!/bin/bash

# Shell script to run unit tests in Test/Unit.
#
# Usage:
#   - runtests.sh - run all unit tests.
#   - runtests.sh group - run unit tests in the specified PHPUnit annotation
#     group, where 'group' is a lower case string.
#   - runtests.sh ClassName - run unit tests in the specified short class name,
#     where 'ClassName' must be a title case string.
#   - runtests.sh methodName - run the matching test methods (PHPUnit uses a
#     regex), where 'methodName' must start with a lower case letter.

FILE="Test/Unit";
GROUP=""
FILTER=""

if [[ $1 = "" ]]; then
  # Run all tests.
  :
elif [[ $1 =~ ^[a-z]+$ ]]; then
  # All lowercase implies a group.
  GROUP="--group=$1";
elif [[ $1 =~ ^[a-z] ]]; then
  # First letter lowercase is a method name to filter by.
  FILTER="--filter=$1"
else
  # Anything else is a class name.
  FILE="Test/Unit/$1.php";
  GROUP="";
fi

vendor/phpunit/phpunit/phpunit "$FILE" "$GROUP" "$FILTER"
