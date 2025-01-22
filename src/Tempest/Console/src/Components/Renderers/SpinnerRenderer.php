<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Renderers;

use Tempest\Console\Components\ComponentState;
use Tempest\Console\Terminal\Terminal;

final class SpinnerRenderer
{
    private const array FRAMES = ['⠁', '⠁', '⠉', '⠙', '⠚', '⠒', '⠂', '⠂', '⠒', '⠲', '⠴', '⠤', '⠄', '⠄', '⠤', '⠠', '⠠', '⠤', '⠦', '⠖', '⠒', '⠐', '⠐', '⠒', '⠓', '⠋', '⠉', '⠈', '⠈'];

    private int $index = 0;

    private(set) int $speed = 80_000;

    public function render(Terminal $terminal, ComponentState $state): string
    {
        if ($state !== ComponentState::ACTIVE) {
            return '';
        }

        $margin = str_repeat(' ', times: 2);
        $previous = $this->index;

        $this->index = ($this->index + 1) % count(self::FRAMES);

        return self::FRAMES[$previous];
    }
}
