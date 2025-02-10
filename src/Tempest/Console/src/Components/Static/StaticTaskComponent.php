<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Static;

use Closure;
use DateTimeImmutable;
use Symfony\Component\Process\Process;
use Tempest\Console\Console;
use Tempest\Console\StaticConsoleComponent;
use Throwable;

final class StaticTaskComponent implements StaticConsoleComponent
{
    public function __construct(
        readonly string $label,
        private null|Process|Closure $handler = null,
    ) {
    }

    public function render(Console $console): bool
    {
        $console->keyValue($this->label, new DateTimeImmutable()->format('Y-m-d H:i:s'));

        $result = match (true) {
            $this->handler instanceof Closure => $this->executeClosureHandler($this->handler),
            $this->handler instanceof Process => $this->executeProcessHandler($this->handler),
            default => false,
        };

        $console->keyValue($this->label, match ($result) {
            true => '<style="bold fg-green">DONE</style>',
            default => '<style="bold fg-red">FAILED</style>',
        });

        return $result;
    }

    private function executeClosureHandler(Closure $handler): bool
    {
        try {
            return (bool) ($handler)();
        } catch (Throwable) {
            return false;
        }
    }

    private function executeProcessHandler(Process $process): bool
    {
        try {
            return $process->run() === 0;
        } catch (Throwable) {
            return false;
        }
    }
}
