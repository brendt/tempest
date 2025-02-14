<?php

declare(strict_types=1);

namespace Tempest\Validation\Rules;

use Attribute;
use Tempest\Validation\Rule;

#[Attribute]
final readonly class IP implements Rule
{
    private int $options;

    public function __construct(
        private bool $allowPrivateRange = true,
        private bool $allowReservedRange = true,
    ) {
        $options = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;

        if (! $this->allowPrivateRange) {
            $options |= FILTER_FLAG_NO_PRIV_RANGE;
        }

        if (! $this->allowReservedRange) {
            $options |= FILTER_FLAG_NO_RES_RANGE;
        }

        $this->options = $options;
    }

    public function isValid(mixed $value): bool
    {
        return boolval(filter_var($value, FILTER_VALIDATE_IP, $this->options));
    }

    public function message(): string
    {
        if ($this->options & FILTER_FLAG_NO_PRIV_RANGE) {
            $additions[] = 'not in a private range';
        }

        if ($this->options & FILTER_FLAG_NO_RES_RANGE) {
            $additions[] = 'not in a reserved range';
        }

        return 'Value should be a valid IP address' . (
            empty($additions) ? '' : ' that is ' . implode(' and ', $additions)
        );
    }
}
