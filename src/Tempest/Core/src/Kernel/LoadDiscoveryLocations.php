<?php

declare(strict_types=1);

namespace Tempest\Core\Kernel;

use Tempest\Core\Composer;
use Tempest\Core\Kernel;
use Tempest\Discovery\DiscoveryException;
use Tempest\Discovery\DiscoveryLocation;
use function Tempest\path;

/** @internal */
final readonly class LoadDiscoveryLocations
{
    public function __construct(
        private Kernel $kernel,
        private Composer $composer,
    ) {
    }

    public function __invoke(): void
    {
        $this->kernel->discoveryLocations =
            [
                ...$this->kernel->discoveryLocations,
                ...$this->discoverCorePackages(),
                ...$this->discoverVendorPackages(),
                ...$this->discoverAppNamespaces(),
            ];
    }

    /**
     * @return DiscoveryLocation[]
     */
    private function discoverCorePackages(): array
    {
        $composerPath = path($this->kernel->root, 'vendor/composer');
        $installed = $this->loadJsonFile(path($composerPath, 'installed.json')->toString());
        $packages = $installed['packages'] ?? [];

        $discoveredLocations = [];

        foreach ($packages as $package) {
            $packageName = ($package['name'] ?? '');
            $isTempest = str_starts_with($packageName, 'tempest');

            if (! $isTempest) {
                continue;
            }

            $packagePath = path($composerPath, $package['install-path'] ?? '');

            foreach ($package['autoload']['psr-4'] as $namespace => $namespacePath) {
                $namespacePath = path($packagePath, $namespacePath);

                $discoveredLocations[] = new DiscoveryLocation($namespace, $namespacePath->toString());
            }
        }

        return $discoveredLocations;
    }

    /**
     * @return DiscoveryLocation[]
     */
    private function discoverAppNamespaces(): array
    {
        $discoveredLocations = [];

        foreach ($this->composer->namespaces as $namespace) {
            $path = path($this->kernel->root, $namespace->path);

            $discoveredLocations[] = new DiscoveryLocation($namespace->namespace, $path->toString());
        }

        return $discoveredLocations;
    }

    /**
     * @return DiscoveryLocation[]
     */
    private function discoverVendorPackages(): array
    {
        $composerPath = path($this->kernel->root, 'vendor/composer');
        $installed = $this->loadJsonFile(path($composerPath, 'installed.json')->toString());
        $packages = $installed['packages'] ?? [];

        $discoveredLocations = [];

        foreach ($packages as $package) {
            $packageName = ($package['name'] ?? '');
            $isTempest = str_starts_with($packageName, 'tempest');

            if ($isTempest) {
                continue;
            }

            $packagePath = path($composerPath, $package['install-path'] ?? '');
            $requiresTempest = isset($package['require']['tempest/framework']) || isset($package['require']['tempest/core']);
            $hasPsr4Namespaces = isset($package['autoload']['psr-4']);

            if (! ($requiresTempest && $hasPsr4Namespaces)) {
                continue;
            }

            foreach ($package['autoload']['psr-4'] as $namespace => $namespacePath) {
                $path = path($packagePath, $namespacePath);

                $discoveredLocations[] = new DiscoveryLocation($namespace, $path->toString());
            }
        }

        return $discoveredLocations;
    }

    private function loadJsonFile(string $path): array
    {
        if (! file_exists($path)) {
            throw new DiscoveryException(sprintf('Could not locate %s, try running "composer install"', $path));
        }

        return json_decode(file_get_contents($path), true);
    }
}
