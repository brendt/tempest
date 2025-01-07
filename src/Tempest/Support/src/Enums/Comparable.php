<?php

declare(strict_types=1);

namespace Tempest\Support\Enums;

use Traversable;

/**
 * This trait provides helpers to compare enums
 */
trait Comparable
{
    /**
     * Check if the current enum is equal to the given enum
     *
     * @param \UnitEnum $enum The enum to compare
     *
     * @return boolean True if the enums are equal, false otherwise
     */
    public function is(\UnitEnum $enum): bool {
        return $this === $enum;
    }

    /**
     * Check if the current enum is not equal to the given enum
     *
     * @param \UnitEnum $enum The enum to compare
     *
     * @return boolean True if the enums are not equal, false otherwise
     */
    public function isNot(\UnitEnum $enum): bool {
        return ! $this->is($enum);
    }
}
