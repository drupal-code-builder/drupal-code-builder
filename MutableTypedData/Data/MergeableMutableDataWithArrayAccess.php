<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\MutableData;

class MergeableMutableDataWithArrayAccess extends MutableData implements \ArrayAccess, MergeableDataInterface {

  use DataItemArrayAccessTrait;

  use MergeableComplexDataTrait;

}
