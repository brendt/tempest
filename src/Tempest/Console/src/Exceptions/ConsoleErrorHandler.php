<?php

declare(strict_types=1);

namespace Tempest\Console\Exceptions;

use Tempest\Console\Console;
use Tempest\Console\ExitCode;
use Tempest\Console\HasExitCode;
use Tempest\Console\Input\ConsoleArgumentBag;
use Tempest\Container\Tag;
use Tempest\Core\ErrorHandler;
use Tempest\Core\Kernel;
use Tempest\Highlight\Escape;
use Tempest\Highlight\Highlighter;
use Throwable;

final readonly class ConsoleErrorHandler implements ErrorHandler
{
    public function __construct(
        private Kernel $kernel,
        #[Tag('console')]
        private Highlighter $highlighter,
        private Console $console,
        private ConsoleArgumentBag $argumentBag,
    ) {
    }

    public function handleException(Throwable $throwable): void
    {
        ll(exception: $throwable->getMessage());

        $this->console
            ->writeln()
            ->error($throwable::class)
            ->when(
                condition: $throwable->getMessage(),
                callback: fn (Console $console) => $console->error($throwable->getMessage()),
            )
            ->writeln($this->getSnippet($throwable->getFile(), $throwable->getLine()))
            ->writeln()
            ->writeln('<u>' . $throwable->getFile() . ':' . $throwable->getLine() . '</u>')
            ->writeln();

        if ($this->argumentBag->get('-v') !== null) {
            foreach ($throwable->getTrace() as $i => $trace) {
                $this->console->writeln("<style='bold fg-blue'>#{$i}</style> " . $this->formatTrace($trace));
            }

            $this->console->writeln();
        } else {
            $firstLine = $throwable->getTrace()[0];

            $this->console
                ->writeln('<style="fg-blue bold">#0</style> ' . $this->formatTrace($firstLine))
                ->writeln()
                ->writeln('<em>-v</em> show more')
                ->writeln();
        }

        $exitCode = $throwable instanceof HasExitCode ? $throwable->getExitCode() : ExitCode::ERROR;

        $this->kernel->shutdown($exitCode->value);
    }

    public function handleError(int $errNo, string $errstr, string $errFile, int $errLine): void
    {
        ll(error: $errstr);

        $this->console
            ->writeln()
            ->error($errstr)
            ->writeln($this->getSnippet($errFile, $errLine));
    }

    private function getSnippet(string $file, int $lineNumber): string
    {
        $highlighter = $this->highlighter->withGutter();
        $code = Escape::terminal($highlighter->parse(file_get_contents($file), 'php'));
        $lines = explode(PHP_EOL, $code);

        $lines[$lineNumber - 1] = $lines[$lineNumber - 1] . ' <style="fg-red"><</style>';

        $excerptSize = 5;
        $start = max(0, $lineNumber - $excerptSize - 2);
        $lines = array_slice($lines, $start, $excerptSize * 2);

        return PHP_EOL . implode(PHP_EOL, $lines);
    }

    private function formatTrace(mixed $trace): string
    {
        if (isset($trace['file'])) {
            return '<u>' . $trace['file'] . ':' . $trace['line'] . '</u>';
        }

        if (isset($trace['class'])) {
            return $trace['class'] . $trace['type'] . $trace['function'];
        }

        return $trace['function'] . '()';
    }
}
