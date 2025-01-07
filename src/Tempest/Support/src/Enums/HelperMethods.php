<?php

declare(strict_types=1);

namespace Tempest\Support\Enums;

use Tempest\Support\ArrayHelper;

use function Tempest\Support\arr;

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

    /**
     * Wrap all enum cases in an array
     *
     * @return ArrayHelper<static>
     */
    public static function collect(): ArrayHelper {
        return arr(static::cases());
    }

    /**
     * Returns an associative array of case names and values
     * For pure enums, this method is the equivalent of `values()`
     *
     * @return array<int|string, string>
     */
    public static function options(): array {
        return is_subclass_of(static::class, \BackedEnum::class)
            ? array_column(static::cases(), 'value', 'name')
            : self::values();
    }
}
