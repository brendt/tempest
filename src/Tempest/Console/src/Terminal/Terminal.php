<?php

declare(strict_types=1);

namespace Tempest\Console\Terminal;

use Generator;
use Tempest\Console\Console;
use Tempest\Console\Cursor;
use Tempest\Console\GenericCursor;
use Tempest\Console\HasCursor;
use Tempest\Console\InteractiveConsoleComponent;
use Tempest\Console\Point;
use function function_exists;
use function Tempest\Support\arr;
use const DIRECTORY_SEPARATOR;

final class Terminal
{
    public int $width;

    public int $height;

    public Cursor $cursor;

    private Cursor $initialCursor;

    private ?string $previousRender = null;

    private ?string $tty = null;

    private bool $supportsTty = true;

    public function __construct(
        private readonly Console $console,
    ) {
        $this->updateActualSize();

        $this->initialCursor = $this->supportsTty()
            ? new TerminalCursor($this->console, $this)
            : new GenericCursor();

        $this->cursor = clone $this->initialCursor;
    }

    public function switchToInteractiveMode(): self
    {
        if (! $this->supportsTty()) {
            return $this;
        }

        $this->tty = exec('stty -g');
        system('stty -echo');
        system('stty -icanon');
        system('stty -isig');

        return $this;
    }

    public function switchToNormalMode(): self
    {
        if (! $this->supportsTty()) {
            return $this;
        }

        $this->cursor->show();

        if ($this->tty) {
            system("stty {$this->tty}");
        }

        system('stty echo');
        system('stty icanon');
        system('stty isig');
        $this->tty = null;

        $this->console->writeln();

        return $this;
    }

    /** @return Generator<string|null> */
    public function render(InteractiveConsoleComponent $component, array $validationErrors = []): Generator
    {
        $rendered = $component
            ->setErrors($validationErrors)
            ->render($this);

        if (! $rendered instanceof Generator) {
            $rendered = (function (string $content): Generator {
                yield $content;

                return null;
            })($rendered);
        }

        foreach ($rendered as $content) {
            $footerLinesForContent = [];

            if ($validationErrors) {
                $content .= PHP_EOL . arr($validationErrors)
                    ->map(fn (string $error) => "   <style=\"fg-yellow\">{$error}</style>")
                    ->implode(PHP_EOL)
                    ->append(PHP_EOL)
                    ->toString();
            }

            if ($footer = $component->renderFooter($this)) {
                $footerLinesForContent[] = $footer;
            }

            if ($footerLinesForContent !== []) {
                $content .= PHP_EOL . implode(PHP_EOL, $footerLinesForContent) . PHP_EOL;
            }

            if ($this->previousRender !== $content) {
                $this->clear();
                $this->write($content);
                $this->resetInitialCursor();
            }

            if ($component instanceof HasCursor) {
                $this->placeComponentCursor($component);
            } else {
                $this->cursor->hide();
            }

            yield;
        }

        return $rendered->getReturn();
    }

    public function disableTty(): self
    {
        $this->supportsTty = false;

        return $this;
    }

    public function supportsTty(): bool
    {
        if ($this->supportsTty === false) {
            return false;
        }

        if (! function_exists('shell_exec')) {
            return false;
        }

        return (bool) shell_exec('stty 2> '.('\\' === DIRECTORY_SEPARATOR ? 'NUL' : '/dev/null'));
    }

    private function clear(): self
    {
        $this->cursor
            ->place($this->initialCursor->getPosition())
            ->clearAfter();

        return $this;
    }

    private function resetInitialCursor(): void
    {
        $this->updateActualSize();

        $requiredHeight = substr_count($this->previousRender, PHP_EOL);
        $availableHeight = $this->height - $this->initialCursor->getPosition()->y;

        if ($requiredHeight > $availableHeight) {
            $this->initialCursor->setPosition(new Point(
                x: $this->initialCursor->getPosition()->x,
                y: $this->height - $requiredHeight,
            ));
        }
    }

    public function placeCursorToEnd(): void
    {
        $lastRenderHeight = substr_count($this->previousRender ?? '', PHP_EOL);

        $this->cursor->place(new Point(
            x: 0,
            y: $this->initialCursor->getPosition()->y + $lastRenderHeight,
        ));
    }

    private function write(string $content): self
    {
        $this->console->write($content);

        $this->previousRender = $content;

        return $this;
    }

    private function placeComponentCursor(HasCursor $component): self
    {
        $initialCursorPosition = $this->initialCursor->getPosition();

        $componentCursorPosition = $component->getCursorPosition($this);

        $this->cursor->place(new Point(
            x: $initialCursorPosition->x + $componentCursorPosition->x,
            y: $initialCursorPosition->y + $componentCursorPosition->y,
        ));

        if ($component->cursorVisible()) {
            $this->cursor->show();
        } else {
            $this->cursor->hide();
        }

        return $this;
    }

    private function updateActualSize(): self
    {
        $this->width = $this->supportsTty() ? (int) exec('tput cols') : 80;
        $this->height = $this->supportsTty() ? (int) exec('tput lines') : 25;

        return $this;
    }
}
