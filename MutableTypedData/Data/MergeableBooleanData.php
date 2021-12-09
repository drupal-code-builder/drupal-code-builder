<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\BooleanData;

class MergeableBooleanData extends BooleanData implements MergeableDataInterface {

  use MergeableSimpleDataTrait;

}
