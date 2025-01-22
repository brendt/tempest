<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Interactive;

use Closure;
use Generator;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tempest\Console\Components\ComponentState;
use Tempest\Console\Components\Concerns\HasErrors;
use Tempest\Console\Components\Concerns\HasState;
use Tempest\Console\Components\Renderers\SpinnerRenderer;
use Tempest\Console\Components\Renderers\TaskRenderer;
use Tempest\Console\InteractiveConsoleComponent;
use Tempest\Console\Terminal\Terminal;
use Throwable;
use function Tempest\Support\arr;

final class TaskComponent implements InteractiveConsoleComponent
{
    use HasErrors;
    use HasState;

    private TaskRenderer $renderer;

    private int $processId;

    private float $startedAt;

    private ?float $finishedAt = null;

    private array $sockets;

    private array $log = [];

    private(set) array $extensions = ['pcntl'];

    public function __construct(
        readonly string $label,
        private null|Process|Closure $handler = null,
    ) {
        $this->handler = $this->resolveHandler($handler);
        $this->renderer = new TaskRenderer(new SpinnerRenderer(), $label);
        $this->startedAt = hrtime(as_number: true);
    }

    public function render(Terminal $terminal): Generator
    {
        // If there is no task handler, we don't need to fork the process, as
        // it is a time-consuming operation. We can simply consider it done.
        if ($this->handler === null) {
            $this->state = ComponentState::SUBMITTED;

            yield $this->renderTask($terminal);

            return true;
        }

        $this->sockets = stream_socket_pair(domain: STREAM_PF_UNIX, type: STREAM_SOCK_STREAM, protocol: STREAM_IPPROTO_IP);
        $this->processId = pcntl_fork();

        if ($this->processId === -1) {
            throw new RuntimeException('Could not fork process');
        }

        if (! $this->processId) {
            $this->executeHandler();
        }

        try {
            fclose($this->sockets[0]);
            stream_set_blocking($this->sockets[1], enable: false);

            while (true) {
                // The process is still running, so we continue looping.
                if (pcntl_waitpid($this->processId, $status, flags: WNOHANG) === 0) {
                    yield $this->renderTask(
                        terminal: $terminal,
                        line: fread($this->sockets[1], length: 1024) ?: null,
                    );

                    usleep($this->renderer->delay());

                    continue;
                }

                // The process is done, we register the finishing timestamp,
                // close the communication socket and determine the finished state.
                fclose($this->sockets[1]);
                $this->finishedAt = hrtime(as_number: true);
                $this->state = match (pcntl_wifexited($status)) {
                    true => match (pcntl_wexitstatus($status)) {
                        0 => ComponentState::SUBMITTED,
                        default => ComponentState::ERROR,
                    },
                    default => ComponentState::CANCELLED,
                };

                yield $this->renderTask($terminal);

                return $this->state === ComponentState::SUBMITTED;
            }
        } finally {
            if ($this->state->isFinished() && $this->processId) {
                posix_kill($this->processId, SIGTERM);
            }

            $this->cleanupSockets();
        }
    }

    private function renderTask(Terminal $terminal, ?string $line = null): string
    {
        if ($line) {
            $this->log[] = $line;
        }

        return $this->renderer->render(
            terminal: $terminal,
            state: $this->state,
            startedAt: $this->startedAt,
            finishedAt: $this->finishedAt,
            hint: end($this->log) ?: null,
        );
    }

    private function cleanupSockets(): void
    {
        foreach ($this->sockets as $socket) {
            if (is_resource($socket)) {
                @fclose($socket);
            }
        }

        $this->sockets = [];
    }

    private function executeHandler(): void
    {
        $log = function (string ...$lines): void {
            arr($lines)
                ->flatMap(fn (string $line) => explode("\n", $line))
                ->each(function (string $line): void {
                    fwrite($this->sockets[0], $line);
                });
        };

        try {
            exit((int) (($this->handler ?? static fn (): bool => true)($log) === false));
        } catch (Throwable) {
            exit(1);
        }
    }

    private function resolveHandler(null|Process|Closure $handler): ?Closure
    {
        if ($handler === null) {
            return null;
        }

        if ($handler instanceof Process) {
            return static function (Closure $log) use ($handler): bool {
                return $handler->run(function (string $output, string $buffer) use ($log): bool {
                    if ($output === Process::ERR) {
                        return true;
                    }

                    if ($line = trim($buffer)) {
                        $log($buffer);
                    }

                    return true;
                }) === 0;
            };
        }

        return $handler;
    }

    public function renderFooter(Terminal $terminal): ?string
    {
        return null;
    }
}
