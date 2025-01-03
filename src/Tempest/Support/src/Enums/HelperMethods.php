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
     *
     * @return array<int, string>
     */
    public static function names(): array {
        return array_column(static::cases(), 'name');
    }
}
