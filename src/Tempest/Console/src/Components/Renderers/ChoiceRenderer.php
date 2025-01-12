<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Renderers;

use Tempest\Console\Components\ComponentState;
use Tempest\Console\Components\OptionCollection;
use Tempest\Console\Components\TextBuffer;
use Tempest\Console\Point;
use Tempest\Console\Terminal\Terminal;

final class ChoiceRenderer
{
    use RendersInput;

    public function __construct(
        private bool $multiple = true,
        private int $maximumOptions = 10,
        private ?string $default = null,
    ) {
    }

    public function render(
        Terminal $terminal,
        ComponentState $state,
        ?string $label,
        TextBuffer $query,
        OptionCollection $options,
        ?string $placeholder = null,
        bool $filtering = true,
    ): string {
        $this->prepareRender($terminal, $state);
        $this->label($label);

        if ($state === ComponentState::SUBMITTED) {
            $this->line(
                $this->multiple
                    ? '<style="fg-gray italic">' . count($options->getSelectedOptions()) . ' selected</style>'
                    : $options->getActive()?->value,
            )->newLine();
        } elseif ($state === ComponentState::CANCELLED) {
            if ($query->text || $this->default) {
                $this->line($this->style('fg-gray italic strikethrough', $query->text ?: $this->default))->newLine();
            }
        } else {
            $this->line(
                $this->style($filtering ? 'fg-magenta' : 'fg-gray', '/ '),
                empty($query->text)
                    ? $this->style('fg-gray dim', $placeholder ?? ($this->multiple && ! $filtering ? (count($options->getSelectedOptions()) . ' selected') : ''))
                    : $this->style($filtering ? 'bold fg-cyan' : 'fg-gray', $this->truncateLeft($query->text, maxLineOffset: 2)),
            );
            $this->newLine();

            if ($state === ComponentState::ACTIVE) {
                $displayOptions = $options->getScrollableSection(
                    offset: $this->calculateScrollOffset($options, $this->maximumOptions, $options->getCurrentIndex()),
                    count: $this->maximumOptions,
                );

                foreach ($displayOptions as $option) {
                    if (! $this->multiple) {
                        $this->line(
                            $options->isActive($option) ? $this->style('fg-magenta', '→ ') : '  ',
                            $this->style($options->isSelected($option) ? 'fg-green bold' : '', $option->value),
                        );
                    } else {
                        $this->line(
                            $options->isActive($option) ? $this->style('fg-magenta', '→ ') : '  ',
                            $options->isSelected($option) ? $this->style('fg-green', '✔︎ ') : $this->style('fg-gray', '⋅ '),
                            $this->style($options->isSelected($option) ? 'fg-green bold' : '', $option->value),
                        );
                    }

                    $this->newLine();
                }
            }
        }

        return $this->finishRender();
    }

    public function getCursorPosition(Terminal $terminal, TextBuffer $buffer): Point
    {
        $position = $buffer->getRelativeCursorPosition($terminal->width - self::MARGIN_X - 1 - self::PADDING_X - self::MARGIN_X);
        $actual = $position->y > 0
            ? mb_strlen($this->truncateLeft($buffer->text, maxLineOffset: 2))
            : $position->x;

        return new Point(
            x: $actual + (self::MARGIN_X + 1 + self::PADDING_X) + 2, // +1 is the border width, +2 is for decoration
            y: self::MARGIN_TOP + $this->offsetY,
        );
    }
}
