<?php

declare(strict_types=1);

namespace Tempest\Console\Discovery;

use Tempest\Console\ConsoleCommand;
use Tempest\Console\ConsoleConfig;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class ConsoleCommandDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly ConsoleConfig $consoleConfig,
    ) {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getPublicMethods() as $method) {
            $consoleCommand = $method->getAttribute(ConsoleCommand::class);

            if (! $consoleCommand) {
                continue;
            }

            $this->discoveryItems->add($location, [$method, $consoleCommand]);
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as [$method, $consoleCommand]) {
            $this->consoleConfig->addCommand($method, $consoleCommand);
        }
    }
}
