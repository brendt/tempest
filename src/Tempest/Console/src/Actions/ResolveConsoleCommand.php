<?php

declare(strict_types=1);

namespace Tempest\Console\Actions;

use Exception;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ConsoleConfig;

final readonly class ResolveConsoleCommand
{
    public function __construct(
        private ConsoleConfig $consoleConfig,
    ) {
    }

    public function __invoke(array|string $command): ConsoleCommand
    {
        if (is_string($command) && array_key_exists($command, $this->consoleConfig->commands)) {
            return $this->consoleConfig->commands[$command];
        }

        if (is_string($command) && class_exists($command)) {
            $command = [$command, '__invoke'];
        }

        if (is_array($command)) {
            $command = array_find(
                array: $this->consoleConfig->commands,
                callback: fn (ConsoleCommand $consoleCommand) =>
                    $consoleCommand->handler->getDeclaringClass()->getName() === $command[0]
                        && $consoleCommand->handler->getName() === $command[1],
            );

            if ($command !== null) {
                return $command;
            }
        }

        throw new Exception('Command not found.');
    }
}
