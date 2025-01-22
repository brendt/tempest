<?php

declare(strict_types=1);

namespace Tempest\Console\Commands;

use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\Highlight\LogLanguage\LogLanguage;
use Tempest\Console\Output\TailReader;
use Tempest\Container\Tag;
use Tempest\Highlight\Highlighter;
use Tempest\Log\Channels\AppendLogChannel;
use Tempest\Log\LogConfig;

final readonly class TailProjectLogCommand
{
    public function __construct(
        private Console $console,
        private LogConfig $logConfig,
        #[Tag('console')]
        private Highlighter $highlighter,
    ) {
    }

    #[ConsoleCommand('tail:project', description: 'Tails the project log')]
    public function __invoke(): void
    {
        $appendLogChannel = null;

        foreach ($this->logConfig->channels as $channel) {
            if ($channel instanceof AppendLogChannel) {
                $appendLogChannel = $channel;

                break;
            }
        }

        $this->console->write('<h1>Project</h1> ');

        if ($appendLogChannel === null) {
            $this->console->error('No AppendLogChannel registered');

            return;
        }

        $dir = pathinfo($appendLogChannel->getPath(), PATHINFO_DIRNAME);

        if (! is_dir($dir)) {
            mkdir($dir);
        }

        if (! file_exists($appendLogChannel->getPath())) {
            touch($appendLogChannel->getPath());
        }

        $this->console->writeln("Listening at {$appendLogChannel->getPath()}");

        new TailReader()->tail(
            path: $appendLogChannel->getPath(),
            format: fn (string $text) => $this->highlighter->parse(
                $text,
                new LogLanguage(),
            ),
        );
    }
}
