<?php

declare(strict_types=1);

namespace Tempest\EventBus\Tests\Fixtures;

final readonly class MyService
{
    public function __construct(
        public string $value,
    ) {
    }
}
