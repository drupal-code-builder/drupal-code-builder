<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\ComplexData;

class MergeableComplexDataWithArrayAccess extends ComplexData implements \ArrayAccess, MergeableDataInterface {

  use DataItemArrayAccessTrait;

  use MergeableComplexDataTrait;

}
