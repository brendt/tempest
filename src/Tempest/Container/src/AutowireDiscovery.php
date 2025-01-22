<?php

declare(strict_types=1);

namespace Tempest\Container;

use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

/**
 * @property GenericContainer $container
 */
final class AutowireDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        $autowire = $class->getAttribute(Autowire::class);

        if ($autowire === null) {
            return;
        }

        $this->discoveryItems->add($location, [$class, $autowire]);
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as [$class, $autowire]) {
            if ($autowire !== null) {
                $this->discoverAsSingleton($class);
            } else {
                $this->discoverAsDefinition($class);
            }
        }
    }

    private function discoverAsSingleton(ClassReflector $class): void
    {
        $interfaces = $class->getReflection()->getInterfaceNames();

        foreach ($interfaces as $interface) {
            $this->container->singleton(
                $interface,
                static fn (Container $container) => $container->get($class->getName()),
            );
        }
    }

    private function discoverAsDefinition(ClassReflector $class): void
    {
        $interfaces = $class->getReflection()->getInterfaceNames();

        foreach ($interfaces as $interface) {
            $this->container->register(
                $interface,
                static fn (Container $container) => $container->get($class->getName()),
            );
        }
    }
}
