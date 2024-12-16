<?php

declare(strict_types=1);

namespace Tempest\Console\Components\Concerns;

use Tempest\Console\Components\ComponentState;
use Tempest\Console\InteractiveConsoleComponent;

/**
 * @mixin InteractiveConsoleComponent
 * @phpstan-require-implements InteractiveConsoleComponent
 */
trait HasState
{
    private ComponentState $state = ComponentState::ACTIVE;

    public function getState(): ComponentState
    {
        return $this->state;
    }

    public function setState(ComponentState $state): void
    {
        $this->state = $state;
    }
}
