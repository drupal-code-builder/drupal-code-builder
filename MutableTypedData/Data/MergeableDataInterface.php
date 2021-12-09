<?php

namespace DrupalCodeBuilder\MutableTypedData\Data;

interface MergeableDataInterface {

  /**
   * Merges a data item into this one.
   *
   * For simple data, both sides must have identical values or one side must be
   * empty, as there is no way to merge two non-empty values without data loss.
   *
   * For single complex data, properties are merged individually. This may
   * result in a 'meshing' merge if both sides have data but no set properties
   * in common.
   *
   * For multiple data, any incoming delta item not present in the present
   * data is appended.
   *
   * @param \DrupalCodeBuilder\MutableTypedData\Data\MergeableDataInterface $other
   *   The data item to merge. TODO: change this type to static when PHP 8 is
   *   the minimum.
   *
   * @return bool
   *   - FALSE if no additional data was added to the called data item.
   *   - TRUE if data from $other was added to the called data item.
   *
   * @throws \DrupalCodeBuilder\Exception\MergeDataLossException
   *   Throws an exception if the merge would cause data from $other to be discarded.
   */
  public function merge(MergeableDataInterface $other): bool;

}
