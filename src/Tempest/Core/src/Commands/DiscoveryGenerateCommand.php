<?php

declare(strict_types=1);

namespace Tempest\Core\Commands;

use Tempest\Cache\DiscoveryCacheStrategy;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\HasConsole;
use Tempest\Container\Container;
use Tempest\Container\GenericContainer;
use Tempest\Core\DiscoveryCache;
use Tempest\Core\Kernel;
use Tempest\Core\Kernel\LoadDiscoveryClasses;
use function Tempest\env;

final readonly class DiscoveryGenerateCommand
{
    use HasConsole;

    public function __construct(
        private Kernel $kernel,
        private DiscoveryCache $discoveryCache,
    ) {
    }

    #[ConsoleCommand(name: 'discovery:generate', description: 'Compile and cache all discovery according to the configured discovery caching strategy')]
    public function __invoke(): void
    {
        $strategy = $this->resolveDiscoveryCacheStrategy();

        if ($strategy === DiscoveryCacheStrategy::NONE) {
            $this->info('Discovery cache disabled, nothing to generate.');

            return;
        }

        $this->clearDiscoveryCache();

        $this->generateDiscoveryCache($strategy);

        $this->discoveryCache->storeStrategy($strategy);
    }

    public function clearDiscoveryCache(): void
    {
        $this->info('Clearing existing discovery cache…');

        $this->console->call('discovery:clear');
    }

    public function generateDiscoveryCache(DiscoveryCacheStrategy $strategy): void
    {
        $this->info(sprintf('Generating new discovery cache… (cache strategy used: %s)', $strategy->value));

        $kernel = $this->resolveKernel();

        $loadDiscoveryClasses = new LoadDiscoveryClasses(
            kernel: $kernel,
            container: $kernel->container,
            discoveryCache: $this->discoveryCache,
        );

        $discoveries = $loadDiscoveryClasses->build();

        $count = 0;

        foreach ($discoveries as $discovery) {
            $discoveryItems = $discovery->getItems();

            if ($strategy === DiscoveryCacheStrategy::PARTIAL) {
                $discoveryItems = $discoveryItems->onlyVendor();
            }

            $this->discoveryCache->store($discovery, $discoveryItems);

            $count += $discoveryItems->count();
        }

        $this->success(sprintf(
            'Cached <em>%d</em> items',
            $count,
        ));
    }

    private function resolveDiscoveryCacheStrategy(): DiscoveryCacheStrategy
    {
        $cache = env('CACHE');

        if ($cache !== null) {
            return DiscoveryCacheStrategy::make($cache);
        }

        return DiscoveryCacheStrategy::make(env('DISCOVERY_CACHE'));
    }

    public function resolveKernel(): Kernel
    {
        $container = new GenericContainer();
        $container->singleton(Container::class, $container);

        return new Kernel(
            root: $this->kernel->root,
            discoveryLocations: $this->kernel->discoveryLocations,
            container: $container,
        )
            ->registerKernel()
            ->loadComposer()
            ->loadConfig();
    }
}
