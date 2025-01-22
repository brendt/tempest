<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Renderers;

use Tempest\Console\Components\ComponentState;
use Tempest\Console\Terminal\Terminal;
use function Tempest\Support\str;

final class TaskRenderer
{
    use RendersInput;

    public function __construct(
        private readonly SpinnerRenderer $spinner,
        private readonly string $label,
    ) {
    }

    public function render(Terminal $terminal, ComponentState $state, float $startedAt, ?float $finishedAt, ?string $hint = null): string
    {
        $this->prepareRender($terminal, $state);
        $this->label($this->label);

        $runtime = fn (float $finishedAt) => $finishedAt
            ? number_format(($finishedAt - $startedAt) / 1_000_000, decimals: 0)
            : null;

        $hint = match ($this->state) {
            ComponentState::ERROR => '<style="fg-red">An error occurred.</style>',
            ComponentState::CANCELLED => '<style="fg-yellow">Cancelled.</style>',
            ComponentState::SUBMITTED => $finishedAt
                ? '<style="fg-gray">Done in <style="bold">'.$runtime($finishedAt).'ms</style>.</style>'
                : '<style="fg-gray">Done.</style>',
            default => $hint ?? $runtime(hrtime(as_number: true)) . 'ms',
        };

        $this->line(
            append: str()
                ->append(match ($this->state) {
                    ComponentState::SUBMITTED => '<style="fg-green">✔</style>',
                    ComponentState::ERROR => '<style="fg-red">✖</style>',
                    ComponentState::CANCELLED => '<style="fg-yellow">⚠</style>',
                    default => '<style="fg-gray">'.$this->spinner->render($terminal, $this->state).'</style>',
                })
                ->append('<style="fg-gray"> ', $hint, '</style>'),
        );

        // If a task has an error, it is no longer active.
        if (in_array($this->state, [ComponentState::ACTIVE, ComponentState::CANCELLED])) {
            $this->newLine();
        }

        return $this->finishRender();
    }

    public function delay(): int
    {
        return $this->spinner->speed;
    }
}
