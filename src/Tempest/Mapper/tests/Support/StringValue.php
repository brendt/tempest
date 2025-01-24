<?php

declare(strict_types=1);

namespace Tempest\Mapper\Tests\Support;

final readonly class StringValue
{
    public function __construct(public string $value)
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
