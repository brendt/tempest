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

    /**
     * Check if the current enum is in the given list of enums
     *
     * @param \Traversable<\UnitEnum>|array<\UnitEnum> $enums The list of enums to check
     *
     * @return boolean True if the current enum is in the list, false otherwise
     */
    public function in( Traversable|array $enums ): bool {
        $iterator = match (true) {
            is_array($enums) => new \ArrayIterator($enums),
            $enums instanceof \Iterator => $enums,
            $enums instanceof \IteratorAggregate => $enums->getIterator(),
            default => throw new \InvalidArgumentException(sprintf('The given value must be an iterable value, "%s" given', get_debug_type($enums))),
        };

        foreach ($iterator as $enum) {
            if ($this->is($enum)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current enum is not in the given list of enums
     *
     * @param \Traversable<\UnitEnum>|array<\UnitEnum> $enums The list of enums to check
     *
     * @return boolean True if the current enum is not in the list, false otherwise
     */
    public function notIn( Traversable|array $enums ): bool {
        return ! $this->in($enums);
    }
}
