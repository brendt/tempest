<?php

declare(strict_types=1);

namespace Tempest\Support\Enums;

/**
 * This trait provides helpers to access enum cases
 */
trait Accessible
{
    /**
     * Gets the enum case by name
     * 
     * @throws \ValueError if the case name is not valid
     */
    public static function fromName(string $name): static {
        return static::tryFromName($name) ?? throw new \ValueError( sprintf('"%s" is not a valid case name for enum "%s"', $name, static::class) );
    }

    /**
     * Gets the enum case by name or null if the case name is not valid
     */
    public static function tryFromName(string $name): ?static {
        $cases = array_filter(
            static::cases(),
            fn( \UnitEnum $case ) => $case->name === $name
        );

        return array_shift($cases);
    }

    /**
     * Gets the enum case by name, for Pure enums
     * This will not override the `from()` method for Backed enums
     */
    public static function from( string $case ): static {
        return static::fromName($case);
    }

    /**
     * Gets the enum case by name, for Pure enums or null if the case is not valid
     * This will not override the `tryFrom()` method for Backed enums
     */
    public static function tryFrom( string $case ): ?static {
        return static::tryFromName($case);
    }
}
