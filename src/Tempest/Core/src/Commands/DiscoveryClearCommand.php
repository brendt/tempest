<?php

declare(strict_types=1);

namespace Tempest\Core\Commands;

use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Core\DiscoveryCache;

final readonly class DiscoveryClearCommand
{
    public function __construct(
        private DiscoveryCache $discoveryCache,
        private Console $console,
    ) {
    }

    #[ConsoleCommand(name: 'discovery:clear', description: 'Clears all cached discovery files')]
    public function __invoke(): void
    {
        $this->console->task(
            label: 'Clearing discovery cache',
            handler: fn () => $this->discoveryCache->clear(),
        );
    }
}
