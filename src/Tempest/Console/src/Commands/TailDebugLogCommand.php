<?php

declare(strict_types=1);

namespace Tempest\Console\Commands;

use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\Highlight\VarExportLanguage\VarExportLanguage;
use Tempest\Console\Output\TailReader;
use Tempest\Container\Tag;
use Tempest\Highlight\Highlighter;
use Tempest\Log\LogConfig;

final readonly class TailDebugLogCommand
{
    public function __construct(
        private Console $console,
        private LogConfig $logConfig,
        #[Tag('console')]
        private Highlighter $highlighter,
    ) {
    }

    #[ConsoleCommand('tail:debug', description: 'Tails the debug log')]
    public function __invoke(): void
    {
        $debugLogPath = $this->logConfig->debugLogPath;

        $this->console->write('<h1>Debug</h1> ');

        if (! $debugLogPath) {
            $this->console->error('No debug log configured in LogConfig');

            return;
        }

        $dir = pathinfo($debugLogPath, PATHINFO_DIRNAME);

        if (! is_dir($dir)) {
            mkdir($dir);
        }

        if (! file_exists($debugLogPath)) {
            touch($debugLogPath);
        }

        $this->console->writeln("Listening at {$debugLogPath}");

        new TailReader()->tail(
            path: $debugLogPath,
            format: fn (string $text) => $this->highlighter->parse(
                $text,
                new VarExportLanguage(),
            ),
        );
    }
}
