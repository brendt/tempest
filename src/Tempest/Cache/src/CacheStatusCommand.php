<?php

declare(strict_types=1);

namespace Tempest\Cache;

use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\HasConsole;
use Tempest\Container\Container;

final readonly class CacheStatusCommand
{
    use HasConsole;

    public function __construct(
        private Console $console,
        private CacheConfig $cacheConfig,
        private Container $container,
    ) {
    }

    #[ConsoleCommand(name: 'cache:status', description: 'Shows which caches are enabled')]
    public function __invoke(): void
    {
        $this->console->header('Cache status');
        $this->console->keyValue('Total caches', (string) count($this->cacheConfig->caches));
        $this->console->keyValue('Global cache', match ($this->cacheConfig->enable) {
            true => '<style="bold fg-green">ENABLED</style>',
            false => '<style="bold fg-red">FORCEFULLY DISABLED</style>',
            default => '<style="bold fg-gray">DISABLED</style>',
        });

        foreach ($this->cacheConfig->caches as $cacheClass) {
            /** @var Cache $cache */
            $cache = $this->container->get($cacheClass);

            $this->console->keyValue(
                key: $cacheClass,
                value: match ($cache->isEnabled()) {
                    true => '<style="bold fg-green">ENABLED</style>',
                    false => '<style="bold fg-red">DISABLED</style>',
                },
            );
        }
    }
}
