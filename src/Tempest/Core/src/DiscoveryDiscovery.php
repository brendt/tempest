<?php

declare(strict_types=1);

namespace Tempest\Core;

use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class DiscoveryDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly Kernel $kernel,
    ) {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        if ($class->getName() === self::class) {
            return;
        }

        if (! $class->implements(Discovery::class)) {
            return;
        }

        $this->discoveryItems->add($location, $class->getName());
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $className) {
            $this->kernel->discoveryClasses[] = $className;
        }
    }
}
