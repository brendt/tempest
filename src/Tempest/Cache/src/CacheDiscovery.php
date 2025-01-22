<?php

declare(strict_types=1);

namespace Tempest\Cache;

use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class CacheDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly CacheConfig $cacheConfig,
    ) {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        if ($class->implements(Cache::class)) {
            $this->discoveryItems->add($location, $class->getName());
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $className) {
            $this->cacheConfig->addCache($className);
        }
    }
}
