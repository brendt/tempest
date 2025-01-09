<?php

declare(strict_types=1);

namespace Tempest\Core\Commands;

use Closure;
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

        $this->console->task(
            label: "Generating discovery cache using the {$strategy->value} strategy",
            handler: fn (Closure $log) => $this->generateDiscoveryCache($strategy, $log),
        );

        $this->discoveryCache->storeStrategy($strategy);
    }

    public function clearDiscoveryCache(): void
    {
        $this->console->call(DiscoveryClearCommand::class);
    }

    public function generateDiscoveryCache(DiscoveryCacheStrategy $strategy, Closure $log): void
    {
        $kernel = $this->resolveKernel();

        $loadDiscoveryClasses = new LoadDiscoveryClasses(
            kernel: $kernel,
            container: $kernel->container,
            discoveryCache: $this->discoveryCache,
        );

        $discoveries = $loadDiscoveryClasses->build();

        foreach ($discoveries as $discovery) {
            $log($discovery::class);
            $discoveryItems = $discovery->getItems();

            if ($strategy === DiscoveryCacheStrategy::PARTIAL) {
                $discoveryItems = $discoveryItems->onlyVendor();
            }

            $this->discoveryCache->store($discovery, $discoveryItems);
        }
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
