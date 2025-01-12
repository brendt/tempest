<?php

declare(strict_types=1);

namespace Tempest\Validation\Rules;

use Attribute;
use Tempest\Validation\Rule;

#[Attribute]
final readonly class DivisibleBy implements Rule
{
    public function __construct(
        public int $divisor,
    ) {
    }

    public function isValid(mixed $value): bool
    {
        if (! is_numeric($value) || $value === 0) {
            return false;
        }

        return new MultipleOf($this->divisor)->isValid($value);
    }

    public function message(): string
    {
        return "Value should be divisible by {$this->divisor}";
    }
}
