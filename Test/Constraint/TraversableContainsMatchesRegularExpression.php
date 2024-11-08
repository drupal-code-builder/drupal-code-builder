<?php

namespace DrupalCodeBuilder\Test\Constraint;

use PHPUnit\Framework\Constraint\TraversableContains;
use SplObjectStorage;

/**
 * PHPUnit constraint for an array having an element matching a regex.
 */
class TraversableContainsMatchesRegularExpression extends TraversableContains {

    private readonly string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * Returns a string representation of the constraint.
     */
    public function toString(bool $exportObjects = false): string
    {
        return 'contains an item matching regular expression "' . $this->pattern . '"';
    }

    /**
     * Evaluates the constraint for parameter $other. Returns true if the
     * constraint is met, false otherwise.
     */
    protected function matches(mixed $other): bool
    {
        if ($other instanceof SplObjectStorage) {
            throw new \Exception("Objects not supported!");
        }

        foreach ($other as $element) {
            if (preg_match($this->pattern, $element)) {
                return true;
            }
        }

        return false;
    }

}

