<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\StringData;

class MergeableStringData extends StringData implements MergeableDataInterface {

  use MergeableSimpleDataTrait;

}
