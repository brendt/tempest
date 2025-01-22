<?php

declare(strict_types=1);

namespace Tempest\Support\Enums;

use ArrayIterator;
use BackedEnum;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use Traversable;
use UnitEnum;

/**
 * This trait provides helpers to compare enums
 */
trait Comparable
{
    /**
     * Check if the current enum case is equal to the given enum
     *
     * @param UnitEnum $enum The enum to compare
     *
     * @return bool True if the enums are equal, false otherwise
     */
    public function is(UnitEnum $enum): bool
    {
        return $this === $enum;
    }

    /**
     * Check if the current enum case is not equal to the given enum
     *
     * @param UnitEnum $enum The enum to compare
     *
     * @return bool True if the enums are not equal, false otherwise
     */
    public function isNot(UnitEnum $enum): bool
    {
        return ! $this->is($enum);
    }

    /**
     * Check if the current enum case is in the given list of enums
     *
     * @param Traversable<UnitEnum>|array<UnitEnum> $enums The list of enums to check
     *
     * @return bool True if the current enum is in the list, false otherwise
     */
    public function in(Traversable|array $enums): bool
    {
        $iterator = match (true) {
            is_array($enums) => new ArrayIterator($enums),
            $enums instanceof Iterator => $enums,
            $enums instanceof IteratorAggregate => $enums->getIterator(),
            default => throw new InvalidArgumentException(sprintf('The given value must be an iterable value, "%s" given', get_debug_type($enums))),
        };

        foreach ($iterator as $enum) {
            if ($this->is($enum)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current enum case is not in the given list of enums
     *
     * @param Traversable<UnitEnum>|array<UnitEnum> $enums The list of enums to check
     *
     * @return bool True if the current enum is not in the list, false otherwise
     */
    public function notIn(Traversable|array $enums): bool
    {
        return ! $this->in($enums);
    }

    /**
     * Check if the current enum has the name in its cases
     *
     * @param string $name The enum case name
     *
     * @return bool True if the name is in the enum, false otherwise
     */
    public static function has(string $name): bool
    {
        $caseNames = array_column(static::cases(), 'name');

        return in_array($name, $caseNames, strict: true);
    }

    /**
     * Check if the current enum does not have the name in its cases
     *
     * @param string $name The enum case name
     *
     * @return bool True if the name is not in the enum, false otherwise
     */
    public static function hasNot(string $name): bool
    {
        return ! self::has($name);
    }

    /**
     * Check if the current enum has the value in its cases
     * For Pure enums, this method is the equivalent of `has()`
     *
     * @param string|int $value The value to check
     *
     * @return bool True if the value is in the enum, false otherwise
     */
    public static function hasValue(string|int $value): bool
    {
        $caseValues = is_subclass_of(static::class, BackedEnum::class)
            ? array_column(static::cases(), 'value')
            : array_column(static::cases(), 'name');

        return in_array($value, $caseValues, strict: true);
    }

    /**
     * Check if the current enum does not have the value in its cases
     * For Pure enums, this method is the equivalent of `hasNot()`
     *
     * @param string|int $value The value to check
     *
     * @return bool True if the value is not in the enum, false otherwise
     */
    public static function hasNotValue(string|int $value): bool
    {
        return ! self::hasValue($value);
    }
}
