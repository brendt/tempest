<?php

declare(strict_types=1);

namespace Tempest\CommandBus;

use DateTimeImmutable;
use Symfony\Component\Process\Process;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\HasConsole;
use Tempest\Console\Input\ConsoleArgumentBag;
use function Tempest\Support\arr;

final readonly class MonitorAsyncCommands
{
    use HasConsole;

    public function __construct(
        private CommandRepository $repository,
        private ConsoleArgumentBag $argumentBag,
        private Console $console,
    ) {
    }

    #[ConsoleCommand(name: 'command:monitor', description: 'Monitors and executes pending async commands')]
    public function __invoke(): void
    {
        $this->info('Monitoring for new commands. Press <em>Ctrl+C</em> to stop.');
        $this->writeln();

        /** @var \Symfony\Component\Process\Process[] $processes */
        $processes = [];

        while (true) { // @phpstan-ignore-line
            foreach ($processes as $uuid => $process) {
                if ($process->isTerminated()) {
                    if ($process->isSuccessful()) {
                        $this->console->keyValue(
                            key: "<style='fg-gray'>{$uuid}</style>",
                            value: "<style='fg-green bold'>SUCCESS</style>",
                        );
                    } else {
                        $this->console->keyValue(
                            key: "<style='fg-gray'>{$uuid}</style>",
                            value: "<style='fg-red bold'>FAILED</style>",
                        );
                    }

                    if ($output = trim($process->getOutput())) {
                        $this->writeln($output);
                    }

                    if ($errorOutput = trim($process->getErrorOutput())) {
                        $this->writeln($errorOutput);
                    }

                    unset($processes[$uuid]);
                }
            }

            $availableCommands = arr($this->repository->getPendingCommands())
                ->filter(fn (object $command, string $uuid) => ! array_key_exists($uuid, $processes));

            if (count($processes) === 5) {
                $this->sleep(0.5);

                continue;
            }

            if ($availableCommands->isEmpty()) {
                $this->sleep(0.5);

                continue;
            }

            // Start a task
            $uuid = $availableCommands->keys()->first();

            // TODO: remove logging in favor of "task" in HandleAsyncCommand
            $time = new DateTimeImmutable();
            $this->console->keyValue(
                key: $uuid,
                value: "<style='fg-gray'>{$time->format('Y-m-d H:i:s')}</style>",
            );

            $process = new Process([
                $this->argumentBag->getBinaryPath(),
                $this->argumentBag->getCliName(),
                'command:handle',
                $uuid,
            ], getcwd());

            $process->start();

            $processes[$uuid] = $process;
        }
    }

    private function sleep(float $seconds): void
    {
        usleep((int) ($seconds * 1_000_000));
    }
}
