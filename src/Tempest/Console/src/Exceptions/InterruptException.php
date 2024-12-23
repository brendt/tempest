<?php

declare(strict_types=1);

namespace Tempest\Console\Exceptions;

use Tempest\Console\Console;

final class InterruptException extends ConsoleException
{
    public function render(Console $console): void
    {
        $console->writeln();
        $console->error('Interrupted by user.');
    }
}
