<?php

declare(strict_types=1);

namespace Tempest\CommandBus;

use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ExitCode;
use Tempest\Console\HasConsole;
use Tempest\Container\Container;
use Throwable;
use function Tempest\Support\arr;

final readonly class HandleAsyncCommand
{
    use HasConsole;

    public function __construct(
        private CommandBusConfig $commandBusConfig,
        private Container $container,
        private Console $console,
        private CommandRepository $repository,
    ) {
    }

    #[ConsoleCommand(name: 'command:handle', description: 'Manually executes a pending command')]
    public function __invoke(?string $uuid = null): ExitCode
    {
        try {
            if ($uuid) {
                $command = $this->repository->findPendingCommand($uuid);
            } else {
                $command = arr($this->repository->getPendingCommands())->first();
            }

            if (! $command) {
                $this->error('No pending command found.');

                return ExitCode::ERROR;
            }

            $commandHandler = $this->commandBusConfig->handlers[$command::class] ?? null;

            if (! $commandHandler) {
                $commandClass = $command::class;
                $this->error("No handler found for command {$commandClass}.");

                return ExitCode::ERROR;
            }

            $commandHandler->handler->invokeArgs(
                $this->container->get($commandHandler->handler->getDeclaringClass()->getName()),
                [$command],
            );

            $this->repository->markAsDone($uuid);

            return ExitCode::SUCCESS;
        } catch (Throwable $throwable) {
            $this->repository->markAsFailed($uuid);
            $this->error($throwable->getMessage());

            return ExitCode::ERROR;
        }
    }
}
