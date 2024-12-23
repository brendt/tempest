<?php

declare(strict_types=1);

namespace Tempest\Console\Components;

use BackedEnum;
use ReflectionEnum;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;
use UnitEnum;

/**
 * @template T
 */
final class Option
{
    public int|string $key;

    /** @var T */
    public mixed $value;

    /** @var T */
    public mixed $displayValue {
        get {
            if (! $this->value instanceof UnitEnum) {
                return $this->value;
            }

            if (method_exists($this->value, 'toString')) {
                return call_user_func([$this->value, 'toString']);
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
    public function __construct(int|string $key, mixed $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function equals(Option $other): bool
    {
        return $other->key === $this->key && $other->value === $this->value;
    }
}
