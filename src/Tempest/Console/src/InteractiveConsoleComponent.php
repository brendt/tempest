<?php

declare(strict_types=1);

namespace Tempest\Console;

use Generator;
use Tempest\Console\Components\ComponentState;
use Tempest\Console\Terminal\Terminal;

interface InteractiveConsoleComponent
{
    public function render(Terminal $terminal): Generator|string|bool;

    public function renderFooter(Terminal $terminal): ?string;

    /** @param string[] $errors */
    public function setErrors(array $errors): self;

    public function getState(): ComponentState;

    public function setState(ComponentState $state): void;
}
