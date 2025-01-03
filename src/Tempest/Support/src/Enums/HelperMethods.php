<?php

declare(strict_types=1);

namespace Tempest\Support\Enums;

/**
 * This trait provides some useful methods for enums
 */
trait HelperMethods
{
    /**
     * Gets an array of case names
     * Both pure and backed enums will return their names
     *
     * @return array<int, string>
     */
    public static function names(): array {
        return array_column(static::cases(), 'name');
    }

    /**
     * Gets an array of case values
     * Pure enums will return their names, backed enums will return their values
     *
     * @return array<int, string>
     */
    public static function values(): array {
        return is_subclass_of(static::class, \BackedEnum::class)
            ? array_column(static::cases(), 'value')
            : array_column(static::cases(), 'name');
    }
}
