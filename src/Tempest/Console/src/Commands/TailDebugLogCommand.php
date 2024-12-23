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

        if (! $debugLogPath) {
            $this->console->error('No debug log configured in <code>LogConfig</code>.');

            return;
        }

        $dir = pathinfo($debugLogPath, PATHINFO_DIRNAME);

        if (! is_dir($dir)) {
            mkdir($dir);
        }

        if (! file_exists($debugLogPath)) {
            touch($debugLogPath);
        }

        $this->console->header('Tailing debug logs', "Reading <file='{$debugLogPath}'/>…");

        new TailReader()->tail(
            path: $debugLogPath,
            format: fn (string $text) => $this->highlighter->parse(
                $text,
                new VarExportLanguage(),
            ),
        );
    }
}
