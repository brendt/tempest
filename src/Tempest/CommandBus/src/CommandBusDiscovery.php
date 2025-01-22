<?php

declare(strict_types=1);

namespace Tempest\CommandBus;

use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class CommandBusDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly CommandBusConfig $commandBusConfig,
    ) {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getPublicMethods() as $method) {
            $commandHandler = $method->getAttribute(CommandHandler::class);

            if (! $commandHandler) {
                continue;
            }

            $parameters = iterator_to_array($method->getParameters());

            if (count($parameters) !== 1) {
                continue;
            }

            $type = $parameters[0]->getType();

            if (! $type->isClass()) {
                continue;
            }

            $this->discoveryItems->add($location, [$commandHandler, $type->getName(), $method]);
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as [$commandHandler, $commandName, $method]) {
            $this->commandBusConfig->addHandler(
                commandHandler: $commandHandler,
                commandName: $commandName,
                handler: $method,
            );
        }
    }
}
