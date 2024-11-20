<?php

declare(strict_types=1);

namespace Tempest\Console\Discovery;

use Tempest\Console\ConsoleCommand;
use Tempest\Console\Schedule;
use Tempest\Console\Scheduler\Interval;
use Tempest\Console\Scheduler\ScheduledInvocation;
use Tempest\Console\Scheduler\SchedulerConfig;
use Tempest\Container\Container;
use Tempest\Core\Discovery;
use Tempest\Core\DiscoveryLocation;
use Tempest\Core\IsDiscovery;
use Tempest\Reflection\ClassReflector;
use Tempest\Reflection\MethodReflector;

final class ScheduleDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly SchedulerConfig $schedulerConfig,
    ) {}

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        foreach ($class->getPublicMethods() as $method) {
            $schedule = $method->getAttribute(Schedule::class);

            if ($schedule === null) {
                continue;
            }

            $command = $method->getAttribute(ConsoleCommand::class);

            $this->discoveryItems->add($location, [$method, $command, $schedule]);
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems->flatten() as [$method, $command, $schedule]) {
            if ($command) {
                $this->schedulerConfig->addCommandInvocation($method, $command, $schedule);
            } else {
                $this->schedulerConfig->addMethodInvocation($method, $schedule);
            }
        }
    }
}
