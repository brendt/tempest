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

    #[ConsoleCommand(name: 'command:monitor')]
    public function __invoke(): void
    {
        $this->success('Monitoring for new commands. Press ctrl+c to stop.');

        /** @var \Symfony\Component\Process\Process[] $processes */
        $processes = [];

        while (true) { // @phpstan-ignore-line
            foreach ($processes as $uuid => $process) {
                $time = new DateTimeImmutable();

                if ($process->isTerminated()) {
                    if ($process->isSuccessful()) {
                        $this->writeln("<style=\"fg-green\">{$uuid}</style> finished at {$time->format('Y-m-d H:i:s')}");
                    } else {
                        $this->writeln("<style=\"fg-red\">{$uuid}</style> failed at {$time->format('Y-m-d H:i:s')}");
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

            $time = new DateTimeImmutable();
            $this->writeln("<h2>{$uuid}</h2> started at {$time->format('Y-m-d H:i:s')}");

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
