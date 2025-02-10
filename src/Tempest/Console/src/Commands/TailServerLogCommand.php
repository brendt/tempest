<?php

declare(strict_types=1);

namespace Tempest\Console\Commands;

use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\Highlight\LogLanguage\LogLanguage;
use Tempest\Console\Output\TailReader;
use Tempest\Container\Tag;
use Tempest\Highlight\Highlighter;
use Tempest\Log\LogConfig;

final readonly class TailServerLogCommand
{
    public function __construct(
        private Console $console,
        private LogConfig $logConfig,
        #[Tag('console')]
        private Highlighter $highlighter,
    ) {
    }

    #[ConsoleCommand('tail:server', description: 'Tails the server log')]
    public function __invoke(): void
    {
        $serverLogPath = $this->logConfig->serverLogPath;

        if (! $serverLogPath) {
            $this->console->error('No server log configured in <code>LogConfig</code>.');

            return;
        }

        if (! file_exists($serverLogPath)) {
            $this->console->error("No valid server log at <file='{$serverLogPath}'/>");

            return;
        }

        $this->console->header('Tailing server logs', "Reading <file='{$serverLogPath}'/>…");

        new TailReader()->tail(
            path: $serverLogPath,
            format: fn (string $text) => $this->highlighter->parse(
                $text,
                new LogLanguage(),
            ),
        );
    }
}
