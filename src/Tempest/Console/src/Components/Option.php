<?php

declare(strict_types=1);

namespace Tempest\Console\Components;

use ReflectionEnum;
use UnitEnum;

/**
 * @template T
 */
final class Option
{
    /** @var T */
    public mixed $displayValue {
        get {
            if (! $this->value instanceof UnitEnum) {
                return $this->value;
            }

            if (method_exists($this->value, 'toString')) {
                return $this->value->toString();
            }

            $reflection = new ReflectionEnum($this->value::class);

            if (! $reflection->isBacked()) {
                return $this->value->name;
            }

            return match ($reflection->getBackingType()->getName()) {
                'string' => $this->value->value,
                default => $this->value->name,
            };
        }
    }

    /** @param T $value */
    public function __construct(public int|string $key, public mixed $value)
    {
    }

    public function equals(Option $other): bool
    {
        return $other->key === $this->key && $other->value === $this->value;
    }
}
