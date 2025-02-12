<?php

declare(strict_types=1);

namespace Tempest\Reflection\Tests\Fixtures;

final class TestClassB
{
    public function __construct(
        public ?string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
