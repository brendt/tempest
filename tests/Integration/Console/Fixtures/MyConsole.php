<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\Console\Fixtures;

use Tempest\Console\ConsoleCommand;

final class MyConsole
{
    #[ConsoleCommand(
        name: 'test',
        description: 'description',
    )]
    public function handle(
        string $path,
        TestStringEnum $type,
        TestStringEnum $fallback = TestStringEnum::A,
        int $times = 1,
        bool $force = false,
    ): void {
    }
}
