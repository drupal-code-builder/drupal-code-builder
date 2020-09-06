<?php

namespace DrupalCodeBuilder\Definition;

/**
 * Interface for providing option definitions.
 */
interface OptionsProviderInterface {

  public function getOptions(): array;

}
