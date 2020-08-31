<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\MutableData;

// shim to reduce the amount of array access that I have to convert to OO!
class MutableDataWithArrayAccess extends MutableData implements \ArrayAccess {

  use DataItemArrayAccessTrait;

}
