<?php

declare(strict_types=1);

namespace Tempest\Validation\Rules;

use Attribute;
use Tempest\Validation\Rule;

#[Attribute]
final readonly class NotIn implements Rule
{
    public function __construct(
        /** @var array<string|int> */
        private array $values,
    ) {
    }

    public function isValid(mixed $value): bool
    {
        return new In($this->values, true)->isValid($value);
    }

    public function message(): string
    {
        return 'Value cannot be any of: ' . implode(', ', $this->values);
    }
}
