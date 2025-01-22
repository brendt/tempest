<?php

declare(strict_types=1);

namespace Tempest\EventBus;

use BackedEnum;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\TypeReflector;
use UnitEnum;

final class EventBusDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly EventBusConfig $eventBusConfig,
    ) {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getPublicMethods() as $method) {
            $eventHandler = $method->getAttribute(EventHandler::class);

            if (! $eventHandler) {
                continue;
            }

            $eventName = match (true) {
                $eventHandler->event instanceof BackedEnum => $eventHandler->event->value,
                $eventHandler->event instanceof UnitEnum => $eventHandler->event->name,
                is_string($eventHandler->event) => $eventHandler->event,
                default => null,
            };

            if ($eventName === null) {
                $parameters = iterator_to_array($method->getParameters());

                if ($parameters === []) {
                    continue;
                }

                /** @var TypeReflector $type */
                $type = $parameters[0]->getType();

                if (! $type->isClass() && ! $type->isInterface()) {
                    continue;
                }

                $eventName = $type->getName();
            }

            $this->discoveryItems->add($location, [$eventName, $eventHandler, $method]);
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as [$eventName, $eventHandler, $method]) {
            $this->eventBusConfig->addClassMethodHandler(
                event: $eventName,
                handler: $eventHandler,
                reflectionMethod: $method,
            );
        }
    }
}
