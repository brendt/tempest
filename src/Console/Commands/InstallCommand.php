<?php

declare(strict_types=1);

namespace Tempest\Console\Commands;

use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;

final readonly class InstallCommand
{
    public function __construct(
        private Console $console,
    ) {
    }

    #[ConsoleCommand(
        name: 'install',
        description:  'Interactively install Tempest in your project'
    )]
    public function install(bool $force = false): void
    {
        $cwd = getcwd();

        if (! $force && ! $this->console->confirm(
            question: "Installing Tempest in {$cwd}, continue?",
        )) {
            return;
        }

        $this->copyTempest($cwd);

        $this->copyIndex($cwd);

        $this->copyEnvExample($cwd);

        $this->copyEnv($cwd);
    }

    private function copyEnv(string $cwd): void
    {
        $path = $cwd . '/.env';

        if (file_exists($path)) {
            $this->console->error("{$path} already exists, skipped.");

            return;
        }

        if (! $this->console->confirm(
            question: sprintf("Do you want to create %s?", $path),
            default: true,
        )) {
            return;
        }

        copy(__DIR__ . '/../../../.env.example', $path);

        $this->console->success("{$path} created");
    }

    private function copyEnvExample(string $cwd): void
    {
        $path = $cwd . '/.env.example';

        if (file_exists($path)) {
            $this->console->error("{$path} already exists, skipped.");

            return;
        }

        if (! $this->console->confirm(
            question: sprintf("Do you want to create %s?", $path),
            default: true,
        )) {
            return;
        }

        copy(__DIR__ . '/../../../.env.example', $path);

        $this->console->success("{$path} created");
    }

    private function copyTempest(string $cwd): void
    {
        $path = $cwd . '/tempest';

        if (file_exists($path)) {
            $this->console->error("{$path} already exists, skipped.");

            return;
        }

        if (! $this->console->confirm(
            question: sprintf("Do you want to create %s?", $path),
            default: true,
        )) {
            return;
        }

        copy(__DIR__ . '/../../../tempest', $path);

        $this->console->success("{$path} created");
    }

    private function copyIndex(string $cwd): void
    {
        $path = $cwd . '/public/index.php';

        if (file_exists($path)) {
            $this->console->error("{$path} already exists, skipped.");

            return;
        }

        if (! $this->console->confirm(
            question: sprintf("Do you want to create %s?", $path),
            default: true,
        )) {
            return;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), recursive: true);
        }

        copy(__DIR__ . '/../../../public/index.php', $path);

        $this->console->success("{$path} created");
    }
}
