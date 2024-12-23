<?php

declare(strict_types=1);

namespace Tempest\CommandBus\AsyncCommandRepositories;

use Tempest\CommandBus\CommandRepository;
use Tempest\CommandBus\Exceptions\CouldNotResolveCommand;
use function Tempest\Support\arr;

final readonly class FileCommandRepository implements CommandRepository
{
    public function store(string $uuid, object $command): void
    {
        $payload = serialize($command);

        file_put_contents(__DIR__ . "/../stored-commands/{$uuid}.pending.txt", $payload);
    }

    public function findPendingCommand(string $uuid): object
    {
        $path = __DIR__ . "/../stored-commands/{$uuid}.pending.txt";

        if (! file_exists($path)) {
            throw new CouldNotResolveCommand($uuid);
        }

        $payload = file_get_contents($path);

        return unserialize($payload);
    }

    public function markAsDone(string $uuid): void
    {
        $path = __DIR__ . "/../stored-commands/{$uuid}.pending.txt";

        unlink($path);
    }

    public function markAsFailed(string $uuid): void
    {
        if (! is_file(__DIR__ . "/../stored-commands/{$uuid}.pending.txt")) {
            return;
        }

        rename(
            from: __DIR__ . "/../stored-commands/{$uuid}.pending.txt",
            to: __DIR__ . "/../stored-commands/{$uuid}.failed.txt",
        );
    }

    public function getPendingCommands(): array
    {
        return arr(glob(__DIR__ . '/../stored-commands/*.pending.txt'))
            ->mapWithKeys(function (string $path) {
                $uuid = str_replace('.pending.txt', '', pathinfo($path, PATHINFO_BASENAME));
                $payload = file_get_contents($path);

                yield $uuid => unserialize($payload);
            })
            ->toArray();
    }
}
