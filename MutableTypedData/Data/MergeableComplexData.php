<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

use MutableTypedData\Data\ComplexData;

class MergeableComplexData extends ComplexData implements MergeableDataInterface {

  use DeltaLabelTrait;

  use MergeableComplexDataTrait;

}
