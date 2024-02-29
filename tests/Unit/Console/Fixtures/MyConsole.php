<?php

declare(strict_types=1);

namespace Tests\Tempest\Unit\Console\Fixtures;

use Tempest\Console\ConsoleCommand;

class MyConsole
{
    #[ConsoleCommand(
        name: 'test',
        description: 'description',
    )]
    public function handle(
        string $path,
        int    $times = 1,
        bool   $force = false,
    ) {
    }
}
