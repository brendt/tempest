<?php

declare(strict_types=1);

namespace Tempest\Core\Commands;

use Tempest\Cache\DiscoveryCacheStrategy;
use Tempest\Console\Console;
use Tempest\Console\ConsoleArgument;
use Tempest\Console\ConsoleCommand;
use Tempest\Core\DiscoveryCache;
use Tempest\Core\Kernel;
use function Tempest\root_path;
use function Tempest\Support\str;

final readonly class DiscoveryStatusCommand
{
    public function __construct(
        private Console $console,
        private Kernel $kernel,
        private DiscoveryCache $discoveryCache,
    ) {
    }

    #[ConsoleCommand(name: 'discovery:status', description: 'Lists all discovery locations and discovery classes')]
    public function __invoke(
        #[ConsoleArgument(description: 'Prints discovery classes', aliases: ['c'])]
        bool $showClasses = false,
        #[ConsoleArgument(description: 'Prints discovery locations', aliases: ['l'])]
        bool $showLocations = false,
    ): void {
        $this->console->header('Discovery status');
        $this->console->keyValue('Registered locations', (string) count($this->kernel->discoveryLocations));
        $this->console->keyValue('Loaded discovery classes', (string) count($this->kernel->discoveryClasses));
        $this->console->keyValue('Cache', match ($this->discoveryCache->isEnabled()) {
            true => '<style="fg-green bold">ENABLED</style>',
            false => '<style="fg-gray bold">DISABLED</style>',
        });
        $this->console->keyValue('Cache strategy', match ($this->discoveryCache->getStrategy()) {
            DiscoveryCacheStrategy::FULL => '<style="fg-green bold">FULL</style>',
            DiscoveryCacheStrategy::PARTIAL => '<style="fg-blue bold">PARTIAL</style>',
            DiscoveryCacheStrategy::NONE => '<style="fg-gray bold">NO CACHING</style>',
            DiscoveryCacheStrategy::INVALID => '<style="fg-red bold">INVALID</style>',
        });
        $this->console->keyValue('Cache validity', match ($this->discoveryCache->isValid()) {
            true => '<style="fg-blue bold">OK</style>',
            false => '<style="fg-red bold">CORRUPTED</style>',
        });

        if ($showClasses) {
            $this->console->header('Discovery classes', subheader: 'These classes are used by Tempest to determine which classes to discover and how to handle them.');
            $this->console->writeln();

            foreach ($this->kernel->discoveryClasses as $discoveryClass) {
                $this->console->keyValue("<style='fg-gray'>{$discoveryClass}</style>");
            }
        }

        if ($showLocations) {
            $this->console->header('Discovery locations', subheader: 'These locations are used by Tempest to discover classes.');
            $this->console->writeln();

            foreach ($this->kernel->discoveryLocations as $discoveryLocation) {
                $path = str(realpath($discoveryLocation->path))
                    ->replaceStart(root_path(), '.')
                    ->toString();

                $this->console->keyValue("<style='fg-gray'>{$path}</style>");
            }
        }
    }
}
