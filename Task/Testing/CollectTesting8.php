<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Testing\CollectTesting8.
 */

namespace DrupalCodeBuilder\Task\Testing;

use DrupalCodeBuilder\Task\Collect8;
use DrupalCodeBuilder\Factory;

/**
 * Collect hook definitions to be stored as a file in our tests folder.
 *
 * This task is meant for internal use only, to keep the testing hook
 * definitions up to date.
 *
 * The Drush command mb-download has a developer option 'test' which switches
 * it to use this task:
 * @code
 *   drush mbdl --test --strict=0
 * @endcode
 *
 * TODO: remove, this doesn't do anything! Or make it do something and put
 * all the jobs we run for sample data collection here rather than the faffy
 * filtering in each collector helper.
 */
class CollectTesting8 extends Collect8 {

}
