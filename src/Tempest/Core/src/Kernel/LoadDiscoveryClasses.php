<?php

declare(strict_types=1);

namespace Tempest\Core\Kernel;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tempest\Cache\DiscoveryCacheStrategy;
use Tempest\Container\Container;
use Tempest\Core\DiscoveryCache;
use Tempest\Core\DiscoveryDiscovery;
use Tempest\Core\Kernel;
use Tempest\Discovery\DiscoversPath;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryItems;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\DoNotDiscover;
use Tempest\Reflection\ClassReflector;
use Throwable;

/** @internal */
final class LoadDiscoveryClasses
{
    private array $appliedDiscovery = [];

    public function __construct(
        private readonly Kernel $kernel,
        private readonly Container $container,
        private readonly DiscoveryCache $discoveryCache,
    ) {
    }

    public function __invoke(): void
    {
        $discoveries = $this->build();

        foreach ($discoveries as $discovery) {
            $this->applyDiscovery($discovery);
        }
    }

    /** @return Discovery[] */
    public function build(): array
    {
        // DiscoveryDiscovery needs to be applied before we can build all other discoveries
        $discoveryDiscovery = $this->buildDiscovery(DiscoveryDiscovery::class);
        $this->applyDiscovery($discoveryDiscovery);

        $builtDiscovery = [$discoveryDiscovery];

        foreach ($this->kernel->discoveryClasses as $discoveryClass) {
            $discovery = $this->buildDiscovery($discoveryClass);
            $builtDiscovery[] = $discovery;
        }

        return $builtDiscovery;
    }

    /**
     * Create a discovery instance from a class name.
     * Optionally set the cached discovery items whenever caching is enabled.
     */
    private function resolveDiscovery(string $discoveryClass): Discovery
    {
        /** @var Discovery $discovery */
        $discovery = $this->container->get($discoveryClass);

        if ($this->discoveryCache->isEnabled()) {
            $discovery->setItems(
                $this->discoveryCache->restore($discoveryClass)
                ?? new DiscoveryItems(),
            );
        } else {
            $discovery->setItems(new DiscoveryItems());
        }

        return $discovery;
    }

    /**
     * Build one specific discovery instance.
     */
    private function buildDiscovery(string $discoveryClass): Discovery
    {
        $discovery = $this->resolveDiscovery($discoveryClass);

        if (
            $this->discoveryCache->getStrategy() === DiscoveryCacheStrategy::FULL
            && $discovery->getItems()->isLoaded()
        ) {
            return $discovery;
        }

        foreach ($this->kernel->discoveryLocations as $location) {
            if ($this->shouldSkipLocation($location)) {
                continue;
            }

            $directories = new RecursiveDirectoryIterator($location->path, FilesystemIterator::UNIX_PATHS | FilesystemIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($directories);

            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                $fileName = $file->getFilename();
                if ($fileName === '') {
                    continue;
                }

                if ($fileName === '.') {
                    continue;
                }

                if ($fileName === '..') {
                    continue;
                }

                $input = $file->getPathname();

                // We assume that any PHP file that starts with an uppercase letter will be a class
                if ($file->getExtension() === 'php' && ucfirst($fileName) === $fileName) {
                    $className = $location->toClassName($file->getPathname());

                    // Discovery errors (syntax errors, missing imports, etc.)
                    // are ignored when they happen in vendor files,
                    // but they are allowed to be thrown in project code
                    if ($location->isVendor()) {
                        try {
                            $input = new ClassReflector($className);
                        } catch (Throwable) {
                        }
                    } elseif (class_exists($className)) {
                        $input = new ClassReflector($className);
                    }
                }

                if ($input instanceof ClassReflector) {
                    // If the input is a class, we'll call `discover`
                    if (! $this->shouldSkipDiscoveryForClass($discovery, $input)) {
                        $discovery->discover($location, $input);
                    }
                } elseif ($discovery instanceof DiscoversPath) {
                    // If the input is NOT a class, AND the discovery class can discover paths, we'll call `discoverPath`
                    $discovery->discoverPath($location, $input);
                }
            }
        }

        return $discovery;
    }

    /**
     * Apply the discovered classes and files. Also store the discovered items into cache, if caching is enabled
     */
    private function applyDiscovery(Discovery $discovery): void
    {
        if ($this->appliedDiscovery[$discovery::class] ?? null) {
            return;
        }

        $discovery->apply();

        $this->appliedDiscovery[$discovery::class] = true;
    }

    /**
     * Check whether discovery for a specific class should be skipped based on the #[DoNotDiscover] attribute
     */
    private function shouldSkipDiscoveryForClass(Discovery $discovery, ClassReflector $input): bool
    {
        $attribute = $input->getAttribute(DoNotDiscover::class);

        if ($attribute === null) {
            return false;
        }

        return ! in_array($discovery::class, $attribute->except, strict: true);
    }

    /**
     * Check whether a discovery location should be skipped based on what's cached for a specific discovery class
     */
    private function shouldSkipLocation(DiscoveryLocation $location): bool
    {
        if (! $this->discoveryCache->isEnabled()) {
            return false;
        }

        return match ($this->discoveryCache->getStrategy()) {
            // If discovery cache is disabled, no locations should be skipped, all should always be discovered
            DiscoveryCacheStrategy::NONE, DiscoveryCacheStrategy::INVALID => false,

            // If discover cache is enabled, all locations cache should be skipped
            DiscoveryCacheStrategy::FULL => true,

            // If partial discovery cache is enabled, vendor locations cache should be skipped
            DiscoveryCacheStrategy::PARTIAL => $location->isVendor(),
        };
    }
}
