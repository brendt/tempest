<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Renderers;

use Tempest\Console\Components\ComponentState;
use Tempest\Console\Components\TextBuffer;
use Tempest\Console\Point;
use Tempest\Console\Terminal\Terminal;
use function Tempest\Support\str;

final class TextInputRenderer
{
    use RendersInput;

    private int $scrollOffset = 0;

    public function __construct(
        private ?bool $multiline = false,
        private int $maximumLines = 4,
    ) {
    }

    public function render(
        Terminal $terminal,
        ComponentState $state,
        TextBuffer $buffer,
        ?string $label,
        ?string $placeholder = null,
        ?string $hint = null,
    ): string {
        $this->prepareRender($terminal, $state);
        $this->label($label);

        if ($hint) {
            $this->offsetY++;
            $this->line($this->style('fg-gray', $hint))->newLine();
        }

        // splits the text to an array so we can work with individual lines
        $lines = str(($buffer->text ?: $placeholder) ?: '')
            ->explode("\n")
            ->flatMap(fn (string $line) => str($line)->split($this->maxLineCharacters)->toArray())
            ->map(static fn (string $line) => str($line)->replaceEnd("\n", ' '));

        // calculates scroll offset based on cursor position
        $this->scrollOffset = $this->calculateScrollOffset($lines, $this->maximumLines, $buffer->getRelativeCursorPosition($this->maxLineCharacters)->y);

        // slices lines to display only the visible portion
        $displayLines = $lines->slice($this->scrollOffset, $this->state->isFinished() ? 1 : $this->maximumLines);

        // if there is nothing to display after the component is done, show "no input"
        if ($this->state->isFinished() && $lines->count() === 0) {
            $this->line($this->style('italic dim', 'No input.'))->newLine();
        }

        // renders visible lines
        foreach ($displayLines as $line) {
            $this->line(
                // Add a symbol depending on the state
                match ($this->state) {
                    ComponentState::DONE => '<style="fg-green">✓</style> ',
                    default => '',
                },
                // Prints the actual line
                $this->style(
                    style: match (true) {
                        $this->state === ComponentState::DONE => 'dim',
                        $this->state === ComponentState::CANCELLED => 'italic dim strikethrough',
                        default => null,
                    },
                    content: $line,
                ),
                // Add an ellipsis if there is more than one line but we're done with the input
                $this->state->isFinished() && $this->multiline && $lines->count() > 1
                    ? '<style="dim">…</style>'
                    : '',
            )->newLine();
        }

        // fills remaining lines if less than max display lines
        if (! $this->state->isFinished()) {
            $lines = $this->multiline ? $this->maximumLines : 1;

            for ($i = $displayLines->count(); $i < $lines; $i++) {
                $this->line("\n");
            }
        }

        return $this->finishRender();
    }

    public function getCursorPosition(Terminal $terminal, TextBuffer $buffer): Point
    {
        $position = $buffer->getRelativeCursorPosition($terminal->width - self::MARGIN_X - 1 - self::PADDING_X - self::MARGIN_X);

        return new Point(
            x: $position->x + (self::MARGIN_X + 1 + self::PADDING_X), // +1 is the border width
            y: $position->y - $this->scrollOffset + (self::MARGIN_TOP + $this->offsetY), // subtract scroll offset
        );
    }
}
