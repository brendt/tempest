<?php

declare(strict_types=1);

namespace Tempest\Support\Enums;

use BackedEnum;
use InvalidArgumentException;
use UnitEnum;
use function Tempest\Support\arr;

/**
 * This trait provides the ability to call an enum case as a function to get its value
 */
trait InvokableCases
{
    /**
     * Returns the enum's value when it's invoked
     */
    public function __invoke(): string
    {
        return $this instanceof BackedEnum
            ? $this->value
            : $this->name;
    }

    /**
     * Returns the enum's value when it's called statically
     *
     * @param string $name The enum case
     * @param array $arguments The arguments
     *
     * @example SampleStatusBackedEnum::PUBLISH()
     * @example SampleStatusPureEnum::PUBLISH()
     */
    public static function __callStatic(string $name, array $arguments): string
    {
        $case = arr(static::cases())->first(fn (UnitEnum $case) => $name === $case->name);

        if (is_null($case)) {
            throw new InvalidArgumentException(sprintf('Call to undefined enum case or method "%s::%s"', static::class, $name));
        }

        return $case();
    }
}
