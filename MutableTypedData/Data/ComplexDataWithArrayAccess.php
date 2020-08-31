<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\ComplexData;

// shim to reduce the amount of array access that I have to convert to OO!
class ComplexDataWithArrayAccess extends ComplexData implements \ArrayAccess {

  use DataItemArrayAccessTrait;

}
