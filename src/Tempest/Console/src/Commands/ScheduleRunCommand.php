<?php

declare(strict_types=1);

namespace Tempest\Console\Commands;

use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\HasConsole;
use Tempest\Console\Scheduler;
use Tempest\Console\Scheduler\ScheduledInvocationRan;
use Tempest\EventBus\EventHandler;

final readonly class ScheduleRunCommand
{
    use HasConsole;

    public function __construct(
        private Scheduler $scheduler,
        private Console $console,
    ) {
    }

    #[ConsoleCommand('schedule:run', description: 'Executes due tasks')]
    public function __invoke(): void
    {
        $this->console->header('Executing tasks');
        $this->scheduler->run();
    }

    #[EventHandler]
    public function onScheduledInvocationRan(ScheduledInvocationRan $invocation): void
    {
        $this->console->keyValue(
            key: $invocation->invocation->getCommandName(),
            value: '<style="bold fg-green">COMPLETED</style>',
        );
    }
}
