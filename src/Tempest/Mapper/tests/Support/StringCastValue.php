<?php

declare(strict_types=1);

namespace Tempest\Mapper\Tests\Support;

final readonly class StringCastValue
{
    private function __construct(private string $value)
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function cast(string $value): self
    {
        return new self($value);
    }
}
