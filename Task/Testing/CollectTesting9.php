<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Task\Testing\CollectTesting8.
 */

namespace DrupalCodeBuilder\Task\Testing;

use DrupalCodeBuilder\Task\Collect9;

/**
 * Collect hook definitions to be stored as a file in our tests folder.
 *
 * This task is meant for internal use only, to keep the testing hook
 * definitions up to date.
 *
 * The Module Builder Devel module (included within Module Builder) has a UI
 * for using this task.
 *
 * TODO: remove, this doesn't do anything! Or make it do something and put
 * all the jobs we run for sample data collection here rather than the faffy
 * filtering in each collector helper.
 */
class CollectTesting9 extends Collect9 {

}
