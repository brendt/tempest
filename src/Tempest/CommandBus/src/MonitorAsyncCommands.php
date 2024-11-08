<?php

declare(strict_types=1);

namespace Tempest\CommandBus;

use DateTimeImmutable;
use Symfony\Component\Process\Process;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\ExitCode;
use Tempest\Console\HasConsole;
use function Tempest\Support\arr;

final readonly class MonitorAsyncCommands
{
    use HasConsole;

    public function __construct(
        private AsyncCommandRepository $repository,
        private Console $console,
    ) {
    }

    #[ConsoleCommand(name: 'command:monitor')]
    public function __invoke(): void
    {
        $this->success("Monitoring for new commands. Press ctrl+c to stop.");

        /** @var \Symfony\Component\Process\Process[] $processes */
        $processes = [];

        while (true) { // @phpstan-ignore-line
            foreach ($processes as $uuid => $process) {
                $time = new DateTimeImmutable();

                if ($process->getExitCode() !== ExitCode::SUCCESS) {
                    $errorOutput = trim($process->getErrorOutput());

                    if ($errorOutput) {
                        $this->error($errorOutput);
                    }

                    $this->repository->markAsFailed($uuid);

                    $this->writeln("<error>{$uuid}</error> failed at {$time->format('Y-m-d H:i:s')}");

                    unset($processes[$uuid]);
                } elseif ($process->isTerminated()) {
                    $this->writeln("<success>{$uuid}</success> finished at {$time->format('Y-m-d H:i:s')}");

                    $this->repository->markAsDone($uuid);

                    unset($processes[$uuid]);
                }
            }

            $availableUuids = arr($this->repository->available())
                ->filter(fn (string $uuid) => ! in_array($uuid, array_keys($processes)));

            if (count($processes) === 5) {
                $this->sleep(0.5);

                continue;
            }

            if ($availableUuids->isEmpty()) {
                $this->sleep(0.5);

                continue;
            }

            // Start a task
            $uuid = $availableUuids->first();
            $time = new DateTimeImmutable();
            $this->writeln("<h2>{$uuid}</h2> started at {$time->format('Y-m-d H:i:s')}");
            $process = new Process(['php', 'tempest', 'command:handle', $uuid], getcwd());
            $process->start();
            $processes[$uuid] = $process;
        }
    }

    private function sleep(float $seconds): void
    {
        usleep((int) ($seconds * 1_000_000));
    }
}
