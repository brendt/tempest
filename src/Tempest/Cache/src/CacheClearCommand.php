<?php

declare(strict_types=1);

namespace Tempest\Cache;

use Tempest\Console\ConsoleCommand;
use Tempest\Console\HasConsole;
use Tempest\Container\Container;

final readonly class CacheClearCommand
{
    use HasConsole;

    public function __construct(
        private CacheConfig $cacheConfig,
        private Container $container,
    ) {
    }

    #[ConsoleCommand(name: 'cache:clear', description: 'Clears all or specified caches')]
    public function __invoke(bool $all = false): void
    {
        $caches = $this->cacheConfig->caches;

        if ($all === false) {
            $caches = $this->ask(
                question: 'Which caches do you want to clear?',
                options: $this->cacheConfig->caches,
                multiple: true,
            );
        }

        if (count($caches) === 0) {
            $this->console->info('No cache selected.');

            return;
        }

        $this->console->header('Clearing caches');

        foreach ($caches as $cacheClass) {
            /** @var Cache $cache */
            $cache = $this->container->get($cacheClass);
            $cache->clear();

            $this->console->keyValue(
                key: $cacheClass,
                value: "<style='bold fg-green'>CLEARED</style>",
            );
        }
    }
}
